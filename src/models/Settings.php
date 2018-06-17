<?php
namespace nzmebooks\eventhelper\models;
use craft\base\Model;
/**
 * @property boolean $enabled
 * @property boolean $sendRSVPNotifications
 * @property mixed   $rsvpNotificationBody
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================
    public $enabled = true;
    public $sendRSVPNotifications = false;
    public $rsvpNotificationBody = '
<h2 style="margin:0; mso-line-height-rule:exactly;">Thanks for showing interest in a China Capable Public Sector event</h2>&#13;
<p style="margin:0;">The details of the event you will be attending are as follows:</p>&#13;
<p style="margin:0;">&#13;
    <b>{{ title }}</b>&#13;
    <br /> Date: {{ dates }}&#13;
    <br /> Location: {{ location }}&#13;
</p>&#13;
<p style="margin:0;">{{ instructions }}</p>&#13;
<p style="margin:0;"><a href="{{ url }}">More information</a></p>&#13;
';
}
