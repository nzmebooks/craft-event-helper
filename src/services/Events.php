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

use plainlanguage\plainIcs\variables\PlainIcsVariable;

use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;
use craft\db\Query;

/**
 * Class Events
 *
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
            'title'           => $event->longTitle ?? $event->title,
            'description'     => $description,
            'startDateTime'   => $event->dateStart->format('Y-m-d H:i:sP'),
            'endDateTime'     => $event->dateEnd->format('Y-m-d H:i:sP'),
            'url'             => $event->url,
            'location'        => $event->location,
            'alarmAction'     => 'DISPLAY',
            'alarmDescription'=> 'Reminder',
            'alarmTrigger'    => '-PT30M',
            'useTimezone'     => true,
            'useUtc'          => false,
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

    /**
     * Get all events
     *
     * @return mixed
     */
    public function getEvents($id = null)
    {
        $dateNowUTC = DateTimeHelper::currentUTCDateTime();
        $dateNowUTCFormatted = $dateNowUTC->format('Y-m-d H:i:s');

        $db = Craft::$app->getDb();

        $query = (new Query())
            ->select([
                'entries.id',
                'elements_sites.title',
                'COUNT(attendee.seats) AS attendance',
                'JSON_UNQUOTE(JSON_EXTRACT(elements_sites.content, CONCAT(\'$.\', cfDateStart.layoutElementUid, \'.date\'))) AS field_dateStart',
                'JSON_UNQUOTE(JSON_EXTRACT(elements_sites.content, CONCAT(\'$.\', cfDateEnd.layoutElementUid, \'.date\'))) AS field_dateEnd',
                'JSON_UNQUOTE(JSON_EXTRACT(elements_sites.content, CONCAT(\'$.\', cfEventCode.layoutElementUid))) AS field_eventCode'
            ])
            ->from('elements_sites')
            ->join('JOIN', 'entries AS entries', 'elements_sites.elementId = entries.id')
            ->join('JOIN', 'sections AS sections', 'entries.sectionId = sections.id')
            ->join('JOIN', 'changedfields AS cfDateStart', 'cfDateStart.elementId = entries.id')
            ->join('JOIN', 'fields AS fDateStart', 'cfDateStart.fieldId = fDateStart.id AND fDateStart.handle = "dateStart"')
            ->join('JOIN', 'changedfields AS cfDateEnd', 'cfDateEnd.elementId = entries.id')
            ->join('JOIN', 'fields AS fDateEnd', 'cfDateEnd.fieldId = fDateEnd.id AND fDateEnd.handle = "dateEnd"')
            ->join('JOIN', 'changedfields AS cfEventCode', 'cfEventCode.elementId = entries.id')
            ->join('JOIN', 'fields AS fEventCode', 'cfEventCode.fieldId = fEventCode.id AND fEventCode.handle = "eventCode"')
            ->leftJoin('eventhelperattendees AS attendee', 'entries.id = attendee.eventId')
            ->where('sections.handle = "events"')
            ->andWhere('JSON_EXTRACT(elements_sites.content, CONCAT(\'$.\', cfDateStart.layoutElementUid, \'.date\')) IS NOT NULL')
            ->andWhere(['>=', 'JSON_EXTRACT(elements_sites.content, CONCAT(\'$.\', cfDateStart.layoutElementUid, \'.date\'))', $dateNowUTCFormatted]);

        if ($id) {
            $query->andWhere(['entries.id' => $id]);
        }

        // $records = $query
        //     ->groupBy('elements_sites.title');
        // $builder = Craft::$app->getDb()->getQueryBuilder();
        // die(var_dump($records->prepare($builder)->createCommand()->rawSql));

        $records = $query
            ->groupBy('elements_sites.title')
            ->all();

        // Set empty eventCode for records that don't have one
        foreach ($records as &$record) {
            if (!isset($record['field_eventCode']) || $record['field_eventCode'] === null) {
                $record['field_eventCode'] = '';
            }
        }

        return $records;
    }

  /**
   * Get all events by the supplied category
   *
   * @param string $categoryTitle
   * @param int $limit
   * @return mixed
   */
    public function getEventsByCategory($categoryTitle, $limit)
    {
        $dateNowUTC = DateTimeHelper::currentUTCDateTime();
        $dateNowUTCFormatted = $dateNowUTC->format('Y-m-d H:i:s');

        $db = Craft::$app->getDb();

        $query = (new Query())
            ->select([
                'entries.id',
                'elements_sites.title',
                'JSON_UNQUOTE(JSON_EXTRACT(elements_sites.content, CONCAT(\'$.\', cfDateStart.layoutElementUid, \'.date\'))) AS field_dateStart',
                'JSON_UNQUOTE(JSON_EXTRACT(elements_sites.content, CONCAT(\'$.\', cfDateEnd.layoutElementUid, \'.date\'))) AS field_dateEnd',
                'JSON_UNQUOTE(JSON_EXTRACT(elements_sites.content, CONCAT(\'$.\', cfEventCode.layoutElementUid))) AS field_eventCode'
            ])
            ->from('elements_sites')
            ->join('JOIN', 'entries AS entries', 'elements_sites.elementId = entries.id')
            ->join('JOIN','sections AS sections', 'entries.sectionId = sections.id')
            ->join('JOIN', 'changedfields AS cfDateStart', 'cfDateStart.elementId = entries.id')
            ->join('JOIN', 'fields AS fDateStart', 'cfDateStart.fieldId = fDateStart.id AND fDateStart.handle = "dateStart"')
            ->join('JOIN', 'changedfields AS cfDateEnd', 'cfDateEnd.elementId = entries.id')
            ->join('JOIN', 'fields AS fDateEnd', 'cfDateEnd.fieldId = fDateEnd.id AND fDateEnd.handle = "dateEnd"')
            ->join('JOIN', 'changedfields AS cfEventCode', 'cfEventCode.elementId = entries.id')
            ->join('JOIN', 'fields AS fEventCode', 'cfEventCode.fieldId = fEventCode.id AND fEventCode.handle = "eventCode"')
            ->join('JOIN','relations AS relations', 'entries.id = relations.sourceId')
            ->join('JOIN','elements_sites AS relatedContent', 'relatedContent.elementId = relations.targetId')
            ->where('sections.handle = "events"')
            ->andWhere("relatedContent.title = '$categoryTitle'")
            ->andWhere('JSON_EXTRACT(elements_sites.content, CONCAT(\'$.\', cfDateStart.layoutElementUid, \'.date\')) IS NOT NULL')
            ->andWhere(['>=', 'JSON_EXTRACT(elements_sites.content, CONCAT(\'$.\', cfDateStart.layoutElementUid, \'.date\'))', $dateNowUTCFormatted]);

        $records = $query
            ->limit($limit)
            ->all();

        // Set empty eventCode for records that don't have one
        foreach ($records as &$record) {
            if (!isset($record['field_eventCode']) || $record['field_eventCode'] === null) {
                $record['field_eventCode'] = '';
            }
        }

        return $records;
    }
}
