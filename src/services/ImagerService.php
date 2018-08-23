<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\imager\services;

use aelvan\imager\exceptions\ImagerException;
use Craft;

use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use Imagine\Image\ImageInterface;

use yii\base\ErrorException;
use yii\base\InvalidParamException;

use aelvan\imager\Imager as Plugin;
use aelvan\imager\helpers\ImagerHelpers;
use aelvan\imager\models\LocalSourceImageModel;
use aelvan\imager\models\LocalTargetImageModel;
use aelvan\imager\transformers\TransformerInterface;
use aelvan\imager\models\ConfigModel;
use aelvan\imager\transformers\CraftTransformer;
use aelvan\imager\transformers\ImgixTransformer;

/**
 * ImagerService Service
 *
 * @author    André Elvan
 * @package   Imager
 * @since     2.0.0
 */
class ImagerService extends Component
{
    /**
     * @var string
     */
    public static $imageDriver = 'gd';

    /**
     * @var null|ConfigModel
     */
    public static $transformConfig = null;

    /**
     * @var array
     */
    public static $transformers = [
        'craft' => CraftTransformer::class,
        'imgix' => ImgixTransformer::class,
    ];

    /**
     * @var array
     */
    public static $effects = [];

    /**
     * @var array
     */
    public static $optimizers = [];

    /**
     * @var array
     */
    public static $storage = [];

    /**
     * @var array
     */
    public static $remoteImageSessionCache = [];

    /**
     * Translate dictionary for translating transform keys into filename markers
     *
     * @var array
     */
    public static $transformKeyTranslate = [
        'width' => 'W',
        'height' => 'H',
        'mode' => 'M',
        'position' => 'P',
        'format' => 'F',
        'bgColor' => 'BC',
        'cropZoom' => 'CZ',
        'effects' => 'E',
        'preEffects' => 'PE',
        'resizeFilter' => 'RF',
        'allowUpscale' => 'upscale',
        'pngCompressionLevel' => 'PNGC',
        'jpegQuality' => 'Q',
        'webpQuality' => 'WQ',
        'webpImagickOptions' => 'WIO',
        'interlace' => 'I',
        'instanceReuseEnabled' => 'REUSE',
        'watermark' => 'WM',
        'letterbox' => 'LB',
        'frames' => 'FR',
    ];

    /**
     * Translate dictionary for resize method
     *
     * @var array
     */
    public static $filterKeyTranslate = [
        'point' => ImageInterface::FILTER_POINT,
        'box' => ImageInterface::FILTER_BOX,
        'triangle' => ImageInterface::FILTER_TRIANGLE,
        'hermite' => ImageInterface::FILTER_HERMITE,
        'hanning' => ImageInterface::FILTER_HANNING,
        'hamming' => ImageInterface::FILTER_HAMMING,
        'blackman' => ImageInterface::FILTER_BLACKMAN,
        'gaussian' => ImageInterface::FILTER_GAUSSIAN,
        'quadratic' => ImageInterface::FILTER_QUADRATIC,
        'cubic' => ImageInterface::FILTER_CUBIC,
        'catrom' => ImageInterface::FILTER_CATROM,
        'mitchell' => ImageInterface::FILTER_MITCHELL,
        'lanczos' => ImageInterface::FILTER_LANCZOS,
        'bessel' => ImageInterface::FILTER_BESSEL,
        'sinc' => ImageInterface::FILTER_SINC,
    ];

    /**
     * Translate dictionary for interlace method
     *
     * @var array
     */
    public static $interlaceKeyTranslate = [
        'none' => \Imagine\Image\ImageInterface::INTERLACE_NONE,
        'line' => \Imagine\Image\ImageInterface::INTERLACE_LINE,
        'plane' => \Imagine\Image\ImageInterface::INTERLACE_PLANE,
        'partition' => \Imagine\Image\ImageInterface::INTERLACE_PARTITION,
    ];

    /**
     * Translate dictionary for dither method
     *
     * @var array
     */
    public static $ditherKeyTranslate = [];

    /**
     * Translate dictionary for composite modes. set in constructor if driver is imagick.
     *
     * @var array
     */
    public static $compositeKeyTranslate = [];

    /**
     * Translate dictionary for translating crafts built in position constants into relative format (width/height offset)
     *
     * @var array
     */
    public static $craftPositionTranslate = [
        'top-left' => '0% 0%',
        'top-center' => '50% 0%',
        'top-right' => '100% 0%',
        'center-left' => '0% 50%',
        'center-center' => '50% 50%',
        'center-right' => '100% 50%',
        'bottom-left' => '0% 100%',
        'bottom-center' => '50% 100%',
        'bottom-right' => '100% 100%'
    ];


    // Constructor
    // =========================================================================

