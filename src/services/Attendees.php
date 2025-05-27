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
use craft\mail\Message;
use craft\db\Query;
use yii\web\Cookie;
use nystudio107\cookies\Cookies;

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
    public function getAttendees($eventId = null)
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
            ->join('JOIN', 'elements_sites', 'elements_sites.elementId = entries.id');

        if ($eventId) {
            $records = $records
                ->where('eventhelperattendees.eventId = ' . $eventId);
        }

        $records = $records
            ->all();

        // $builder = Craft::$app->getDb()->getQueryBuilder();
        // die(var_dump($query->prepare($builder)->createCommand()->rawSql));

        $data = array();

        foreach ($records as $index => $row) {
            $content = json_decode($row['content'], true);

            // Skip processing if an event ID was specified (we want all attendees regardless of date)
            if (!$eventId) {
                // Find all date fields in the content
                $dateTimes = [];
                foreach ($content as $uid => $value) {
                    // Check if this is a date field (has a 'date' key with a string value)
                    if (is_array($value) && isset($value['date']) && is_string($value['date'])) {
                        $dateTimes[$uid] = $value['date'];
                    }
                }

                // If we don't have any dates, skip this record but still include in results
                if (!empty($dateTimes)) {
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

                    // Store the resolved dates in the row for easier access
                    $row['field_dateStart'] = $startDate;

                    // If we have at least one more date after the start date, use it as the end date
                    if (count($dateValues) > 1 && isset($dateValues[count($dateValues) - 1])) {
                        $row['field_dateEnd'] = $dateValues[count($dateValues) - 1];
                    }
                }
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

            // Find all date fields in the content
            $dateTimes = [];
            foreach ($content as $uid => $value) {
                // Check if this is a date field (has a 'date' key with a string value)
                if (is_array($value) && isset($value['date']) && is_string($value['date'])) {
                    $dateTimes[$uid] = $value['date'];
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

            // Store the resolved dates in the row for easier access
            $row['field_dateStart'] = $startDate;

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

            // Find all date fields in the content
            $dateTimes = [];
            foreach ($content as $uid => $value) {
                // Check if this is a date field (has a 'date' key with a string value)
                if (is_array($value) && isset($value['date']) && is_string($value['date'])) {
                    $dateTimes[$uid] = $value['date'];
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

            // For past attendees, we want to keep only those with start dates in the past
            if ($startDate > $dateNowUTCFormatted) {
                unset($records[$index]);
                continue;
            }

            // Store the resolved dates in the row for easier access
            $row['field_dateStart'] = $startDate;

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
    public function isAttended($eventId, $userId = null)
    {
        if (!$userId) {
            // Check whether the user is already logged in
            $userId = Craft::$app->getUser()->id;
        }

        if (!$userId) {
            // Check whether there is a cookie set for the eventId  -- the user may be logged out
            $userId = Cookies::$plugin->cookies->get('user-registed-for-event-' . $eventId);
        }

        if (!$userId) {
            return false;
        }

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

        // Save a cookie indicating that the user has registered for this event
        $this->setEventCookie($model->eventId, $model->userId);

        return $record->save();
    }

    public function setEventCookie($eventId, $userId)
    {
        $name = 'user-registed-for-event-' . $eventId;
        $value = $userId;
        $expire = time() + (86400 * 365); // 365 days
        $path = '/';
        $domain = Craft::$app->getRequest()->getHostName();
        $secure = Craft::$app->getRequest()->getIsSecureConnection();
        $httpOnly = true;
        $sameSite = Cookie::SAME_SITE_LAX; // or Cookie::SAME_SITE_STRICT, Cookie::SAME_SITE_NONE

        Cookies::$plugin->cookies->set($name, $value, $expire, $path, $domain, $secure, $httpOnly, $sameSite);
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
