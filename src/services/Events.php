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
use plainlanguage\plainIcs\variables\PlainIcsVariable;

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

    /**
     * Return an array of params ready to call craft.ics
     *
     * @method getEventICSParams
     * @param object $model An Event object.
     * @return array
     */
    public function getEventICSParams($event)
    {
        $description = $event->subheading;
        if ($event->subheading) {
            $description = $event->subheading . "\n\n";
        }

        if ($event->instructions) {
            $description = $description . $event->instructions . "\n\n";
        }

        if ($event->mapUrl) {
            $description = $description . 'Map link: ' . $event->mapUrl;
        }

        $params = array(
            'title'           => $event->title,
            'description'     => $description,
            'startDateTime'   => $event->dateStart->format('Y-m-d H:i:sP'),
            'endDateTime'     => $event->dateEnd->format('Y-m-d H:i:sP'),
            'url'             => $event->url,
            'location'        => $event->location,
            'alarmAction'     => 'DISPLAY',
            'alarmDescription'=> 'Reminder',
            'alarmTrigger'    => '-PT30M',
        );

        return $params;
    }

    /**
     * Call craft.ics plugin to render an ics file
     *
     * @method renderIcs
     * @param object $model An Event object.
     * @return undefined
     */
    public function renderIcs($event, $return = false) {
        $params = $this->getEventICSParams($event);

        $plainIcsVariable = new PlainIcsVariable();
        $results = $plainIcsVariable->render($params, $return);

        if ($return) {
            return $results;
        }
    }

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

    public function getEventsByCategory($categoryTitle, $limit)
    {
        $dateNowUTC = DateTimeHelper::currentUTCDateTime();
        $dateNowUTCFormatted = $dateNowUTC->format('Y-m-d H:i:s');

        $query = (new Query())
            ->select('entries.id, content.title, content.field_dateStart, content.field_dateEnd')
            ->from('content')
            ->join('JOIN', 'entries AS entries', 'content.elementId = entries.id')
            ->join('JOIN','sections AS sections', 'entries.sectionId = sections.id')
            ->join('JOIN','relations AS relations', 'entries.id = relations.sourceId')
            ->join('JOIN','content AS relatedContent', 'relatedContent.elementId = relations.targetId')
            ->where('sections.handle = "events"')
            ->andWhere("content.field_dateStart > \"$dateNowUTCFormatted\"")
            ->andWhere("relatedContent.title = '$categoryTitle'")
            ->limit($limit)
            ->all();

        return $query;
    }
}
