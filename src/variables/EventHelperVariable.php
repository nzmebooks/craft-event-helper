<?php

/**
 * event-helper plugin for Craft CMS 3.x
 *
 * Event Helper is a simple Craft CMS plugin that gives you the ability
 * to track event attendance.
 *
 * @link      https://mebooks.co.nz
 * @copyright 2018 meBooks
 */

namespace nzmebooks\eventhelper\variables;

use nzmebooks\eventhelper\EventHelper;

use Craft;

/**
 * Class EventHelperVariable
 *
 * Template variables for the Event Helper plugin
 *
 * @author    meBooks
 * @package   Eventhelper
 * @since     1.0.0
 */
class EventHelperVariable
{
    // Public Methods
    // =========================================================================

    public function getPluginName()
    {
        $pluginName = EventHelper::$plugin->getName();

        return $pluginName;
    }

    /**
     * Returns an array of attendees
     *
     * @method getAttendees
     * @return array
     */
    public function getAttendees()
    {
        return EventHelper::$plugin->attendees->getAttendees();
    }

    /**
     * Returns a boolean indicating whether a supplied event is attended
     * by a supplied user.
     *
     * @method isAttended
     *
     * @param int $eventId
     * @param int $userId
     *
     * @return Boolean
     */
    public function isAttended($eventId)
    {
        return EventHelper::$plugin->attendees->isAttended($eventId);
    }

    public function getEvents()
    {
        $query = EventHelper::$plugin->events->getEvents();

        foreach ($query as &$row) {
            foreach ($row as $key => &$value) {
                $row[$key] = html_entity_decode($value, ENT_QUOTES);
            }
        }

        return $query;
    }

    /**
     * Return an array of events for a given category title
     *
     * @param string $categoryTitle
     * @param int    $limit
     *
     * @return array
     */
    public function getEventsByCategory($categoryTitle, $limit)
    {
        $query = EventHelper::$plugin->events->getEventsByCategory(
            $categoryTitle,
            $limit
        );

        foreach ($query as &$row) {
            foreach ($row as $key => &$value) {
                $row[$key] = html_entity_decode($value, ENT_QUOTES);
            }
        }

        return $query;
    }

    /**
     * Returns an .ics ical string
     *
     * @param Entry $event
     * @return string ical
     */
    public function renderIcs($event)
    {
        return EventHelper::$plugin->events->renderIcs($event);
    }
  }
