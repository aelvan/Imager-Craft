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
        
        craft()->on('assets.onReplaceFile', function (Event $event) {
            craft()->imager->removeTransformsForAsset($event->params['asset']);
        });
    }
    
    /**
     * Adds Imager paths to the list of things the Clear Caches tool can delete.
     *
     * @return array
     */
    public function registerCachePaths()
    {
        return array(
            craft()->imager->getSetting('imagerSystemPath') => Craft::t('Imager image transform cache'),
            craft()->path->getRuntimePath() . 'imager/' => Craft::t('Imager remote images cache'),
        );
    }

    /**
     * Adds asset action for clearing asset transforms
     * 
     * @return array
     */
    public function addAssetActions()
    {
        $actions = array();

        $purgeAction = craft()->elements->getAction('Imager_ClearTransforms');

        $purgeAction->setParams(array(
          'label' => Craft::t('Clear Imager transforms'),
        ));

        $actions[] = $purgeAction;

        return $actions;
    }

    
    
}