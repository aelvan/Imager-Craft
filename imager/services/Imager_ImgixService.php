<?php

namespace Craft;

use Imgix\UrlBuilder;
use Imgix\ShardStrategy;

/**
 * Imager by André Elvan
 *
 * @author      André Elvan <http://vaersaagod.no>
 * @package     Imager
 * @copyright   Copyright (c) 2016, André Elvan
 * @license     http://opensource.org/licenses/mit-license.php MIT License
 * @link        https://github.com/aelvan/Imager-Craft
 */
class Imager_ImgixService extends BaseApplicationComponent
{
    public static $transformKeyTranslate = [
        'width' => 'w',
        'height' => 'h',
        'format' => 'fm',
        'bgColor' => 'bg',
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Gets transformed Imgix image
     *
     * @param $image
     * @param $transform
     *
     * @return Imager_ImgixModel
     * @throws Exception
     */
    public function getTransformedImage($image, $transform)
    {
        $transform = craft()->imager->normalizeTransform($transform, null);
        $domains = craft()->imager->getSetting('imgixDomains', $transform);

        if (!is_array($domains)) {
            $msg = Craft::t('Config setting “imgixDomains” does not appear to be correctly set up. It needs to be an array of strings representing your Imgix source`s domains.');
            throw new Exception($msg);
        }

        if ((craft()->imager->getSetting('imgixSourceIsWebProxy', $transform) === true) && (craft()->imager->getSetting('imgixSignKey', $transform) === '')) {
            $msg = Craft::t('Your Imgix source is a web proxy according to config setting “imgixSourceIsWebProxy”, but no sign key/security token has been given in config setting “imgixSignKey”. You`ll find this in your Imgix source details page.');
            throw new Exception($msg);
        }

        $builder = new UrlBuilder($domains);
        $builder->setUseHttps(craft()->imager->getSetting('imgixUseHttps', $transform));

        if (craft()->imager->getSetting('imgixSignKey', $transform) !== '') {
            $builder->setSignKey(craft()->imager->getSetting('imgixSignKey', $transform));
        }

        if (count($domains) > 1) {
            $builder->setShardStrategy(craft()->imager->getSetting('imgixShardStrategy', $transform) === 'cycle' ? ShardStrategy::CYCLE : ShardStrategy::CRC);
        }

        $params = $this->createParams($transform, $image);

        if (is_string($image)) { // if $image is a string, just pass it to builder, we have to assume the user knows what he's doing (sry) :)
            $url = $builder->createURL($image, $params);
        } else {
            if (craft()->imager->getSetting('imgixSourceIsWebProxy', $transform) === true) {
                $url = $builder->createURL($image->url, $params);
            } else {
                if ((craft()->imager->getSetting('imgixUseCloudSourcePath', $transform) === true) && in_array($image->getSource()->type, array("S3", "Rackspace", "GoogleCloud"), true)) {
                    $path = implode("/", [$image->getSource()->settings['subfolder'], $image->getPath()]);
                } else {
                    $path = $image->path;
                }
                
                $url = $builder->createURL($this->getUrlEncodedPath($path), $params);
            }
        }

        return new Imager_ImgixModel($url, $image, $params);
    }

    /**
     * Create Imgix transform params
     *
     * @param $transform
     * @param $image
     *
     * @return array
     */
    private function createParams($transform, $image)
    {
        $r = [];

        // Merge in default values
        if (is_array(craft()->imager->getSetting('imgixDefaultParams', $transform))) {
            $transform = array_merge(craft()->imager->getSetting('imgixDefaultParams', $transform), $transform);
        }
        
        // Directly translate some keys
        foreach (Imager_ImgixService::$transformKeyTranslate as $key => $val) {
            if (isset($transform[$key])) {
                $r[$val] = $transform[$key];
                unset($transform[$key]);
            }
        }

        // Set quality 
        if (!isset($transform['q'])) {
            if (isset($r['fm'])) {
                $r['q'] = $this->getQualityFromExtension($r['fm'], $transform);
            } else {
                $ext = null;

                if ($image instanceof \Craft\AssetFileModel) {
                    $ext = $image->getExtension();
                }

                if (is_string($image)) {
                    $ext = IOHelper::getExtension($image);
                }

                $r['q'] = $this->getQualityFromExtension($ext, $transform);
            }
        }

        unset($transform['jpegQuality'], $transform['pngCompressionLevel'], $transform['webpQuality']);

        // Deal with resize mode, called fit in Imgix
        if (!isset($transform['fit'])) {
            if (isset($transform['mode'])) {
                $mode = $transform['mode'];

                switch ($mode) {
                    case 'crop':
                        $r['fit'] = 'crop';
                        break;
                    case 'fit':
                        $r['fit'] = 'clip';
                        break;
                    case 'stretch':
                        $r['fit'] = 'scale';
                        break;
                    case 'croponly':
                        // todo : Not really supported, need to figure out if there's a workaround 
                        break;
                    case 'letterbox':
                        $r['fit'] = 'fill';
                        $letterboxDef = craft()->imager->getSetting('letterbox', $transform);
                        $r['bg'] = $this->getLetterboxColor($letterboxDef);
                        unset($transform['letterbox']);
                        break;
                    default:
                        $r['fit'] = 'crop';
                        break;
                }

                unset($transform['mode']);
            } else {
                if (isset($r['w']) && isset($r['h'])) {
                    $r['fit'] = 'crop';
                } else {
                    $r['fit'] = 'clip';
                }
            }
        } else {
            $r['fit'] = $transform['fit'];
            unset($transform['fit']);
        }

        // If fit is crop, and crop isn't specified, use position as focal point.
        if ($r['fit'] === 'crop' && !isset($transform['crop'])) {
            $position = craft()->imager->getSetting('position', $transform);
            list($left, $top) = explode(' ', $position);
            $r['crop'] = 'focalpoint';
            $r['fp-x'] = ((float)$left) / 100;
            $r['fp-y'] = ((float)$top) / 100;

            if (isset($transform['cropZoom'])) {
                $r['fp-z'] = $transform['cropZoom'];
                unset($transform['cropZoom']);
            }

            unset($transform['position']);
        }
        
        // Add any explicitly set Imgix params
        if (isset($transform['imgixParams'])) {
            foreach ($transform['imgixParams'] as $key => $val) {
                $r[$key] = $val;
            }

            unset($transform['imgixParams']);
        }

        // Assume that the reset of the values left in the transform object is Imgix specific 
        foreach ($transform as $key => $val) {
            $r[$key] = $val;
        }
        
        // If allowUpscale is disabled, use max-w/-h instead of w/h
        if (!craft()->imager->getSetting('allowUpscale', $transform) && isset($r['fit'])) {
            if ($r['fit'] === 'crop') {
                $r['fit'] = 'min';
            }

            if ($r['fit'] === 'clip') {
                $r['fit'] = 'max';
            }
        }
        
        // Unset stuff that's not supported by Imgix and has not yet been dealt with
        unset(
            $r['effects'], 
            $r['preeffects'],
            $r['allowUpscale'],
            $r['cacheEnabled'],
            $r['cacheDuration'],
            $r['interlace'],
            $r['resizeFilter'],
            $r['smartResizeEnabled'],
            $r['removeMetadata'],
            $r['hashFilename'],
            $r['hashRemoteUrl']
        );
        
        // Remove any empty values in return array, since these will result in 
        // an empty query string value that will give us trouble with Facebook (!).
        foreach ($r as $key=>$val) {
            if ($val==='') {
                unset($r[$key]);
            }
        }
        
        
        return $r;
    }

    /**
     * Gets letterbox params string
     *
     * @param $letterboxDef
     *
     * @return mixed|string
     */
    private function getLetterboxColor($letterboxDef)
    {
        $color = $letterboxDef['color'];
        $opacity = $letterboxDef['opacity'];

        $color = str_replace('#', '', $color);

        if (strlen($color) === 3) {
            $opacity = dechex($opacity * 15);

            return $opacity.$color;
        }

        if (strlen($color) === 6) {
            $opacity = dechex($opacity * 255);
            $val = $opacity.$color;
            if (strlen($val) === 7) {
                $val = '0'.$val;
            }

            return $val;
        }

        if (strlen($color) === 4 || strlen($color) === 8) { // assume color already is 4 or 8 digit rgba. 
            return $color;
        }

        return '0fff';
    }

    /**
     * Gets the quality setting based on the extension.
     *
     * @param      $ext
     * @param null $transform
     *
     * @return string
     */
    private function getQualityFromExtension($ext, $transform = null)
    {
        switch($ext) {
            case 'png':
                $pngCompression = craft()->imager->getSetting('pngCompressionLevel', $transform);
                $pngQuality = max(100 - ($pngCompression * 10), 1);
                return $pngQuality;
                
            case 'webp':
                return craft()->imager->getSetting('webpQuality', $transform);
        }
        
        return craft()->imager->getSetting('jpegQuality', $transform);
    }

    /**
     * URL encode the asset path properly
     * 
     * @param $path
     *
     * @return string
     */
    private function getUrlEncodedPath($path) {
        $path = str_replace('%2F', '/', urlencode($path));
        return $path;
    }
}
