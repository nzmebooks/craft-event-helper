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
use nzmebooks\eventhelper\models\AttendeeModel;

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
        $this->requirePostRequest();

        $attendee = new AttendeeModel();

        $attendee->userId = $this->getSanitisedBodyParam('userId');
        $attendee->name = $this->getSanitisedBodyParam('name');
        $attendee->email = $this->getSanitisedBodyParam('email');
        $attendee->eventId = $this->getSanitisedBodyParam('eventId');
        $attendee->seats = $this->getSanitisedBodyParam('seats');
        $redirect = $this->getSanitisedBodyParam('redirect');

        $eventsGlobals = Craft::$app->globals->getSetByHandle('events');

        // You need to declare a rules() method in your model for the
        // validate method to work.

        if (!$attendee->validate()) {
            $message = $eventsGlobals->rsvpFailure
                ? $eventsGlobals->rsvpFailure
                : 'Something wasn\'t right about your reservation. Try submitting it again.';

            Craft::$app->getSession()->setError($message);

            Craft::$app->getUrlManager()->setRouteVariables([
                'attendee' => $attendee
            ]);

            if ($redirect) {
                return $this->redirect($redirect);
            } else {
                return $this->redirectToPostedUrl();
            }
        }

        $settings = EventHelper::$plugin->getSettings();

        if ($settings->sendRSVPNotifications) {
            EventHelper::$plugin->attendees->SendRSVPNotifications($attendee);
        }

        EventHelper::$plugin->attendees->SaveAttendee($attendee);

        $notice = $eventsGlobals->rsvpSuccess
          ? $eventsGlobals->rsvpSuccess
          : 'Thanks for your RSVP!';

        $message = "$notice<br /><br /><a href='/events/ical/{$attendee->eventId}'>Add this event to your calendar.</a>";
        Craft::$app->getSession()->setNotice($message);

        if ($redirect) {
            return $this->redirect($redirect);
        } else {
            return $this->redirectToPostedUrl();
        }
    }

    /**
     * Delete an attendee from an event. This
     * method also santizes user input as much as reasonably possible.
     *
     * @method actionRemoveAttendee
     * @return void
     */
    public function actionRemoveAttendee()
    {
        $this->requirePostRequest();

        $attendee = new AttendeeModel();
        $attendee->eventId = $this->getSanitisedBodyParam('eventId');
        $attendee->userId = $this->getSanitisedBodyParam('userId');
        $redirect = $this->getSanitisedBodyParam('redirect');

        $eventsGlobals = Craft::$app->globals->getSetByHandle('events');

        if (!EventHelper::$plugin->attendees->RemoveAttendee($attendee)) {
            $message = $eventsGlobals->rsvpRemovalFailure
                ? $eventsGlobals->rsvpRemovalFailure
                : 'Something wasn\'t right about your removal request. Try submitting it again.';

            Craft::$app->getSession()->setError($message);
            Craft::$app->getUrlManager()->setRouteVariables([
                'attendee' => $attendee
            ]);

            if ($redirect) {
                return $this->redirect($redirect);
            } else {
                return $this->redirectToPostedUrl();
            }
        }

        $message = $eventsGlobals->rsvpRemovalSuccess
            ? $eventsGlobals->rsvpRemovalSuccess
            : 'Your reservation has been removed for this event.<br />We hope to see you at future events.';
        Craft::$app->getSession()->setNotice($message);

        if ($redirect) {
            return $this->redirect($redirect);
        } else {
            return $this->redirectToPostedUrl();
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

    // Private Methods
    // =========================================================================

    /**
     * Return a POST request param
     *
     * @method getSanitisedBodyParam
     * @return string
     */
    private function getSanitisedBodyParam($name)
    {
        $value = Craft::$app->getRequest()->getBodyParam($name);
        $valueEncoded = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $valueEncoded = htmlspecialchars($value, ENT_QUOTES);
        $valueEncoded = htmlentities($valueEncoded);

        return $valueEncoded;
    }

}
