<?php

/**
 * It's free. And if most of your software is open source code that others have created, yours should be too.
 */

namespace Craft;

class ImagerPlugin extends BasePlugin
{
    public function getName()
    {
        return Craft::t('Imager');
    }

    public function getVersion()
    {
        return '0.9';
    }

    public function getDeveloper()
    {
        return 'André Elvan';
    }

    public function getDeveloperUrl()
    {
        return 'http://vaersaagod.no';
    }

    public function init()
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }
}