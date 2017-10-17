<?php

namespace Craft;

/**
 * Imager by André Elvan
 *
 * @author      André Elvan <http://vaersaagod.no>
 * @package     Imager
 * @copyright   Copyright (c) 2016, André Elvan
 * @license     http://opensource.org/licenses/mit-license.php MIT License
 * @link        https://github.com/aelvan/Imager-Craft
 */

class ImagerPlugin extends BasePlugin
{
    protected $_version = '1.6.4',
      $_schemaVersion = '1.0.0',
      $_name = 'Imager',
      $_url = 'https://github.com/aelvan/Imager-Craft',
      $_releaseFeedUrl = 'https://raw.githubusercontent.com/aelvan/Imager-Craft/master/releases.json',
      $_documentationUrl = 'https://github.com/aelvan/Imager-Craft/blob/master/README.md',
      $_description = 'Image transforms gone wild.',
      $_developer = 'André Elvan',
      $_developerUrl = 'http://vaersaagod.no/',
      $_minVersion = '2.4';

    public function getName()
    {
        return Craft::t($this->_name);
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function getDeveloper()
    {
        return $this->_developer;
    }

    public function getDeveloperUrl()
    {
        return $this->_developerUrl;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function getDocumentationUrl()
    {
        return $this->_documentationUrl;
    }

    public function getSchemaVersion()
    {
        return $this->_schemaVersion;
    }

    public function getReleaseFeedUrl()
    {
        return $this->_releaseFeedUrl;
    }

    public function getCraftRequiredVersion()
    {
        return $this->_minVersion;
    }

    /**
     * Init function
     */
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