    public function __construct($config = [])
    {
        parent::__construct($config);
        
        // Detect image driver 
        self::detectImageDriver();
        
        // Set up imagick specific constant aliases
        if (self::$imageDriver === 'imagick') {
            self::$compositeKeyTranslate['blend'] = \Imagick::COMPOSITE_BLEND;
            self::$compositeKeyTranslate['darken'] = \Imagick::COMPOSITE_DARKEN;
            self::$compositeKeyTranslate['lighten'] = \Imagick::COMPOSITE_LIGHTEN;
            self::$compositeKeyTranslate['modulate'] = \Imagick::COMPOSITE_MODULATE;
            self::$compositeKeyTranslate['multiply'] = \Imagick::COMPOSITE_MULTIPLY;
            self::$compositeKeyTranslate['overlay'] = \Imagick::COMPOSITE_OVERLAY;
            self::$compositeKeyTranslate['screen'] = \Imagick::COMPOSITE_SCREEN;

            self::$ditherKeyTranslate['no'] = \Imagick::DITHERMETHOD_NO;
            self::$ditherKeyTranslate['riemersma'] = \Imagick::DITHERMETHOD_RIEMERSMA;
            self::$ditherKeyTranslate['floydsteinberg'] = \Imagick::DITHERMETHOD_FLOYDSTEINBERG;
        }
    }


    // Static public Methods
    // =========================================================================

    /**
     * @return ConfigModel
     */
    public static function getConfig(): ConfigModel
    {
        return self::$transformConfig ?? new ConfigModel(Plugin::$plugin->getSettings(), null);
    }

    /**
     * Detects which image driver to use
     */
    public static function detectImageDriver()
    {
        $extension = mb_strtolower(Craft::$app->getConfig()->getGeneral()->imageDriver);

        if ($extension === 'gd') {
            self::$imageDriver = 'gd';
        } else if ($extension === 'imagick') {
            self::$imageDriver = 'imagick';
        } else { // autodetect
            self::$imageDriver = Craft::$app->images->getIsGd() ? 'gd' : 'imagick';
        }
    }

