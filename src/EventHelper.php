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

use nzmebooks\eventhelper\controllers\AttendeesController;
use nzmebooks\eventhelper\variables\EventHelperVariable;
use nzmebooks\eventhelper\services\EventHelperService;
use nzmebooks\eventhelper\services\Attendees;
use nzmebooks\eventhelper\services\Events;
// use nzmebooks\eventhelper\variables\EventHelperVariable;
use nzmebooks\eventhelper\models\Settings;

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
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }
}
