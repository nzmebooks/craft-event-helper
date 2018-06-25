<?php
/**
 * event-helper plugin for Craft CMS 3.x
 *
 * Event Helper is a simple Craft CMS plugin that gives you the ability to track event attendance.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

namespace nzmebooks\eventhelper\models;

use nzmebooks\eventhelper\EventHelper;

use Craft;
use craft\base\Model;

/**
 * Class AttendeeModel
 *
 * @author    meBooks
 * @package   Eventhelper
 * @since     1.0.0
 */
class AttendeeModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var int
     */
    public $userId;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $email;

    /**
     * @var int
     */
    public $eventId;

    /**
     * @var int
     */
    public $seats;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['userId', 'required'],
            ['name', 'required'],
            ['email', 'required'],
            ['eventId', 'required'],
            ['seats', 'required'],
        ];
    }
}
