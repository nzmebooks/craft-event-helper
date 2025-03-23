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
use craft\elements\Entry;
use nystudio107\cookies\Cookies;

/**
 * Class AttendeesController
 *
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
    protected array|int|bool $allowAnonymous = ['remove-attendee'];

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

        if (!$attendee->validate()) {
            $message = $eventsGlobals->rsvpFailure
                ? $eventsGlobals->rsvpFailure
                : 'Something wasn\'t right about your reservation. Try submitting it again.';

            Craft::$app->getSession()->setError($message);

            Craft::$app->getUrlManager()->setRouteParams([
                'attendee' => $attendee
            ]);

            return $redirect
                ? $this->redirect($redirect)
                : $this->redirectToPostedUrl();
        }

        // Check that the user hasn't already RSVP'd
        $isAttended = EventHelper::$plugin->attendees->IsAttended($attendee->eventId, $attendee->userId);

        if (!$isAttended) {
            $settings = EventHelper::$plugin->getSettings();

            if ($settings->sendRSVPNotifications) {
                EventHelper::$plugin->attendees->SendRSVPNotifications($attendee);
            }

            EventHelper::$plugin->attendees->SaveAttendee($attendee);
        }

        $notice = $eventsGlobals->rsvpSuccess
            ? $eventsGlobals->rsvpSuccess
            : 'Thanks for your RSVP!';

        $event = Entry::find()
            ->filterWhere(['entries.id' => $attendee->eventId])
            ->one();

        if ($event->furtherRegistrationUrl ?? false) {
            $notice = $notice . "<br /><br /><a target='_blank' href='{$event->furtherRegistrationUrl}'>Please visit this link to complete registration for this event.</a>";
        }
        $message = "$notice<br /><br /><a href='/events/ical/{$attendee->eventId}'>Add this event to your calendar.</a>";
        Craft::$app->getSession()->setNotice($message);

        return $redirect
            ? $this->redirect($redirect)
            : $this->redirectToPostedUrl();
    }

    /**
     * Delete an attendee from an event.
     * This method also santizes user input as much as reasonably possible.
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

        if (!$attendee->userId) {
            // Check whether the user is already logged in
            $attendee->userId = Craft::$app->getUser()->id;
        }

        $cookieName = 'user-registed-for-event-' . $attendee->eventId;
        if (!$attendee->userId) {
            // Check whether there is a cookie set for the eventId  -- the user may be logged out
            $attendee->userId = Cookies::$plugin->cookies->get($cookieName);
        }

        $eventsGlobals = Craft::$app->globals->getSetByHandle('events');

        if (!EventHelper::$plugin->attendees->RemoveAttendee($attendee)) {
            $message = $eventsGlobals->rsvpRemovalFailure
                ? $eventsGlobals->rsvpRemovalFailure
                : 'Something wasn\'t right about your removal request. Try submitting it again.';

            Craft::$app->getSession()->setError($message);
            Craft::$app->getUrlManager()->setRouteParams([
                'attendee' => $attendee
            ]);

            return $this->redirectToPostedUrl();
        }

        // Remove the associated cookie
        Cookies::$plugin->cookies->set($cookieName);

        $message = $eventsGlobals->rsvpRemovalSuccess
            ? $eventsGlobals->rsvpRemovalSuccess
            : 'Your reservation has been removed for this event.<br />We hope to see you at future events.';
        Craft::$app->getSession()->setNotice($message);

        return $this->redirectToPostedUrl();
    }

    /**
     * Download export of attendees to upcoming events.
     *
     * @return string CSV
     */
    public function actionDownloadUpcoming()
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

        Craft::$app->end();
    }

    /**
     * Download export of attendees to past events.
     *
     * @return string CSV
     */
    public function actionDownloadPast()
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

        Craft::$app->end();
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

        // Stop "Ella.O'Neill@mfat.govt.nz" ending up as "Ella.O&amp;#039;Neill@mfat.govt.nz";
        // $valueEncoded = htmlspecialchars($value, ENT_QUOTES);
        // $valueEncoded = htmlentities($valueEncoded);

        return $valueEncoded;
    }

}