    /**
     * @return bool
     */
    public static function hasSupportForWebP(): bool
    {
        self::detectImageDriver();
        
        if (self::$imageDriver === 'gd' && \function_exists('imagewebp')) {
            return true;
        }

        if (self::$imageDriver === 'imagick' && (\count(\Imagick::queryFormats('WEBP')) > 0)) {
            return true;
        }

        $config = self::getConfig();

        if ($config->useCwebp && $config->cwebpPath !== '' && file_exists($config->cwebpPath)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $handle
     * @param string $class
     */
    public static function registerEffect($handle, $class)
    {
        self::$effects[mb_strtolower($handle)] = $class;
    }

    /**
     * @param string $handle
     * @param string $class
     */
    public static function registerOptimizer($handle, $class)
    {
        self::$optimizers[mb_strtolower($handle)] = $class;
    }

    /**
     * @param string $handle
     * @param string $class
     */
    public static function registerExternalStorage($handle, $class)
    {
        self::$storage[mb_strtolower($handle)] = $class;
    }

    /**
     * @param string $path
     */
    public static function registerCachedRemoteFile($path)
    {
        self::$remoteImageSessionCache[] = $path;
    }


    // Public Methods
    // =========================================================================

    /**
     * @param Asset|string $image
     * @param array        $transforms
     * @param array        $transformDefaults
     * @param array        $configOverrides
     *
     * @return array|null
     * @throws ImagerException
     */
    public function transformImage($image, $transforms, $transformDefaults = null, $configOverrides = null)
    {
        if (!$image) {
            return null;
        }

        $returnType = 'array';

        if (!isset($transforms[0])) {
            $transforms = [$transforms];
            $returnType = 'object';
        }

        // Create config model
        self::$transformConfig = new ConfigModel(Plugin::$plugin->getSettings(), $configOverrides);

        // Fill missing transforms if fillTransforms is enabled
        if (self::$transformConfig->fillTransforms === true && \count($transforms) > 1) {
            $transforms = $this->fillTransforms($transforms);
        }

        // Merge in default transform parameters
        $transforms = $this->mergeTransforms($transforms, $transformDefaults);

        // Normalize transform parameters
        $transforms = $this->normalizeTransforms($transforms);

        // Create transformer
        try {
            if (!isset(self::$transformers[self::$transformConfig->transformer])) {
                Craft::error('Invalid transformer "'.self::$transformConfig->transformer.'"', __METHOD__);
                throw new ImagerException('Invalid transformer "'.self::$transformConfig->transformer.'"');
            }

            /** @var TransformerInterface $transformer */
            $transformer = new self::$transformers[self::$transformConfig->transformer]();
            $transformedImages = $transformer->transform($image, $transforms);
        } catch (ImagerException $e) {
            if (self::$transformConfig->suppressExceptions) {
                return null;
            }

            throw $e;
        }

        self::cleanSession();
        self::$transformConfig = null;

        if ($transformedImages === null) {
            return null;
        }

        return $returnType === 'object' ? $transformedImages[0] : $transformedImages;
    }

    /**
     * Creates srcset string
     *
     * @param array|mixed  $images
     * @param string $descriptor
     *
     * @return string
     */
    public function srcset($images, $descriptor = 'w'): string
    {
        $r = '';
        $generated = [];

        if (!\is_array($images)) {
            return '';
        }

        foreach ($images as $image) {
            switch ($descriptor) {
                case 'w':
                    if (!isset($generated[$image->getWidth()])) {
                        $r .= $image->getUrl().' '.$image->getWidth().'w, ';
                        $generated[$image->getWidth()] = true;
                    }
                    break;
                case 'h':
                    if (!isset($generated[$image->getHeight()])) {
                        $r .= $image->getUrl().' '.$image->getHeight().'h, ';
                        $generated[$image->getHeight()] = true;
                    }
                    break;
                case 'w+h':
                    $key = $image->getWidth().'x'.$image->getHeight();
                    if (!isset($generated[$key])) {
                        $r .= $image->getUrl().' '.$image->getWidth().'w '.$image->getHeight().'h, ';
                        $generated[$image->getWidth().'x'.$image->getHeight()] = true;
                    }
                    break;
            }
        }

        return $r !== '' ? substr($r, 0, -2) : '';
    }

    /**
     * Checks if asset is animated.
     *
     * An animated gif contains multiple "frames", with each frame having a header made up of:
     *  - a static 4-byte sequence (\x00\x21\xF9\x04)
     *  - 4 variable bytes
     *  - a static 2-byte sequence (\x00\x2C)
     *
     * We read through the file til we reach the end of the file, or we've found at least 2 frame headers
     *
     * @param $asset
     *
     * @return bool
     * @throws ImagerException
     */
    public function isAnimated($asset): bool
    {
        $source = new LocalSourceImageModel($asset);
        $source->getLocalCopy();

        if ($source->extension !== 'gif') {
            return false;
        }

        if (!($fh = @fopen($source->getFilePath(), 'rb'))) {
            return false;
        }

        $count = 0;

        while (!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100); //read 100kb at a time
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
        }

        fclose($fh);

        self::cleanSession();

        return $count > 0;
    }

    /**
     * Remove transforms for a given asset
     *
     * @param Asset $asset
     */
    public function removeTransformsForAsset(Asset $asset)
    {
        $config = self::getConfig();

        try {
            $sourceModel = new LocalSourceImageModel($asset);
            $targetModel = new LocalTargetImageModel($sourceModel, []);

            if (strpos($targetModel->path, $config->imagerSystemPath) !== false) {
                try {
                    FileHelper::clearDirectory(FileHelper::normalizePath($targetModel->path));
                } catch (ErrorException $e) {
                    Craft::error('Could not clear directory "'.$targetModel->path.'" ('.$e->getMessage().')', __METHOD__);
                } catch (InvalidParamException $e) {
                    Craft::error('Could not clear directory "'.$targetModel->path.'" ('.$e->getMessage().')', __METHOD__);
                }

                Craft::$app->templateCaches->deleteCachesByElementId($asset->id);

                if ($sourceModel->type !== 'local' && file_exists($sourceModel->getFilePath())) {
                    try {
                        FileHelper::unlink($sourceModel->getFilePath());
                    } catch (ErrorException $e) {
                        Craft::error('Could not remove file "'.$sourceModel->getFilePath().'" ('.$e->getMessage().')', __METHOD__);
                    }
                }
            }
        } catch (ImagerException $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }
    }

    /**
     * Clear all image transforms caches
     */
    public function deleteImageTransformCaches()
    {
        $path = Plugin::$plugin->getSettings()->imagerSystemPath;

        try {
            FileHelper::clearDirectory(FileHelper::normalizePath($path));
        } catch (ErrorException $e) {
            Craft::error('Could not clear directory "'.$path.'" ('.$e->getMessage().')', __METHOD__);
        } catch (InvalidParamException $e) {
            Craft::error('Could not clear directory "'.$path.'" ('.$e->getMessage().')', __METHOD__);
        }
    }

    /**
     * Clear all remote image caches
     */
    public function deleteRemoteImageCaches()
    {
        $path = Craft::$app->getPath()->getRuntimePath().'/imager/';

        try {
            FileHelper::clearDirectory(FileHelper::normalizePath($path));
        } catch (ErrorException $e) {
            Craft::error('Could not clear directory "'.$path.'" ('.$e->getMessage().')', __METHOD__);
        } catch (InvalidParamException $e) {
            Craft::error('Could not clear directory "'.$path.'" ('.$e->getMessage().')', __METHOD__);
        }
    }

    /**
     *
     */
    public static function cleanSession()
    {
        $config = self::getConfig();

        if (!$config->cacheRemoteFiles && \count(self::$remoteImageSessionCache) > 0) {
            foreach (self::$remoteImageSessionCache as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Fills in the missing transform objects
     *
     * @param array $transforms
     *
     * @return array
     */
    private function fillTransforms($transforms): array
    {
        $r = [];

        $attributeName = self::$transformConfig->fillAttribute;
        $interval = self::$transformConfig->fillInterval;

        $r[] = $transforms[0];

        for ($i = 1, $l = \count($transforms); $i < $l; $i++) {
            $prevTransform = $transforms[$i - 1];
            $currentTransform = $transforms[$i];

            if (isset($prevTransform[$attributeName], $currentTransform[$attributeName])) {
                if ($prevTransform[$attributeName] < $currentTransform[$attributeName]) {
                    for ($num = $prevTransform[$attributeName] + $interval, $maxNum = $currentTransform[$attributeName]; $num < $maxNum; $num += $interval) {
                        $transformCopy = $prevTransform;
                        $transformCopy[$attributeName] = $num;
                        $r[] = $transformCopy;
                    }
                } else {
                    for ($num = $prevTransform[$attributeName] - $interval, $minNum = $currentTransform[$attributeName]; $num > $minNum; $num -= $interval) {
                        $transformCopy = $prevTransform;
                        $transformCopy[$attributeName] = $num;
                        $r[] = $transformCopy;
                    }
                }
            }

            $r[] = $currentTransform;
        }

        return $r;
    }

    /**
     * Merges default transform object into an array of transforms
     *
     * @param array $transforms
     * @param array $defaults
     *
     * @return array
     */
    private function mergeTransforms($transforms, $defaults): array
    {
        $r = [];

        foreach ($transforms as $t) {
            $r[] = ($defaults !== null ? array_merge($defaults, $t) : $t);
        }

        return $r;
    }

    /**
     * Merges default transform object into an array of transforms
     *
     * @param array $transforms
     *
     * @return array
     */
    private function normalizeTransforms($transforms): array
    {
        $r = [];

        foreach ($transforms as $t) {
            $r[] = $this->normalizeTransform((array)$t);
        }

        return $r;
    }

    /**
     * Normalize transform object and values
     *
     * @param array $transform
     *
     * @return array
     */
    private function normalizeTransform($transform): array
    {
        // if resize mode is not crop or croponly, remove position
        if (isset($transform['mode'], $transform['position']) && (($transform['mode'] !== 'crop') && ($transform['mode'] !== 'croponly'))) {
            unset($transform['position']);
        }

        // if quality is used, assume it's jpegQuality
        if (isset($transform['quality'])) {
            $value = $transform['quality'];
            unset($transform['quality']);

            if (!isset($transform['jpegQuality'])) {
                $transform['jpegQuality'] = $value;
            }
        }

        // if ratio is set, and width or height is missing, calculate missing size
        if (isset($transform['ratio']) && (\is_float($transform['ratio']) || \is_int($transform['ratio']))) {
            if (isset($transform['width']) && !isset($transform['height'])) {
                $transform['height'] = round($transform['width'] / $transform['ratio']);
                unset($transform['ratio']);
            } else {
                if (isset($transform['height']) && !isset($transform['width'])) {
                    $transform['width'] = round($transform['height'] * $transform['ratio']);
                    unset($transform['ratio']);
                }
            }
        }

        // if transform is in Craft's named version, convert to percentage
        if (isset($transform['position'])) {

            if (\is_array($transform['position']) && isset($transform['position']['x']) && isset($transform['position']['y'])) {
                $transform['position'] = ($transform['position']['x'] * 100).' '.($transform['position']['y'] * 100);
            }

            if (isset(self::$craftPositionTranslate[(string)$transform['position']])) {
                $transform['position'] = self::$craftPositionTranslate[(string)$transform['position']];
            }

            $transform['position'] = str_replace('%', '', (string)$transform['position']);
        }

        
        // sort keys to get them in the same order 
        ksort($transform);

        // Move certain keys around abit to make the filename a bit more sane when viewed unencoded
        $transform = ImagerHelpers::moveArrayKeyToPos('mode', 0, $transform);
        $transform = ImagerHelpers::moveArrayKeyToPos('height', 0, $transform);
        $transform = ImagerHelpers::moveArrayKeyToPos('width', 0, $transform);
        $transform = ImagerHelpers::moveArrayKeyToPos('preEffects', 99, $transform);
        $transform = ImagerHelpers::moveArrayKeyToPos('effects', 99, $transform);
        $transform = ImagerHelpers::moveArrayKeyToPos('watermark', 99, $transform);

        return $transform;
    }
}
