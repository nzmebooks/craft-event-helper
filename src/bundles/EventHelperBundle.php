<?php

/**
 * event-helper plugin for Craft CMS 3.x
 *
 * Event Helper is a simple Craft CMS plugin that gives you the ability to track event attendance.
 *
 * @link      https://mebooks.co.nz
 * @copyright Copyright (c) 2018 meBooks
 */

 namespace nzmebooks\eventhelper\bundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Class EventHelperBundle
 *
 * @author    meBooks
 * @package   EventHelper
 * @since     1.0.0
 */
class EventHelperBundle extends AssetBundle
{
    public function init()
    {
        // Define the path that your publishable resources live
        $this->sourcePath = '@nzmebooks/eventhelper/resources';

        // Define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // Define the relative path to CSS/JS files that should be registered
        // with the page when this asset bundle is registered
        $this->js = [
            'js/event-helper.js',
        ];

        $this->css = [
            'css/event-helper.css',
        ];

        parent::init();
    }
}
