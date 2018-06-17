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

use nzmebooks\eventhelper\services\EventHelperService as EventHelperService;
use nzmebooks\eventhelper\services\AttendeeService as AttendeeService;
use nzmebooks\eventhelper\services\EventService as EventService;
use nzmebooks\eventhelper\variables\EventHelperVariable;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

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
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'eventHelperController' => EventHelperController::class,
            'attendeeController' => AttendeeController::class,
        ]);

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'event-helper/default';
                $event->rules['siteActionTrigger2'] = 'event-helper/attendee';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'event-helper/default/do-something';
                $event->rules['cpActionTrigger2'] = 'event-helper/attendee/do-something';
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('event-helper', EventHelperVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'event-helper',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

}
