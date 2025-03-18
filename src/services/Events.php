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
        // die(var_dump($query->prepare($builder)->createCommand()->rawSql));

        foreach ($records as $index => $record) {
          $content = json_decode($record['content'], true);
          $keys = array_keys($content);

          if (
            ($keys[1] ?? null) && ($content[$keys[1]]['date'] ?? null)
          ) {
            $startDate = isset($content[$keys[1]]) && is_array($content[$keys[1]]) ? $content[$keys[1]]['date'] : null;
          }

          if ($startDate && $startDate < $dateNowUTCFormatted) {
            unset($records[$index]);
            continue;
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

        foreach ($records as $index => $record) {
          $content = json_decode($record['content'], true);
          $keys = array_keys($content);

          if (
            ($keys[1] ?? null) && ($content[$keys[1]]['date'] ?? null)
          ) {
            $record['field_dateStart'] = isset($content[$keys[1]]) && is_array($content[$keys[1]]) ? $content[$keys[1]]['date'] : null;
          }

          if (
            ($keys[2] ?? null) && ($content[$keys[2]]['date'] ?? null)
          ) {
            $record['field_dateEnd'] = isset($content[$keys[2]]) && is_array($content[$keys[2]]) ? $content[$keys[2]]['date'] : null;
          }

          if (($record['field_dateStart'] ?? null) && $record['field_dateStart'] < $dateNowUTCFormatted) {
            unset($records[$index]);
            continue;
          }
        }

        return $records;
    }
}
