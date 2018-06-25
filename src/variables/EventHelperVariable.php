<?php
/**
 * event-helper plugin for Craft CMS 3.x
 *
 * Event Helper is a simple Craft CMS plugin that gives you the ability to track event attendance.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\eventhelper\variables;

use nzmebooks\eventhelper\EventHelper;

use Craft;

/**
 * @author    meBooks
 * @package   EventHelper
 * @since     1.0.0
 */
class EventHelperVariable
{
    // Public Methods
    // =========================================================================

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
     * Returns a boolean indicating whether a supplied event is attended by a supplied user.
     *
     * @method isAttended
     * @return Boolean
     */
    public function isAttended($eventId, $userId)
    {
        return EventHelper::$plugin->attendees->isAttended($eventId, $userId);
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
     * @param string $categoryTitle
     * @param integer $limit
     * @return array
     */
    public function getEventsByCategory($categoryTitle, $limit)
    {
        $query = EventHelper::$plugin->events->getEventsByCategory($categoryTitle, $limit);

        foreach ($query as &$row) {
            foreach ($row as $key => &$value) {
                $row[$key] = html_entity_decode($value, ENT_QUOTES);
            }
        }

        return $query;
    }

    public function renderIcs($event)
    {
        return EventHelper::$plugin->events->renderIcs($event);
    }
  }
