<?php
/**
 * event-helper plugin for Craft CMS 3.x
 *
 * Event Helper is a simple Craft CMS plugin that gives you the ability to track event attendance.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\eventhelper\controllers;

use nzmebooks\eventhelper\EventHelper;
use nzmebooks\eventhelper\services\Attendees;

use Craft;
use craft\web\Controller;
use craft\helpers\DateTimeHelper;
use yii\web\Cookie;

/**
 * @author    meBooks
 * @package   EventHelper
 * @since     1.0.0
 */
class AttendeesController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['save-attendee', 'remove-attendee'];

    // Public Methods
    // =========================================================================

    /**
     * Create and prep an Attendee object to be sent to the Service. This
     * method also santizes user input as much as reasonably possible.
     *
     * @method actionSaveAttendee
     * @return void
     *
     * TODO : return the model with errors. (Currently non-functional.)
     */
    public function actionSaveAttendee()
    {
        return 'HERE';
        // $this->requirePostRequest();

        // foreach (craft()->request->getPost() as $key => $value) {
        //     // Cleanse the data as much as possible
        //     $encodedValue = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        //     $encodedValue = htmlspecialchars($value, ENT_QUOTES);
        //     $encodedValue = htmlentities($encodedValue);

        //     $data[substr($key, 9)] = $encodedValue;
        // }

        // $attendee = new EventHelper_AttendeeModel();
        // $attendee->userId = $data['userId'];
        // $attendee->name = $data['name'];
        // $attendee->email = $data['email'];
        // $attendee->eventId = $data['eventId'];
        // $attendee->seats = $data['seats'];

        // $eventsGlobals = craft()->globals->getSetByHandle('events');
        // // You need to declare a rules() method in your model for the
        // // validate method to work.
        // if ($attendee->validate()) {
        //     $settings = craft()->plugins->getPlugin('eventhelper')->getSettings();

        //     if ($settings->sendRSVPNotifications) {
        //         craft()->eventHelper_attendees->SendRSVPNotifications($attendee);
        //     }

        //     craft()->eventHelper_attendees->SaveAttendee($attendee);

        //     $notice = $eventsGlobals->rsvpSuccess ? $eventsGlobals->rsvpSuccess : 'Thanks for your RSVP!';

        //     craft()->userSession->setNotice("$notice<br /><br /><a href='/events/ical/{$data['eventId']}'>Add this event to your calendar.</a>");

        //     if ($data['redirect']) {
        //         craft()->request->redirect($data['redirect']);
        //     } else {
        //         $this->redirectToPostedUrl();
        //     }
        // }

        // craft()->userSession->setError($eventsGlobals->rsvpFailure ? $eventsGlobals->rsvpFailure : 'Something wasn\'t right about your reservation. Try submitting it again.');
        // craft()->urlManager->setRouteVariables(array(
        //     'attendee' => $attendee
        // ));

        // if ($data['redirect']) {
        //     craft()->request->redirect($data['redirect']);
        // } else {
        //     $this->redirectToPostedUrl();
        // }
    }

    /**
     * Delete an attendee from an event. This
     * method also santizes user input as much as reasonably possible.
     *
     * @method actionRemoveAttendee
     * @return void
     *
     * TODO : return the model with errors. (Currently non-functional.)
     */
    public function actionRemoveAttendee()
    {
        $this->requirePostRequest();

        foreach (craft()->request->getPost() as $key => $value) {
            // Cleanse the data as much as possible
            $encodedValue = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            $encodedValue = htmlspecialchars($value, ENT_QUOTES);
            $encodedValue = htmlentities($encodedValue);

            $data[substr($key, 9)] = $encodedValue;
        }

        $attendee = new EventHelper_AttendeeModel();
        $attendee->userId = $data['userId'];
        $attendee->eventId = $data['eventId'];

        $eventsGlobals = craft()->globals->getSetByHandle('events');

        if (craft()->eventHelper_attendees->RemoveAttendee($attendee)) {
            craft()->userSession->setNotice($eventsGlobals->rsvpRemovalSuccess ? $eventsGlobals->rsvpRemovalSuccess : 'Your reservation has been removed for this event.<br />We hope to see you at future events.');

            if ($data['redirect']) {
                craft()->request->redirect($data['redirect']);
            } else {
                $this->redirectToPostedUrl();
            }
        }

        craft()->userSession->setError($eventsGlobals->rsvpRemovalFailure ? $eventsGlobals->rsvpRemovalFailure : 'Something wasn\'t right about your removal request. Try submitting it again.');
        craft()->urlManager->setRouteVariables(array(
            'attendee' => $attendee
        ));

        if ($data['redirect']) {
            craft()->request->redirect($data['redirect']);
        } else {
            $this->redirectToPostedUrl();
        }
    }

    /**
     * Download export of attendees to upcoming events.
     *
     * @return string CSV
     */
    public function actionDownloadupcoming()
    {
        // Get data
        $results = EventHelper::$plugin->attendees->getUpcomingAttendeesForCsv();
        $data = EventHelper::$plugin->eventhelper->download($results);

        // Set a cookie to indicate that the export has finished.
        $cookie = new Cookie(['name' => 'eventhelperExportFinished']);
        $cookie->value = 'true';
        $cookie->expire = time() + 3600;
        $cookie->httpOnly = false;

        Craft::$app->getResponse()->getCookies()->add($cookie);

        $dateGenerated = DateTimeHelper::currentUTCDateTime();
        $dateGenerated = $dateGenerated->format('d-m-Y\TH:i:s');

        // Download the csv
        Craft::$app->getResponse()->sendContentAsFile($data, "event_helper_export_upcoming_{$dateGenerated}.csv", array(
          'forceDownload' => true,
          'mimeType' => 'text/csv'
        ));
    }

    /**
     * Download export of attendees to past events.
     *
     * @return string CSV
     */
    public function actionDownloadpast()
    {
        // Get data
        $results = EventHelper::$plugin->attendees->getPastAttendeesForCsv();
        $data = EventHelper::$plugin->eventhelper->download($results);

        // Set a cookie to indicate that the export has finished.
        $cookie = new Cookie(['name' => 'eventhelperExportFinished']);
        $cookie->value = 'true';
        $cookie->expire = time() + 3600;
        $cookie->httpOnly = false;

        Craft::$app->getResponse()->getCookies()->add($cookie);

        $dateGenerated = DateTimeHelper::currentUTCDateTime();
        $dateGenerated = $dateGenerated->format('d-m-Y\TH:i:s');

        // Download the csv
        Craft::$app->getResponse()->sendContentAsFile($data, "event_helper_export_past_{$dateGenerated}.csv", array(
          'forceDownload' => true,
          'mimeType' => 'text/csv'
        ));
    }
}
