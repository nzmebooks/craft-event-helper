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
use nzmebooks\eventhelper\records\AttendeeRecord;

use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;
use craft\elements\Entry;
use craft\web\View;
use craft\mail\Message;
use craft\db\Query;
use DateTimeZone;

/**
 * Class Attendees
 *
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
     *
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
        $dateNowUTCFormatted = $dateNowUTC->format('Y-m-d H:i:s');

        $records = (new Query())
            ->select('
              eventhelperattendees.*,
              elements_sites.title,
              elements_sites.content
            ')
            ->from('eventhelperattendees')
            ->leftJoin('entries AS entries', 'entries.id = eventhelperattendees.eventId')
            ->join('JOIN', 'elements_sites', 'elements_sites.elementId = entries.id')
            ->all();

        // $builder = Craft::$app->getDb()->getQueryBuilder();
        // die(var_dump($query->prepare($builder)->createCommand()->rawSql));

        $data = array();

        foreach ($records as $index => $row) {
            $content = json_decode($row['content'], true);
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

            $row['dateCreated'] = date_format(date_create($row['dateCreated']), "M d, Y");
            $row['dateUpdated'] = date_format(date_create($row['dateUpdated']), "M d, Y");

            $data[] = $row;
        }

        return $data;
    }

    /**
     * Gets all attendees for all upcoming events from the database, for use in a CSV report.
     *
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
        $dateNowUTCFormatted = $dateNowUTC->format('Y-m-d H:i:s');

        $records = (new Query())
            ->select('
              eventhelperattendees.name,
              eventhelperattendees.email,
              eventhelperattendees.dateCreated,
              elements_sites.title AS field_dateStart,
              elements_sites.title,
              elements_sites.content
            ')
            ->from('eventhelperattendees')
            ->leftJoin('entries', 'entries.id = eventhelperattendees.eventId')
            ->join('JOIN', 'elements_sites', 'elements_sites.elementId = entries.id')
            ->all();

        $data = array();

        foreach ($records as $index => $row) {
            $content = json_decode($row['content'], true);
            $keys = array_keys($content);

            if (
              ($keys[0] ?? null) && ($content[$keys[0]]['date'] ?? null)
            ) {
              $row['field_dateStart'] = isset($content[$keys[0]]) && is_array($content[$keys[0]]) ? $content[$keys[0]]['date'] : null;
            } else {
              if (
                ($keys[2] ?? null) && ($content[$keys[2]]['date'] ?? null)
              ) {
                $row['field_dateStart'] = isset($content[$keys[2]]) && is_array($content[$keys[2]]) ? $content[$keys[2]]['date'] : null;
              }
            }

            if (($row['field_dateStart'] ?? null) && $row['field_dateStart'] < $dateNowUTCFormatted) {
              unset($records[$index]);
              continue;
            }

            $row['dateCreated'] = date_format(date_create($row['dateCreated']), "Y-m-d H:i:s");
            unset($row['content']);

            $data[] = $row;
        }

        $data = array_merge([['Name', 'Email', 'RSVP Date', 'Event Start', 'Event']], $data);

        return $data;
    }

    /**
     * Gets all attendees for all past events from the database, for use in a CSV report.
     *
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
        $dateNowUTCFormatted = $dateNowUTC->format('Y-m-d H:i:s');

        $records = (new Query())
            ->select('
              eventhelperattendees.name,
              eventhelperattendees.email,
              eventhelperattendees.dateCreated,
              elements_sites.title AS field_dateStart,
              elements_sites.title,
              elements_sites.content'
            )
            ->from('eventhelperattendees')
            ->leftJoin('entries', 'entries.id = eventhelperattendees.eventId')
            ->join('JOIN', 'elements_sites', 'elements_sites.elementId = entries.id')
            ->all();

        $data = array();

        foreach ($records as $index => $row) {
            $content = json_decode($row['content'], true);
            $keys = array_keys($content);

            if (
              ($keys[1] ?? null) && ($content[$keys[1]]['date'] ?? null)
            ) {
              $row['field_dateStart'] = isset($content[$keys[1]]) && is_array($content[$keys[1]]) ? $content[$keys[1]]['date'] : null;
            }

            if (($row['field_dateStart'] ?? null) && $row['field_dateStart'] > $dateNowUTCFormatted) {
              unset($records[$index]);
              continue;
            }

            $row['dateCreated'] = date_format(date_create($row['dateCreated']), "Y-m-d H:i:s");
            unset($row['content']);

            $data[] = $row;
        }

        $data = array_merge([['Name', 'Email', 'RSVP Date', 'Event Start', 'Event']], $data);

        return $data;
    }

    /**
     * Determines whether a given event is attended by a given user.
     *
     * @method isAttended
     * @return Boolean
     */
    public function isAttended($eventId, $userId)
    {
        $query = (new Query())
            ->select('
              eventhelperattendees.*,
              elements_sites.title
            ')
            ->from('eventhelperattendees')
            ->leftJoin('entries', 'entries.id = eventhelperattendees.eventId')
            ->join('JOIN', 'elements_sites', 'elements_sites.elementId = entries.id')
            ->where('eventhelperattendees.userId = ' . $userId)
            ->andWhere('entries.id = ' . $eventId)
            ->all();

        return count($query) ? true : false;
    }

    /**
     * Send notification email to an individual attendee.
     *
     * @method sendRSVPNotifications
     * @param object $model An Attendee object.
     * @return boolean
     */
    public function sendRSVPNotifications($attendee)
    {
        $settings = EventHelper::$plugin->getSettings();

        // find the event
        $event = Entry::find()
            ->filterWhere(['entries.id' => $attendee->eventId])
            ->one();

        $eventTitle = $event->longTitle ?? $event->title;

        // determine the human-readable dates
        if ($event->dateEnd) {
            if ($event->dateEnd->format('Y-m-d') == $event->dateStart->format('Y-m-d')) {
                $dates = $event->dateStart->format('l j F, Y, g:ia') . '-' . $event->dateEnd->format('g:ia');
            } else {
                $dates = $event->dateStart->format('l j F, Y, g:ia') . '-' . $event->dateEnd->format('l j F, Y, g:ia');
            }
        } else {
            $dates = $event->dateStart->format('l j F, Y, g:ia');
        }

        // template the settings body template
        $rsvpNotificationBodyTemplated = \Craft::$app->view->renderString(
            trim($settings->rsvpNotificationBody),
            array(
                'title' => $eventTitle,
                'dates' => $dates,
                'location' => $event->location,
                'url' => $event->url,
                'instructions' => $event->instructions,
            )
        );

        // Change multiple linebreaks (i.e. empty lines) to <br />s
        $rsvpNotificationBodyTemplated = preg_replace("/([\r\n]){2,}/m", '<br /><br />', $rsvpNotificationBodyTemplated);

        // Template the email template
        $template = Craft::$app->getMailer()->template ?? 'email.twig';
        $emailTemplated = \Craft::$app->view->renderTemplate(
            $template,
            array(
                'body' => $rsvpNotificationBodyTemplated,
            )
        );

        // construct and send the email
        // TODO: consider abstracting email functionality to a separate class, as per
        // https://github.com/vigetlabs/craft-disqusnotify/blob/master/src/services/Email.php
        $email = new Message();
        $emailSettings = Craft::$app->getProjectConfig()->get('email');

        $email->setFrom([$emailSettings['fromEmail'] => $emailSettings['fromName']]);
        $email->setTo($attendee->email);
        $email->setSubject('China Capable Public Sector event: ' . $eventTitle);
        $email->setHtmlBody($emailTemplated);

        $tmpName = tempnam(sys_get_temp_dir(), 'cal.ics');
        if ($tmpName) {
            $tmpFile = fopen($tmpName, 'w');
            $ics = EventHelper::$plugin->events->renderIcs($event, true);
            fputs($tmpFile, $ics);
            fclose($tmpFile);

            $email->attach($tmpName, array(
                'fileName' => 'cal.ics',
                'contentType' => 'text/calendar',
            ));
        }

        return Craft::$app->mailer->send($email);
    }

    /**
     * Save an individual attendee to the Attendees table.
     *
     * @method saveAttendee
     * @param object $model An Attendee object.
     * @return boolean
     */
    public function saveAttendee($model)
    {
        $attributes = array(
            'userId' => $model->userId,
            'name' => $model->name,
            'email' => $model->email,
            'eventId' => $model->eventId,
            'seats' => $model->seats,
        );

        $record = new AttendeeRecord();

        foreach ($attributes as $key => $value) {
            $record->setAttribute($key, $value);
        }

        return $record->save();
    }

    /**
     * Remove an individual attendee from the Attendees table.
     *
     * @method removeAttendee
     * @param object $model An Attendee object.
     * @return integer
     */
    public function removeAttendee($model)
    {
        $record = new AttendeeRecord();

        return $record->deleteAll([
            'userId' => $model->userId,
            'eventId' => $model->eventId,
        ]);
    }
}
