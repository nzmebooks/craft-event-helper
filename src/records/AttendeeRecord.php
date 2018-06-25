<?php
/**
 * event-helper plugin for Craft CMS 3.x
 *
 * Event Helper is a simple Craft CMS plugin that gives you the ability to track event attendance.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\eventhelper\records;

use nzmebooks\eventhelper\EventHelper;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    meBooks
 * @package   Eventhelper
 * @since     1.0.0
 */
class AttendeeRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%eventhelperattendees}}';
    }
}
