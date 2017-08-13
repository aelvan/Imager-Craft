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

class Imager_ConfigModel extends BaseModel
{
    var $configOverrideString = '';

    /**
     * Constructor
     * 
     * @param null $overrides
     */
    public function __construct($overrides = null)
    {
        foreach ($this->attributeNames() as $attr) {
            if ($overrides !== null && isset($overrides[$attr])) {
                $this[$attr] = $overrides[$attr];
                $this->_addToOverrideFilestring($attr, $overrides[$attr]);
            } else {
                $this[$attr] = craft()->config->get($attr, 'imager');
            }
        }
        
        if (isset(ImagerService::$craftPositionTranslate[(string)$this->position])) {
            $this->position = ImagerService::$craftPositionTranslate[(string)$this->position];
        } 
            
        $this->position = str_replace('%', '', $this->position);
    }

    protected function defineAttributes()
    {
        return array(
          'imagerSystemPath' => array(AttributeType::String),
          'imagerUrl' => array(AttributeType::String),
          'jpegQuality' => array(AttributeType::Number),
          'pngCompressionLevel' => array(AttributeType::Number),
          'webpQuality' => array(AttributeType::Number),
          'webpImagickOptions' => array(AttributeType::Mixed),
          'useCwebp' => array(AttributeType::Bool),
          'cwebpPath' => array(AttributeType::String),
          'cwebpOptions' => array(AttributeType::String),
          'interlace' => array(AttributeType::Mixed),
          'allowUpscale' => array(AttributeType::Bool),
          'resizeFilter' => array(AttributeType::String),
          'smartResizeEnabled' => array(AttributeType::Bool),
          'bgColor' => array(AttributeType::String),
          'position' => array(AttributeType::String),
          'letterbox' => array(AttributeType::Mixed),
          'hashFilename' => array(AttributeType::Bool),
          'hashPath' => array(AttributeType::Bool),
          'hashRemoteUrl' => array(AttributeType::Bool),
          'useRemoteUrlQueryString' => array(AttributeType::Bool),
          'cacheEnabled' => array(AttributeType::Bool),
          'cacheDuration' => array(AttributeType::Number),
          'cacheDurationRemoteFiles' => array(AttributeType::Number),
          'instanceReuseEnabled' => array(AttributeType::Bool),
          'noop' => array(AttributeType::Bool),
          'suppressExceptions' => array(AttributeType::Bool),
          'convertToRGB' => array(AttributeType::Bool),
          'fillTransforms' => array(AttributeType::Bool),
          'fillAttribute' => array(AttributeType::String),
          'fillInterval' => array(AttributeType::Number),
          'jpegoptimEnabled' => array(AttributeType::Bool),
          'jpegoptimPath' => array(AttributeType::String),
          'jpegoptimOptionString' => array(AttributeType::String),
          'jpegtranEnabled' => array(AttributeType::Bool),
          'jpegtranPath' => array(AttributeType::String),
          'jpegtranOptionString' => array(AttributeType::String),
          'mozjpegEnabled' => array(AttributeType::Bool),
          'mozjpegPath' => array(AttributeType::String),
          'mozjpegOptionString' => array(AttributeType::String),
          'optipngEnabled' => array(AttributeType::Bool),
          'optipngPath' => array(AttributeType::String),
          'optipngOptionString' => array(AttributeType::String),
          'pngquantEnabled' => array(AttributeType::Bool),
          'pngquantPath' => array(AttributeType::String),
          'pngquantOptionString' => array(AttributeType::String),
          'gifsicleEnabled' => array(AttributeType::Bool),
          'gifsiclePath' => array(AttributeType::String),
          'gifsicleOptionString' => array(AttributeType::String),
          'tinyPngEnabled' => array(AttributeType::Bool),
          'tinyPngApiKey' => array(AttributeType::String),
          'optimizeType' => array(AttributeType::String),
          'skipExecutableExistCheck' => array(AttributeType::Bool),
          'logOptimizations' => array(AttributeType::Bool),
          'imgixEnabled' => array(AttributeType::Bool),
          'imgixDomains' => array(AttributeType::Mixed),
          'imgixUseHttps' => array(AttributeType::Bool),
          'imgixSignKey' => array(AttributeType::String),
          'imgixSourceIsWebProxy' => array(AttributeType::Bool),
          'imgixUseCloudSourcePath' => array(AttributeType::Bool),
          'imgixShardStrategy' => array(AttributeType::String),
          'imgixGetExternalImageDimensions' => array(AttributeType::Bool),
          'imgixDefaultParams' => array(AttributeType::Mixed),
          'awsEnabled' => array(AttributeType::Bool),
          'awsAccessKey' => array(AttributeType::String),
          'awsSecretAccessKey' => array(AttributeType::String),
          'awsBucket' => array(AttributeType::String),
          'awsFolder' => array(AttributeType::String),
          'awsCacheDuration' => array(AttributeType::Number),
          'awsCacheDurationNonOptimized' => array(AttributeType::Number),
          'awsRequestHeaders' => array(AttributeType::Mixed),
          'awsStorageType' => array(AttributeType::String),
          'gcsEnabled' => array(AttributeType::Bool),
          'gcsAccessKey' => array(AttributeType::String),
          'gcsSecretAccessKey' => array(AttributeType::String),
          'gcsBucket' => array(AttributeType::String),
          'gcsFolder' => array(AttributeType::String),
          'gcsCacheDuration' => array(AttributeType::Number),
          'gcsCacheDurationNonOptimized' => array(AttributeType::Number),
          'cloudfrontInvalidateEnabled' => array(AttributeType::String),
          'cloudfrontDistributionId' => array(AttributeType::String),
          'removeMetadata' => array(AttributeType::Bool),
          'curlOptions' => array(AttributeType::Mixed),
          'runTasksImmediatelyOnAjaxRequests' => array(AttributeType::Bool),
          'clearKey' => array(AttributeType::String),
        );
    }

    public function getSetting($name, $transform = null)
    {
        if (isset($transform[$name])) {
            return $transform[$name];
        }
        return $this[$name];
    }

    public function getConfigOverrideString()
    {
        return $this->configOverrideString;
    }

    /**
     * Creates additional file string based on config overrides that is appended to filename
     *
     * @param $transform
     * @return string
     */
    private function _addToOverrideFilestring($k, $v)
    {
        $r = (isset(ImagerService::$transformKeyTranslate[$k]) ? ImagerService::$transformKeyTranslate[$k] : $k) . (is_array($v) ? md5(implode('-',$v)) : $v);
        $this->configOverrideString .= '_' . str_replace('%', '', str_replace(array(' ', '.'), '-', $r));
    }
}
