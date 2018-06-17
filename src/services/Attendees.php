<?php
/**
 * event-helper plugin for Craft CMS 3.x
 *
 * Event Helper is a simple Craft CMS plugin that gives you the ability to track event attendance.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\eventhelper\services;

use nzmebooks\eventhelper\EventHelper;

use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;
use craft\db\Query;
use DateTimeZone;

/**
 * @author    meBooks
 * @package   EventHelper
 * @since     1.0.0
 */
class Attendees extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Gets all attendees for all upcoming events from the database.
     * This is only useful in the CP, where you might want to view all attendees
     * across all of your events.
     * Return a multidimensional array that's available in the CP.
     *
     * @method getAttendees
     * @return array
     */
    public function getAttendees()
    {
        $dateNowUTC = DateTimeHelper::currentUTCDateTime();
        $dateNowUTC = $dateNowUTC->format('Y-m-d H:i:s');

        $query = (new Query())
            ->select('eventhelperattendees.*, content.title')
            ->from('eventhelperattendees')
            ->leftJoin('entries AS entries', 'entries.id = eventhelperattendees.eventId')
            ->join('JOIN', 'content AS content', 'content.elementId = entries.id')
            ->where("content.field_dateStart > \"$dateNowUTC\"")
            ->all();

        $data = array();

        foreach ($query as $row) {
            $row['dateCreated'] = date_format(date_create($row['dateCreated']), "M d, Y");
            $row['dateUpdated'] = date_format(date_create($row['dateUpdated']), "M d, Y");

            $data[] = $row;
        }

        return $data;
    }

      /**
     * Gets all attendees for all upcoming events from the database, for use in a CSV report.
     * This is only useful in the CP, where you might want to view all attendees
     * across all of your events.
     * Return a multidimensional array.
     *
     * @method getUpcomingAttendeesForCsv
     * @return array
     */
    public function getUpcomingAttendeesForCsv()
    {
        $dateNowUTC = DateTimeHelper::currentUTCDateTime();
        $dateNowUTC = $dateNowUTC->format('Y-m-d H:i:s');

        $query = (new Query())
            ->select('eventhelperattendees.name, eventhelperattendees.email, eventhelperattendees.dateCreated, content.field_dateStart, content.title')
            ->from('eventhelperattendees')
            ->leftJoin('entries AS entries', 'entries.id = eventhelperattendees.eventId')
            ->join('JOIN', 'content AS content', 'content.elementId = entries.id')
            ->where("content.field_dateStart > \"$dateNowUTC\"")
            ->orderBy('content.field_dateStart DESC')
            ->all();

        $data = array();

        foreach ($query as $row) {
            $row['dateCreated'] = date_format(date_create($row['dateCreated']), "Y-m-d");

            $data[] = $row;
        }

        $data = array_merge([['Name', 'Email', 'RSVP Date', 'Event Start', 'Event']], $data);

        return $data;
    }

    /**
     * Gets all attendees for all past events from the database, for use in a CSV report.
     * This is only useful in the CP, where you might want to view all attendees
     * across all of your events.
     * Return a multidimensional array.
     *
     * @method getPastAttendeesForCsv
     * @return array
     */
    public function getPastAttendeesForCsv()
    {
        $dateNowUTC = DateTimeHelper::currentUTCDateTime();
        $dateNowUTC = $dateNowUTC->format('Y-m-d H:i:s');

        $query = (new Query())
            ->select('eventhelperattendees.name, eventhelperattendees.email, eventhelperattendees.dateCreated, content.field_dateStart, content.title')
            ->from('eventhelperattendees')
            ->leftJoin('entries AS entries', 'entries.id = eventhelperattendees.eventId')
            ->join('JOIN', 'content AS content', 'content.elementId = entries.id')
            ->where("content.field_dateStart < \"$dateNowUTC\"")
            ->orderBy('content.field_dateStart DESC')
            ->all();

        $data = array();

        foreach ($query as $row) {
            $row['dateCreated'] = date_format(date_create($row['dateCreated']), "Y-m-d");

            $data[] = $row;
        }

        $data = array_merge([['Name', 'Email', 'RSVP Date', 'Event Start', 'Event']], $data);

        return $data;
    }
}
