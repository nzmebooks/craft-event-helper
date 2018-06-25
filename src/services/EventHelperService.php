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

use Craft;
use craft\base\Component;
use craft\helpers\App;
use craft\helpers\StringHelper;

/**
 * Class EventHelperService
 *
 * @author    meBooks
 * @package   EventHelper
 * @since     1.0.0
 */
class EventHelperService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Download the export csv.
     *
     * @param array $settings
     *
     * @return string
     *
     * @throws Exception
     */
    public function download($data)
    {
        // Get max power
        App::maxPowerCaptain();

        // Get delimiter
        $delimiter = ',';

        // Open output buffer
        ob_start();

        // Write to output stream
        $export = fopen('php://output', 'w');

        // If there is data, process
        if (is_array($data) && count($data)) {

            // Loop through data
             foreach ($data as $fields) {

                // Gather row data
                $rows = array();

                // Loop through the fields
                foreach ($fields as $field) {

                    // Encode and add to rows
                    $rows[] = StringHelper::convertToUTF8($field);
                }

                // Add rows to export
                fputcsv($export, $rows, $delimiter);
            }
        }

        // Close buffer and return data
        fclose($export);
        $data = ob_get_clean();

        // Use windows friendly newlines
        $data = str_replace("\n", "\r\n", $data);

        // Return the data to controller
        return $data;
    }
}
