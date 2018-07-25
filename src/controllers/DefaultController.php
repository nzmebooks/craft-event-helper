<?php
/**
 * event-helper plugin for Craft CMS 3.x
 *
 * Event Helper is a simple Craft CMS plugin that gives you the ability to track event attendance.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\eventhelper\controllers;

use nzmebooks\eventhelper\EventHelper;

use Craft;
use craft\web\Controller;

/**
 * Class EventHelperController
 *
 * @author    meBooks
 * @package   EventHelper
 * @since     1.0.0
 */
class DefaultController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     *  Our index action
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $attendees = EventHelper::$plugin->attendees->getAttendees();
        $events = EventHelper::$plugin->events->getEvents();

        $this->renderTemplate('event-helper/home/index', array(
          'tab' => 'home',
          'attendees' => $attendees,
          'events' => $events,
        ));
    }

    /**
     * Our settings action
     *
     * @return mixed
     */
    public function actionSettings()
    {
        $settings = EventHelper::$plugin->getSettings();

        $this->renderTemplate('event-helper/settings/index', array(
          'tab' => 'settings',
          'settings' => $settings,
        ));
    }
}
