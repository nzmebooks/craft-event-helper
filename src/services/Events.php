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
class Events extends Component
{
    // Public Methods
    // =========================================================================

    /*
     * @return mixed
     */
    public function getEvents()
    {
        $dateNowUTC = DateTimeHelper::currentUTCDateTime();
        $dateNowUTCFormatted = $dateNowUTC->format('Y-m-d H:i:s');

        $query = (new Query())
            ->select('entries.id, content.title, content.field_dateStart, content.field_dateEnd, COUNT(attendee.seats) AS attendance')
            ->from('content')
            ->join('JOIN', 'entries AS entries', 'content.elementId = entries.id')
            ->join('JOIN', 'sections AS sections', 'entries.sectionId = sections.id')
            ->leftJoin('eventhelperattendees AS attendee', 'entries.id = attendee.eventId')
            ->where('sections.handle = "events"')
            ->andWhere("content.field_dateStart > \"$dateNowUTCFormatted\"")
            ->groupBy('content.title')
            ->all();

        return $query;
    }
}
