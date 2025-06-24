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

        $records = (new Query())
            ->select('
                entries.id,
                elements_sites.title,
                elements_sites.content,
                COUNT(attendee.seats) AS attendance'
            )
            ->from('elements_sites')
            ->join('JOIN', 'entries AS entries', 'elements_sites.elementId = entries.id')
            ->join('JOIN', 'sections AS sections', 'entries.sectionId = sections.id')
            ->leftJoin('eventhelperattendees AS attendee', 'entries.id = attendee.eventId')
            ->where('sections.handle = "events"');

        if ($id) {
            $records = $records->andWhere(['entries.id' => $id]);
        }

        $records = $records
            ->groupBy('elements_sites.title')
            ->all();

        // $builder = Craft::$app->getDb()->getQueryBuilder();
        // die(var_dump($records->prepare($builder)->createCommand()->rawSql));

        foreach ($records as $index => &$record) {
          $content = json_decode($record['content'], true);

          // Find all date fields in the content and the event code
          $dateTimes = [];
          $eventCode = null;
          foreach ($content as $uid => $value) {
              // Check if this is a date field (has a 'date' key with a string value)
              if (is_array($value) && isset($value['date']) && is_string($value['date'])) {
                  $dateTimes[$uid] = $value['date'];
              }

              // Look for event code (short string, likely all caps or alphanumeric)
              if (is_string($value) && preg_match('/^[A-Z0-9]{2,10}$/', $value)) {
                  $eventCode = $value;
              }
          }

          // If we don't have any dates, remove this record
          if (empty($dateTimes)) {
              unset($records[$index]);
              continue;
          }

          // Sort dates chronologically
          asort($dateTimes);

          // Get the middle date (start date)
          $dateKeys = array_keys($dateTimes);
          $dateValues = array_values($dateTimes);

          // If we have 3 or more dates, get the middle one
          if (count($dateValues) >= 3) {
              $middleIndex = floor(count($dateValues) / 2);
              $startDate = $dateValues[$middleIndex];
          }
          // If we have 2 dates, get the first one (earlier date)
          else if (count($dateValues) == 2) {
              $startDate = $dateValues[0];
          }
          // If we have only 1 date, use that
          else {
              $startDate = $dateValues[0];
          }

          // If this event's start date is in the past, remove it
          if ($startDate < $dateNowUTCFormatted) {
              unset($records[$index]);
              continue;
          }

          // Store the resolved dates and event code in the record for easier access
          $record['field_dateStart'] = $startDate;

          // If we have at least one more date after the start date, use it as the end date
          if (count($dateValues) > 1 && isset($dateValues[count($dateValues) - 1])) {
              $record['field_dateEnd'] = $dateValues[count($dateValues) - 1];
          }

          // Store the event code if found
          if ($eventCode) {
              $record['field_eventCode'] = $eventCode;
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

        $records = (new Query())
            ->select('
              entries.id,
              elements_sites.title,
              elements_sites.content'
            )
            ->from('elements_sites')
            ->join('JOIN', 'entries AS entries', 'elements_sites.elementId = entries.id')
            ->join('JOIN','sections AS sections', 'entries.sectionId = sections.id')
            ->join('JOIN','relations AS relations', 'entries.id = relations.sourceId')
            ->join('JOIN','elements_sites AS relatedContent', 'relatedContent.elementId = relations.targetId')
            ->where('sections.handle = "events"')
            ->andWhere("relatedContent.title = '$categoryTitle'")
            ->limit($limit)
            ->all();

        foreach ($records as $index => &$record) {
          $content = json_decode($record['content'], true);

          // Find all date fields in the content and the event code
          $dateTimes = [];
          $eventCode = null;
          foreach ($content as $uid => $value) {
              // Check if this is a date field (has a 'date' key with a string value)
              if (is_array($value) && isset($value['date']) && is_string($value['date'])) {
                  $dateTimes[$uid] = $value['date'];
              }

              // Look for event code (short string, likely all caps or alphanumeric)
              if (is_string($value) && preg_match('/^[A-Z0-9]{2,10}$/', $value)) {
                  $eventCode = $value;
              }
          }

          // If we don't have any dates, remove this record
          if (empty($dateTimes)) {
              unset($records[$index]);
              continue;
          }

          // Sort dates chronologically
          asort($dateTimes);

          // Get the middle date (start date)
          $dateKeys = array_keys($dateTimes);
          $dateValues = array_values($dateTimes);

          // If we have 3 or more dates, get the middle one
          if (count($dateValues) >= 3) {
              $middleIndex = floor(count($dateValues) / 2);
              $startDate = $dateValues[$middleIndex];
          }
          // If we have 2 dates, get the first one (earlier date)
          else if (count($dateValues) == 2) {
              $startDate = $dateValues[0];
          }
          // If we have only 1 date, use that
          else {
              $startDate = $dateValues[0];
          }

          // If this event's start date is in the past, remove it
          if ($startDate < $dateNowUTCFormatted) {
              unset($records[$index]);
              continue;
          }

          // Store the resolved dates and event code in the record for easier access
          $record['field_dateStart'] = $startDate;

          // If we have at least one more date after the start date, use it as the end date
          if (count($dateValues) > 1 && isset($dateValues[count($dateValues) - 1])) {
              $record['field_dateEnd'] = $dateValues[count($dateValues) - 1];
          }

          // Store the event code if found
          if ($eventCode) {
              $record['field_eventCode'] = $eventCode;
          }
        }

        return $records;
    }
}
