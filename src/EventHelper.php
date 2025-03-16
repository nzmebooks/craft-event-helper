<?php
/**
 * event-helper plugin for Craft CMS 3.x
 *
 * Event Helper is a simple Craft CMS plugin that gives you the ability to track event attendance.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\eventhelper;

use nzmebooks\eventhelper\variables\EventHelperVariable;
use nzmebooks\eventhelper\services\EventHelperService;
use nzmebooks\eventhelper\services\Attendees;
use nzmebooks\eventhelper\services\Events;
use nzmebooks\eventhelper\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;
use craft\web\User;
use craft\elements\User as UserElement;
use craft\elements\Entry;
use craft\errors\InvalidElementException;

use yii\base\Event;

/**
 * Class Eventhelper
 *
 * @author    meBooks
 * @package   Eventhelper
 * @since     1.0.0
 *
 * @property  EventHelperService $eventhelperService
 * @property  AttendeeService $attendeeService
 * @property  EventService $eventService
 */
class EventHelper extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var EventHelper
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        self::$plugin = $this;

        // Register Components (Services)
        $this->setComponents([
            'eventhelper' => EventHelperService::class,
            'attendees' => Attendees::class,
            'events' => Events::class,
        ]);

        // Register our control panel rules
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['event-helper'] = 'event-helper/default';
                $event->rules['event-helper/settings'] = 'event-helper/default/settings';
            }
        );

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('eventHelper', EventHelperVariable::class);
            }
        );

        // Listen for the EVENT_BEFORE_LOGIN event
        // and check whether the user is logging in in order to RSVP to an event.
        // If so, check if they are not active, and if so, activate them.
        Event::on(
            UserElement::class,
            UserElement::EVENT_BEFORE_AUTHENTICATE,
            function (\craft\events\AuthenticateUserEvent $userEvent) {
                $post = Craft::$app->getRequest()->getBodyParams();
                $loginName = $post['loginName'] ?? null;
                $password = $post['password'] ?? null;

                $user = Craft::$app->users->getUserByUsernameOrEmail($loginName);

                if (!$user) {
                    return;
                }

                if (!Craft::$app->getSecurity()->validatePassword($password, $user->password)) {
                    return;
                }

                $eventId = $post['eventId'] ?? null;

                if ($eventId) {
                    // User is logging in to RSVP to an event
                    // find the event
                    $event = Entry::find()
                        ->filterWhere(['entries.id' => $eventId])
                        ->one();

                    if ($event) {
                        // Check if the user is inactive
                        if ($user->status === UserElement::STATUS_INACTIVE || $user->status === UserElement::STATUS_SUSPENDED) {
                            try {
                                // Activate the user
                                Craft::$app->getUsers()->activateUser($user);

                                // Don't perform authentication, as we've just activated the user
                                // and we know their password is good
                                $userEvent->performAuthentication = false;
                            } catch (InvalidElementException $e) {
                                Craft::error("Failed to activate user during login with ID {$user->id}: " . implode(', ', $user->getErrorSummary(true)), __METHOD__);
                                $userEvent->sender->authError = 'Failed to activate user during login.';

                                return false;
                            }

                            Craft::info("User {$user->email} activated during login.", __METHOD__);
                        }
                        return true;
                    }
                }
            }
        );

        // Listen for the EVENT_AFTER_LOGIN event
        // and check whether the user is logging in in order to RSVP to an event.
        // If so, mark them as attended.
        Event::on(
            User::class,
            User::EVENT_AFTER_LOGIN,
            function (\yii\web\UserEvent $userEvent) {
                $post = Craft::$app->getRequest()->getBodyParams();
                $eventId = $post['eventId'] ?? null;

                if ($eventId) {
                    // User is logging in to RSVP to an event
                    // find the event
                    $event = Entry::find()
                        ->filterWhere(['entries.id' => $eventId])
                        ->one();

                    if ($event) {
                        // Get the logged-in user
                        $user = $userEvent->identity;

                        Craft::$app->getRequest()->setBodyParams([
                            Craft::$app->config->general->csrfTokenName => Craft::$app->request->getCsrfToken(), // Add CSRF token
                            'redirect' => $event->url,
                            'eventId' => $event->id,
                            'userId' => $user->id,
                            'name' => $user->fullName,
                            'email' => $user->email,
                            'seats' => 1,
                        ]);

                        Craft::$app->runAction('event-helper/attendees/save-attendee');
                        Craft::$app->end();
                    }

                    $eventsGlobals = Craft::$app->globals->getSetByHandle('events');

                    $message = $eventsGlobals->rsvpFailure
                    ? $eventsGlobals->rsvpFailure
                    : 'Something wasn\'t right about your reservation. Try submitting it again.';

                    Craft::$app->getSession()->setError($message);

                    return $this->redirectToPostedUrl();
                }
            }
        );
    }

    /**
     * Returns the user-facing name of the plugin, which can override the name
     * in composer.json
     *
     * @return string
     */
    public function getName()
    {
        return Craft::t('event-helper', 'Event Helper');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return Settings
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    // protected function settingsHtml(): string
    // {
    //   return Craft::$app->view->renderTemplate('event-helper/settings/index', [
    //     'tab' => 'settings',
    //     'settings' => $this->getSettings(),
    //   ]);
    // }
}
