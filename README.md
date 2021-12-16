# craft-event-helper plugin for Craft CMS 3.x

Event Helper is a simple Craft CMS plugin, based on the [Mighty Events] plugin(https://github.com/taylordaughtry/Craft-MightyEvents), that gives you the ability to track event attendance.

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

Note that we assume that the [craft-plain-ics plugin](https://github.com/plainlanguage/craft-plain-ics) is installed.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require nzmebooks/craft-event-helper

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for craft-event-helper.

## Using craft-event-helper

### Event Signup Forms

Here's the basic structure of an event form:

````
{% if craft.session.isLoggedIn %}
    {% if craft.eventHelper.isAttended(entry.id, currentUser.id) %}
        <form method="post" action="" accept-charset="UTF-8">
            {{ csrfInput() }}
            <input type="hidden" name="action" value="event-helper/attendees/remove-attendee">
            <input type="hidden" name="eventId" value="{{ entry.id }}">
            <input type="hidden" name="userId" value="{{ currentUser.id }}">
            <p>You are going to this event.</p>
            <input type="submit" class="button" value="Change RSVP">
        </form>
    {% else %}
        <form method="post" action="" accept-charset="UTF-8">
            {{ csrfInput() }}
            <input type="hidden" name="action" value="event-helper/attendees/save-attendee">
            <input type="hidden" name="eventId" value="{{ entry.id }}">
            <input type="hidden" name="userId" value="{{ currentUser.id }}">
            <input type="hidden" name="name" value="{{ currentUser.fullName }}">
            <input type="hidden" name="email" value="{{ currentUser.email }}">
            <input type="hidden" name="seats" value="1">
            <input type="submit" class="button" value="RSVP">
        </form>
    {% endif %}
{% else %}
    <a class="button" href="/login">Login to RSVP</a>
{% endif %}
````

Date is submitted via `POST` into the module, where it's validated and saved to
the database.

Notice that the `action` attribute on the form is blank. This submits it to the
same page by default. A `Flash` value is passed as a `Notice` on success, or an
`Error` when something's not right. (Usually bad data.) The `action` hidden
input actually sends the data to the Event Helper controller, which processes
the data.

When a user RSVP's or removes their RSVP, we set a flash notice accordingly. We provide fallback values for these flash messages, but they can be overridden via the following globals:

* $eventsGlobals->rsvpSuccess
* $eventsGlobals->rsvpFailure
* $eventsGlobals->rsvpRemovalSuccess
* $eventsGlobals->rsvpRemovalFailure

### Database Structure

We currently presume the existence of a section with a handle of `events`, which has entries with fields that have the following handles:

* title
* dateStart
* dateEnd

The `eventId` submitted from the frontend from is expected to map to a `content.id` field (i.e. the record representing the event):

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

Brought to you by [meBooks](https://mebooks.co.nz)
