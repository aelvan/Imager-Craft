<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\imager\transformers;

use Craft;

use craft\base\Component;
use craft\base\LocalVolumeInterface;
use craft\base\Volume;
use craft\elements\Asset;
use craft\volumes\Local;

use aelvan\imager\models\ConfigModel;
use aelvan\imager\models\ImgixSettings;
use aelvan\imager\models\ImgixTransformedImageModel;
use aelvan\imager\services\ImagerService;
use aelvan\imager\exceptions\ImagerException;

use Imgix\ShardStrategy;
use Imgix\UrlBuilder;

use yii\base\InvalidConfigException;

/**
 * ImgixTransformer
 *
 * @author    André Elvan
 * @package   Imager
 * @since     2.0.0
 */
class ImgixTransformer extends Component implements TransformerInterface
{
    public static $transformKeyTranslate = [
        'width' => 'w',
        'height' => 'h',
        'format' => 'fm',
        'bgColor' => 'bg',
    ];

    /**
     * ImgixTransformer constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * Main transform method
     *
     * @param Asset|string $image
     * @param array        $transforms
     *
     * @return array|null
     * @throws ImagerException
     */
    public function transform($image, $transforms)
    {
        $transformedImages = [];

        foreach ($transforms as $transform) {
            $transformedImages[] = $this->getTransformedImage($image, $transform);
        }

        return $transformedImages;
    }

    /**
     * Transform one image
     *
     * @param Asset|string $image
     * @param array        $transform
     *
     * @return ImgixTransformedImageModel
     *
     * @throws ImagerException
     */
    private function getTransformedImage($image, $transform): ImgixTransformedImageModel
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        $profile = $config->getSetting('imgixProfile', $transform);
        $imgixConfigArr = $config->getSetting('imgixConfig', $transform);

        if (!isset($imgixConfigArr[$profile])) {
            $msg = 'Imgix profile “'.$profile.'” does not exist.';
            Craft::error($msg, __METHOD__);
            throw new ImagerException($msg);
        }

        $imgixConfig = new ImgixSettings($imgixConfigArr[$profile]);

        $domains = $imgixConfig->domains;

        if (!\is_array($domains)) {
            $msg = Craft::t('imager', 'Imgix config setting “domains” does not appear to be correctly set up. It needs to be an array of strings representing your Imgix source`s domains.');
            Craft::error($msg, __METHOD__);
            throw new ImagerException($msg);
        }

        if (($imgixConfig->sourceIsWebProxy === true) && ($imgixConfig->signKey === '')) {
            $msg = Craft::t('imager', 'Your Imgix source is a web proxy according to config setting “sourceIsWebProxy”, but no sign key/security token has been given in imgix config setting “signKey”. You`ll find this in your Imgix source details page.');
            Craft::error($msg, __METHOD__);
            throw new ImagerException($msg);
        }

        try {
            $builder = new UrlBuilder($domains,
                $imgixConfig->useHttps,
                $imgixConfig->signKey,
                ($imgixConfig->shardStrategy === 'cycle' ? ShardStrategy::CYCLE : ShardStrategy::CRC),
                false);
        } catch (\InvalidArgumentException $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }

        $params = $this->createParams($transform, $image, $imgixConfig);

        if (\is_string($image)) { // if $image is a string, just pass it to builder, we have to assume the user knows what he's doing (sry) :)
            $url = $builder->createURL($image, $params);
        } else {
            if ($imgixConfig->sourceIsWebProxy === true) {
                $url = $builder->createURL($image->url, $params);
            } else {
                try {
                    /** @var LocalVolumeInterface|Volume|Local $volume */
                    $volume = $image->getVolume();
                } catch (InvalidConfigException $e) {
                    Craft::error($e->getMessage(), __METHOD__);
                    throw new ImagerException($e->getMessage(), $e->getCode(), $e);
                }

                if (($imgixConfig->useCloudSourcePath === true) && isset($volume->subfolder) && \get_class($volume) !== 'craft\volumes\Local') {
                    $path = implode('/', [$volume->subfolder, $image->getPath()]);
                } else {
                    $path = $image->getPath();
                }

                $url = $builder->createURL($this->getUrlEncodedPath($path), $params);
            }
        }

        return new ImgixTransformedImageModel($url, $image, $params, $imgixConfig);
    }

    /**
     * Create Imgix transform params
     *
     * @param array         $transform
     * @param Asset|string  $image
     * @param ImgixSettings $imgixConfig
     *
     * @return array
     */
    private function createParams($transform, $image, $imgixConfig): array
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        $r = [];

        // Merge in default values
        if (\is_array($imgixConfig->defaultParams)) {
            $transform = array_merge($imgixConfig->defaultParams, $transform);
        }

        // Directly translate some keys
        foreach (self::$transformKeyTranslate as $key => $val) {
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

                if ($image instanceof Asset) {
                    $ext = $image->getExtension();
                }

                if (\is_string($image)) {
                    $pathParts = pathinfo($image);
                    $ext = $pathParts['extension'];
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
                        $letterboxDef = $config->getSetting('letterbox', $transform);
                        $r['bg'] = $this->getLetterboxColor($letterboxDef);
                        unset($transform['letterbox']);
                        break;
                    default:
                        $r['fit'] = 'crop';
                        break;
                }

                unset($transform['mode']);
            } else {
                if (isset($r['w'], $r['h'])) {
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
            $position = $config->getSetting('position', $transform);
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
        if (isset($r['fit']) && !$config->getSetting('allowUpscale', $transform)) {
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
        foreach ($r as $key => $val) {
            if ($val === '') {
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
     * @return string
     */
    private function getLetterboxColor($letterboxDef): string
    {
        $color = $letterboxDef['color'];
        $opacity = $letterboxDef['opacity'];

        $color = str_replace('#', '', $color);

        if (\strlen($color) === 3) {
            $opacity = dechex($opacity * 15);

            return $opacity.$color;
        }

        if (\strlen($color) === 6) {
            $opacity = dechex($opacity * 255);
            $val = $opacity.$color;
            if (\strlen($val) === 7) {
                $val = '0'.$val;
            }

            return $val;
        }

        if (\strlen($color) === 4 || \strlen($color) === 8) { // assume color already is 4 or 8 digit rgba. 
            return $color;
        }

        return '0fff';
    }

    /**
     * Gets the quality setting based on the extension.
     *
     * @param string $ext
     * @param array|null   $transform
     *
     * @return string
     */
    private function getQualityFromExtension($ext, $transform = null): string
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        switch ($ext) {
            case 'png':
                $pngCompression = $config->getSetting('pngCompressionLevel', $transform);

                return max(100 - ($pngCompression * 10), 1);

            case 'webp':
                return $config->getSetting('webpQuality', $transform);
        }

        return $config->getSetting('jpegQuality', $transform);
    }

    /**
     * URL encode the asset path properly
     *
     * @param string $path
     *
     * @return string
     */
    private function getUrlEncodedPath($path): string
    {
        $path = str_replace('%2F', '/', urlencode($path));

        return $path;
    }
}
