<?php
namespace nzmebooks\eventhelper\bundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class EventHelperBundle extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@nzmebooks/eventhelper/resources';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/event-helper.js',
        ];

        $this->css = [
            'css/event-helper.css',
        ];

        parent::init();
    }
}
