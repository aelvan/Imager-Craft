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
use craft\elements\Asset;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;

use aelvan\imager\helpers\ImagerHelpers;
use aelvan\imager\jobs\OptimizeJob;
use aelvan\imager\models\ConfigModel;
use aelvan\imager\models\CraftTransformedImageModel;
use aelvan\imager\models\LocalSourceImageModel;
use aelvan\imager\models\LocalTargetImageModel;
use aelvan\imager\services\ImagerService;
use aelvan\imager\exceptions\ImagerException;
use aelvan\imager\effects\ImagerEffectsInterface;
use aelvan\imager\optimizers\ImagerOptimizeInterface;
use aelvan\imager\externalstorage\ImagerStorageInterface;

use Imagine\Gd\Image as GdImage;
use Imagine\Imagick\Image as ImagickImage;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\OutOfBoundsException;
use Imagine\Exception\RuntimeException;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\LayersInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;

use yii\base\ErrorException;
use yii\base\Exception;

/**
 * CraftTransformer
 *
 * @author    André Elvan
 * @package   Imager
 * @since     2.0.0
 */
class CraftTransformer extends Component implements TransformerInterface
{
    private $imagineInstance = null;
    private $imageInstance = null;

    /**
     * CraftTransformer constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->imagineInstance = $this->createImagineInstance();
    }

    /**
     * Main transform method
     *
     * @param Asset|string $image
     * @param array        $transforms
     *
     * @return array|null
     *
     * @throws ImagerException
     */
    public function transform($image, $transforms)
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        $sourceModel = new LocalSourceImageModel($image);

        $transformedImages = [];

        foreach ($transforms as $transform) {
            $transformedImages[] = $this->getTransformedImage($sourceModel, $transform);
        }

        $taskCreated = false;

        // Loop over transformed images and do post optimizations and upload to external storage 
        foreach ($transformedImages as $transformedImage) {
            /** @var CraftTransformedImageModel $transformedImage */
            if ($transformedImage->isNew) {
                $isFinalVersion = $this->optimize($transformedImage);
                $this->store($transformedImage, $isFinalVersion);

                if (!$isFinalVersion) {
                    $taskCreated = true;
                }
            }
        }

        // If ajax request, trigger jobs immediately
        if ($taskCreated && $config->runJobsImmediatelyOnAjaxRequests && Craft::$app->getRequest()->getIsAjax()) {
            $this->triggerQueueNow();
        }

        return $transformedImages;
    }

    /**
     * Store transformed image in configured storages
     *
     * @param CraftTransformedImageModel $image
     * @param bool                       $isFinalVersion
     *
     * @throws ImagerException
     */
    public function store(CraftTransformedImageModel $image, bool $isFinalVersion)
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        if (empty($config->storages)) {
            return;
        }

        $path = $image->path;
        $uri = str_replace($config->imagerSystemPath, '', $path);

        foreach ($config->storages as $storage) {
            if (isset(ImagerService::$storage[$storage])) {
                $storageSettings = $config->storageConfig[$storage] ?? null;

                if ($storageSettings) {
                    /** @var ImagerStorageInterface $storageClass */
                    $storageClass = ImagerService::$storage[$storage];
                    $result = $storageClass::upload($path, $uri, $isFinalVersion, $storageSettings);

                    if (!$result) {
                        // todo : delete transformed file. Assume that we'd want to try again.
                    }
                } else {
                    $msg = 'Could not find settings for storage "'.$storage.'"';
                    Craft::error($msg, __METHOD__);
                    throw new ImagerException($msg);
                }
            } else {
                $msg = 'Could not find a registered storage with handle "'.$storage.'"';
                Craft::error($msg, __METHOD__);
                throw new ImagerException($msg);
            }
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Gets one transformed image based on source image and transform
     *
     * @param LocalSourceImageModel $sourceModel
     * @param array                 $transform
     *
     * @return CraftTransformedImageModel|null
     *
     * @throws ImagerException
     */
    private function getTransformedImage($sourceModel, $transform)
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        if ($config->getSetting('noop', $transform)) {
            // todo : just return source image unmodified
        }

        if ($this->imagineInstance === null) {
            $msg = Craft::t('imager', 'Imagine instance was not created for driver “{driver}”.', ['driver' => ImagerService::$imageDriver]);
            Craft::error($msg, __METHOD__);
            throw new ImagerException($msg);
        }

        // Create target model
        $targetModel = new LocalTargetImageModel($sourceModel, $transform);

        // Set save options
        $saveOptions = $this->getSaveOptions($targetModel->extension, $transform);

        // Do transform if transform doesn't exist, cache is disabled, or cache expired
        if (!$config->getSetting('cacheEnabled', $transform) ||
            !file_exists($targetModel->getFilePath()) ||
            (($config->getSetting('cacheDuration', $transform) !== false) && (FileHelper::lastModifiedTime($targetModel->getFilePath()) + $config->getSetting('cacheDuration', $transform) < time()))
        ) {
            // Make sure that we have a local copy.
            $sourceModel->getLocalCopy();

            // Check all the things that could go wrong(tm)
            if (!realpath($sourceModel->path)) {
                $msg = Craft::t('imager', 'Source folder “{sourcePath}” does not exist', ['sourcePath' => $sourceModel->path]);
                Craft::error($msg, __METHOD__);
                throw new ImagerException($msg);
            }

            if (!realpath($targetModel->path)) {
                try {
                    FileHelper::createDirectory($targetModel->path);
                } catch (Exception $e) {
                    // ignore for now, trying to create
                }

                if (!realpath($targetModel->path)) {
                    $msg = Craft::t('imager', 'Target folder “{targetPath}” does not exist and could not be created', ['targetPath' => $targetModel->path]);
                    Craft::error($msg, __METHOD__);
                    throw new ImagerException($msg);
                }

                $targetModel->path = realpath($targetModel->path);
            }

            try {
                $targetPathIsWriteable = FileHelper::isWritable($targetModel->path);
            } catch (ErrorException $e) {
                $targetPathIsWriteable = false;
            }

            if ($targetModel->path && !$targetPathIsWriteable) {
                $msg = Craft::t('imager', 'Target folder “{targetPath}” is not writeable', ['targetPath' => $targetModel->path]);
                Craft::error($msg, __METHOD__);
                throw new ImagerException($msg);
            }

            if (!file_exists($sourceModel->getFilePath())) {
                $msg = Craft::t('imager', 'Requested image “{fileName}” does not exist in path “{sourcePath}”', ['fileName' => $sourceModel->filename, 'sourcePath' => $sourceModel->path]);
                Craft::error($msg, __METHOD__);
                throw new ImagerException($msg);
            }

            if (!Craft::$app->images->checkMemoryForImage($sourceModel->getFilePath())) {
                $msg = Craft::t('imager', 'Not enough memory available to perform this image operation.');
                Craft::error($msg, __METHOD__);
                throw new ImagerException($msg);
            }

            // Create the imageInstance. only once if reuse is enabled, or always
            if ($this->imageInstance === null || !$config->getSetting('instanceReuseEnabled', $transform)) {
                $this->imageInstance = $this->imagineInstance->open($sourceModel->getFilePath());
            }

            $animated = false;

            // Check if this is an animated gif and we're using Imagick
            if ($sourceModel->extension === 'gif' && ImagerService::$imageDriver !== 'gd' && $this->imageInstance->layers()) {
                $animated = true;
            }

            // Run tranforms, either on each layer of an animated gif, or on the whole image.
            if ($animated) {
                if ($this->imageInstance->layers()) {
                    $this->imageInstance->layers()->coalesce();
                }

                // We need to create a new image instance with the target size, or letterboxing will be wrong.
                $originalSize = $this->imageInstance->getSize();
                $resizeSize = ImagerHelpers::getResizeSize($originalSize, $transform, $config->getSetting('allowUpscale', $transform));
                $layers = $this->imageInstance->layers() ?? [];
                $gif = $this->imagineInstance->create($resizeSize);

                if ($gif->layers()) {
                    $gif->layers()->remove(0);
                }

                list($startFrame, $endFrame, $interval) = $this->getFramesVars($layers, $transform);

                for ($i = $startFrame; $i <= $endFrame; $i += $interval) {
                    if (isset($layers[$i])) {
                        $layer = $layers[$i];
                        $this->transformLayer($layer, $transform, $sourceModel->extension, $targetModel->extension);
                        $gif->layers()->add($layer);
                    }
                }

                $this->imageInstance = $gif;
            } else {
                $this->transformLayer($this->imageInstance, $transform, $sourceModel->extension, $targetModel->extension);
            }

            // If Image Driver is imagick and removeMetadata is true, remove meta data
            if (ImagerService::$imageDriver === 'imagick' && $config->getSetting('removeMetadata', $transform)) {
                $this->imageInstance->strip();
            }

            // Convert the image to RGB before converting to webp/saving
            if ($config->getSetting('convertToRGB', $transform)) {
                $this->imageInstance->usePalette(new RGB());
            }

            // Save the transform
            if ($targetModel->extension === 'webp') {
                if (ImagerService::hasSupportForWebP()) {
                    $this->saveAsWebp($this->imageInstance, $targetModel->getFilePath(), $sourceModel->extension, $saveOptions);
                } else {
                    $msg = Craft::t('imager', 'This version of {imageDriver} does not support the webp format. You should use “craft.imager.serverSupportsWebp” in your templates to test for it.', ['imageDriver' => ImagerService::$imageDriver === 'gd' ? 'GD' : 'Imagick']);
                    Craft::error($msg, __METHOD__);
                    throw new ImagerException($msg);
                }
            } else {
                $this->imageInstance->save($targetModel->getFilePath(), $saveOptions);
            }

            $targetModel->isNew = true;
        }

        // create CraftTransformedImageModel for transformed image
        $imageModel = new CraftTransformedImageModel($targetModel, $sourceModel, $transform);

        return $imageModel;
    }

    /**
     * Apply transforms to an image or layer.
     *
     * @param GdImage|ImagickImage $layer
     * @param array                $transform
     * @param string               $sourceExtension
     * @param string               $targetExtension
     *
     * @throws ImagerException
     */
    private function transformLayer(&$layer, $transform, $sourceExtension, $targetExtension)
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        // Apply any pre resize filters
        if (isset($transform['preEffects'])) {
            $this->applyEffects($layer, $transform['preEffects']);
        }

        try {
            // Get size and crop information
            $originalSize = $layer->getSize();
            $cropSize = ImagerHelpers::getCropSize($originalSize, $transform, $config->getSetting('allowUpscale', $transform));
            $resizeSize = ImagerHelpers::getResizeSize($originalSize, $transform, $config->getSetting('allowUpscale', $transform));
            $filterMethod = $this->getFilterMethod($transform);

            // Do the resize
            if (ImagerService::$imageDriver === 'imagick' && $config->getSetting('smartResizeEnabled', $transform)) {
                /** @var ImagickImage $layer */
                $layer->smartResize($resizeSize, (bool)Craft::$app->config->general->preserveImageColorProfiles, $config->getSetting('jpegQuality', $transform));
            } else {
                $layer->resize($resizeSize, $filterMethod);
            }

            // Do the crop
            if (!isset($transform['mode']) || mb_strtolower($transform['mode']) === 'crop' || mb_strtolower($transform['mode']) === 'croponly') {
                $cropPoint = ImagerHelpers::getCropPoint($resizeSize, $cropSize, $config->getSetting('position', $transform));
                $layer->crop($cropPoint, $cropSize);
            }
        } catch (InvalidArgumentException $e) {
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        } catch (RuntimeException $e) {
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }

        // Letterbox, add padding
        if (isset($transform['mode']) && mb_strtolower($transform['mode']) === 'letterbox') {
            $this->applyLetterbox($layer, $transform);
        }

        // Apply post resize effects
        if (isset($transform['effects'])) {
            $this->applyEffects($layer, $transform['effects']);
        }

        // Interlace if true
        if ($config->getSetting('interlace', $transform)) {
            $interlaceVal = $config->getSetting('interlace', $transform);

            if (\is_string($interlaceVal)) {
                $layer->interlace(ImagerService::$interlaceKeyTranslate[$interlaceVal]);
            } else {
                $layer->interlace(ImagerService::$interlaceKeyTranslate['line']);
            }
        }

        // Apply watermark if enabled
        if (isset($transform['watermark'])) {
            $this->applyWatermark($layer, $transform['watermark']);
        }

        // Apply background color if enabled and applicable
        if (($sourceExtension !== $targetExtension) && ($sourceExtension !== 'jpg') && ($targetExtension === 'jpg') && ($config->getSetting('bgColor', $transform) !== '')) {
            $this->applyBackgroundColor($layer, $config->getSetting('bgColor', $transform));
        }
    }

    /**
     * Creates the Imagine instance depending on the chosen image driver.
     *
     * @return \Imagine\Gd\Imagine|\Imagine\Imagick\Imagine|null
     */
    private function createImagineInstance()
    {
        try {
            if (ImagerService::$imageDriver === 'gd') {
                return new \Imagine\Gd\Imagine();
            }

            if (ImagerService::$imageDriver === 'imagick') {
                return new \Imagine\Imagick\Imagine();
            }
        } catch (RuntimeException $e) {
            // just ignore for now
        }

        return null;
    }

    /**
     * Returns the filter method for resize operations
     *
     * @param array $transform
     *
     * @return string
     */
    private function getFilterMethod($transform): string
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        return ImagerService::$imageDriver === 'imagick' ? ImagerService::$filterKeyTranslate[(string)$config->getSetting('resizeFilter', $transform)] : ImageInterface::FILTER_UNDEFINED;
    }


    /**
     * Saves image as webp
     *
     * @param GdImage|ImagickImage $imageInstance
     * @param string               $path
     * @param string               $sourceExtension
     * @param array                $saveOptions
     *
     * @throws ImagerException
     */
    private function saveAsWebp($imageInstance, $path, $sourceExtension, $saveOptions)
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        if ($config->getSetting('useCwebp')) {

            // Save temp file
            $tempFile = $this->saveTemporaryFile($imageInstance, $sourceExtension);

            // Convert to webp with cwebp
            $command = escapeshellcmd($config->getSetting('cwebpPath').' '.$config->getSetting('cwebpOptions').' -q '.$saveOptions['webp_quality'].' '.$tempFile.' -o '.$path);
            $r = shell_exec($command);

            if (!file_exists($path)) {
                $msg = Craft::t('imager', 'Temporary file save operation failed: '.$r);
                Craft::error($msg, __METHOD__);
                throw new ImagerException($msg);
            }

            // Delete temp file
            unlink($tempFile);
        } else {
            if (ImagerService::$imageDriver === 'gd') {
                /** @var GdImage $imageInstance */
                $instance = $imageInstance->getGdResource();

                if (false === /** @scrutinizer ignore-call */ \imagewebp($instance, $path, $saveOptions['webp_quality'])) {
                    $msg = Craft::t('imager', 'GD webp save operation failed');
                    Craft::error($msg, __METHOD__);
                    throw new ImagerException($msg);
                }

                // Fix for corrupt file bug (http://stackoverflow.com/questions/30078090/imagewebp-php-creates-corrupted-webp-files)
                if (filesize($path) % 2 === 1) {
                    file_put_contents($path, "\0", FILE_APPEND);
                }
            }

            if (ImagerService::$imageDriver === 'imagick') {
                 /** @var ImagickImage $imageInstance */
                $instance = $imageInstance->getImagick();

                $instance->setImageFormat('webp');

                $hasTransparency = $instance->getImageAlphaChannel();

                if ($hasTransparency) {
                    $instance->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
                    $instance->setBackgroundColor(new \ImagickPixel('transparent'));
                }

                $instance->setImageCompressionQuality($saveOptions['webp_quality']);
                $imagickOptions = $saveOptions['webp_imagick_options'];

                if ($imagickOptions && \count($imagickOptions) > 0) {
                    foreach ($imagickOptions as $key => $val) {
                        $instance->setOption('webp:'.$key, $val);
                    }
                }

                $instance->writeImage($path);
            }
        }
    }

    /**
     * Save temporary file and return filename
     *
     * @param GdImage|ImagickImage $imageInstance
     * @param string               $sourceExtension
     *
     * @return string
     *
     * @throws ImagerException
     */
    private function saveTemporaryFile($imageInstance, $sourceExtension): string
    {
        $tempPath = Craft::$app->getPath()->getRuntimePath().'imager/temp/';

        // Check if the path exists
        if (!realpath($tempPath)) {
            try {
                FileHelper::createDirectory($tempPath);
            } catch (Exception $e) {
                // just ignore for now, trying to create
            }

            if (!realpath($tempPath)) {
                $msg = Craft::t('imager', 'Temp folder “{tempPath}” does not exist and could not be created', ['tempPath' => $tempPath]);
                Craft::error($msg, __METHOD__);
                throw new ImagerException($msg);
            }
        }

        $targetFilePath = $tempPath.md5(time()).'.'.$sourceExtension;

        $saveOptions = [
            'jpeg_quality' => 100,
            'png_compression_level' => 1,
            'flatten' => true
        ];

        $imageInstance->save($targetFilePath, $saveOptions);

        return $targetFilePath;
    }

    /**
     * Get the save options based on extension and transform
     *
     * @param string $extension
     * @param array  $transform
     *
     * @return array
     */
    private function getSaveOptions($extension, $transform): array
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        switch (mb_strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                return ['jpeg_quality' => $config->getSetting('jpegQuality', $transform)];
            case 'gif':
                return ['flatten' => false];
            case 'png':
                return ['png_compression_level' => $config->getSetting('pngCompressionLevel', $transform)];
            case 'webp':
                return ['webp_quality' => $config->getSetting('webpQuality', $transform), 'webp_imagick_options' => $config->getSetting('webpImagickOptions', $transform)];
        }

        return [];
    }

    /**
     * Apply letterbox to image
     *
     * @param GdImage|ImagickImage|ImageInterface $imageInstance
     * @param array                               $transform
     *
     * @throws ImagerException
     */
    private function applyLetterbox(&$imageInstance, $transform)
    {
        if (isset($transform['width'], $transform['height'])) { // if both isn't set, there's no need for a letterbox
            /** @var ConfigModel $settings */
            $config = ImagerService::getConfig();

            $letterboxDef = $config->getSetting('letterbox', $transform);

            try {
                $size = new Box($transform['width'], $transform['height']);

                $position = new Point(
                    (int)floor(((int)$transform['width'] - $imageInstance->getSize()->getWidth()) / 2),
                    (int)floor(((int)$transform['height'] - $imageInstance->getSize()->getHeight()) / 2)
                );
            } catch (InvalidArgumentException $e) {
                Craft::error($e->getMessage(), __METHOD__);
                throw new ImagerException($e->getMessage(), $e->getCode(), $e);
            }

            $palette = new RGB();
            $color = $palette->color(
                $letterboxDef['color'] ?? '#000',
                isset($letterboxDef['opacity']) ? (int)($letterboxDef['opacity'] * 100) : 0
            );
            
            if ($this->imagineInstance !== null) {
                $backgroundImage = $this->imagineInstance->create($size, $color);
                $backgroundImage->paste($imageInstance, $position);
                $imageInstance = $backgroundImage;
            }
        }
    }

    /**
     * Apply background color to image when converting from transparent to non-transparent
     *
     * @param GdImage|ImagickImage|ImageInterface $imageInstance
     * @param string                              $bgColor
     *
     * @throws ImagerException
     */
    private function applyBackgroundColor(&$imageInstance, $bgColor)
    {
        $palette = new RGB();
        $color = $palette->color($bgColor);

        try {
            $topLeft = new Point(0, 0);
        } catch (InvalidArgumentException $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }

        if ($this->imagineInstance !== null) {
            $backgroundImage = $this->imagineInstance->create($imageInstance->getSize(), $color);
            $backgroundImage->paste($imageInstance, $topLeft);
            $imageInstance = $backgroundImage;
        }
    }

    /**
     * Apply watermark to image
     *
     * @param GdImage|ImagickImage|ImageInterface $imageInstance
     * @param array                               $watermark
     *
     * @throws ImagerException
     */
    private function applyWatermark($imageInstance, $watermark)
    {
        if (!isset($watermark['image'])) {
            $msg = Craft::t('imager', 'Watermark image property not set');
            Craft::error($msg, __METHOD__);
            throw new ImagerException($msg);
        }

        if (!isset($watermark['width'], $watermark['height'])) {
            $msg = Craft::t('imager', 'Watermark image size is not set');
            Craft::error($msg, __METHOD__);
            throw new ImagerException($msg);
        }

        $sourceModel = new LocalSourceImageModel($watermark['image']);
        $sourceModel->getLocalCopy();
        $watermarkInstance = $this->imagineInstance->open($sourceModel->getFilePath());

        try {
            $watermarkBox = new Box($watermark['width'], $watermark['height']);
        } catch (InvalidArgumentException $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }

        $watermarkInstance->resize($watermarkBox, ImageInterface::FILTER_UNDEFINED);

        if (isset($watermark['position'])) {
            $position = $watermark['position'];

            if (isset($position['top'])) {
                $posY = (int)$position['top'];
            } else {
                if (isset($position['bottom'])) {
                    $posY = $imageInstance->getSize()->getHeight() - (int)$watermark['height'] - (int)$position['bottom'];
                } else {
                    $posY = $imageInstance->getSize()->getHeight() - (int)$watermark['height'] - 10;
                }
            }

            if (isset($position['left'])) {
                $posX = (int)$position['left'];
            } else {
                if (isset($position['right'])) {
                    $posX = $imageInstance->getSize()->getWidth() - (int)$watermark['width'] - (int)$position['right'];
                } else {
                    $posX = $imageInstance->getSize()->getWidth() - (int)$watermark['width'] - 10;
                }
            }
        } else {
            $posY = $imageInstance->getSize()->getHeight() - (int)$watermark['height'] - 10;
            $posX = $imageInstance->getSize()->getWidth() - (int)$watermark['width'] - 10;
        }

        try {
            $positionPoint = new Point($posX, $posY);
        } catch (InvalidArgumentException $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }

        if (ImagerService::$imageDriver === 'imagick') {
            /** @var ImagickImage $watermarkInstance */
            $watermarkImagick = $watermarkInstance->getImagick();

            if (isset($watermark['opacity'])) {
                $watermarkImagick->evaluateImage(\Imagick::EVALUATE_MULTIPLY, (float)$watermark['opacity'],
                    \Imagick::CHANNEL_ALPHA);
            }

            if (isset($watermark['blendMode'], ImagerService::$compositeKeyTranslate[$watermark['blendMode']])) {
                $blendMode = ImagerService::$compositeKeyTranslate[$watermark['blendMode']];
            } else {
                $blendMode = \Imagick::COMPOSITE_ATOP;
            }

            /** @var ImagickImage $imageInstance */
            $imageInstance->getImagick()->compositeImage($watermarkImagick, $blendMode, $positionPoint->getX(), $positionPoint->getY());
        } else { // it's GD :(
            try {
                $imageInstance->paste($watermarkInstance, $positionPoint);
            } catch (InvalidArgumentException $e) {
                Craft::error($e->getMessage(), __METHOD__);
                throw new ImagerException($e->getMessage(), $e->getCode(), $e);
            } catch (OutOfBoundsException $e) {
                Craft::error($e->getMessage(), __METHOD__);
                throw new ImagerException($e->getMessage(), $e->getCode(), $e);
            } catch (RuntimeException $e) {
                Craft::error($e->getMessage(), __METHOD__);
                throw new ImagerException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * Applies effects to image.
     *
     * @param GdImage|ImagickImage $image
     * @param array                $effects
     */
    private function applyEffects($image, $effects)
    {
        foreach ($effects as $effect => $value) {
            $effect = mb_strtolower($effect);

            if (isset(ImagerService::$effects[$effect])) {
                /** @var ImagerEffectsInterface $effectClass */
                $effectClass = ImagerService::$effects[$effect];
                $effectClass::apply($image, $value);
            }
        }
    }

    /**
     * Get vars for animated gif frames setup
     *
     * @param LayersInterface|array $layers
     * @param array                 $transform
     *
     * @return array
     */
    private function getFramesVars($layers, $transform): array
    {
        $startFrame = 0;
        $endFrame = \count($layers) - 1;
        $interval = 1;

        if (isset($transform['frames'])) {
            $framesIntArr = explode('@', $transform['frames']);

            if (\count($framesIntArr) > 1) {
                $interval = $framesIntArr[1];
            }

            $framesArr = explode('-', $framesIntArr[0]);

            if (\count($framesArr) > 1) {
                $startFrame = $framesArr[0];
                if ($framesArr[1] !== '*') {
                    $endFrame = $framesArr[1];
                }
            } else {
                $startFrame = $endFrame = $framesArr[0];
            }

            if ($endFrame > \count($layers) - 1) {
                $endFrame = \count($layers) - 1;
            }
        }

        return [$startFrame, $endFrame, $interval];
    }

    /**
     * Post optimizations
     *
     * @param CraftTransformedImageModel $transformedImage
     *
     * @return bool Return if the image is the final version or not. If a task was set up, it's not.
     */
    private function optimize($transformedImage): bool
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        // If there are no enabled optimizers, exit now
        if (empty($config->optimizers)) {
            return true;
        }

        $jobCreated = false;
        foreach ($config->optimizers as $optimizer) {
            if (isset(ImagerService::$optimizers[$optimizer])) {
                $optimizerSettings = $config->optimizerConfig[$optimizer] ?? null;

                if ($optimizerSettings) {
                    if ($this->shouldOptimizeByExtension($transformedImage->extension, $optimizerSettings['extensions'])) {
                        if ($config->optimizeType === 'job' || $config->optimizeType === 'task') {
                            $this->createOptimizeJob($optimizer, $transformedImage->getPath(), $optimizerSettings);
                            $jobCreated = true;
                        } else {
                            /** @var ImagerOptimizeInterface $optimizerClass */
                            $optimizerClass = ImagerService::$optimizers[$optimizer];
                            $optimizerClass::optimize($transformedImage->getPath(), $optimizerSettings);

                            // Clear stat cache to make sure old file size is not cached
                            clearstatcache(true, $transformedImage->getPath());
                        }
                    }
                } else {
                    Craft::error('Could not find settings for optimizer "'.$optimizer.'"', __METHOD__);
                }
            } else {
                Craft::error('Could not find a registered optimizer with handle "'.$optimizer.'"', __METHOD__);
            }
        }

        return !$jobCreated;
    }

    /**
     * Checks if extension is in array of extensions
     *
     * @param string $extension
     * @param array  $validExtensions
     *
     * @return bool
     */
    private function shouldOptimizeByExtension(string $extension, array $validExtensions): bool
    {
        return \in_array($extension === 'jpeg' ? 'jpg' : $extension, $validExtensions, true);
    }

    /**
     * Creates optimize queue job
     *
     * @param string $handle
     * @param string $filePath
     * @param array  $settings
     */
    private function createOptimizeJob(string $handle, string $filePath, array $settings)
    {
        $queue = Craft::$app->getQueue();

        $jobId = $queue->push(new OptimizeJob([
            'description' => Craft::t('imager', 'Optimizing images ('.$handle.')'),
            'optimizer' => $handle,
            'optimizerSettings' => $settings,
            'filePath' => $filePath,
        ]));

        Craft::info('Created optimize job for '.$handle.' (job id is '.$jobId.')', __METHOD__);
    }

    /**
     * Trigger queue/run immediately
     */
    private function triggerQueueNow()
    {
        $url = UrlHelper::actionUrl('queue/run');

        if (\function_exists('curl_init')) {
            $ch = curl_init($url);

            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => false,
                CURLOPT_NOSIGNAL => true
            ];

            if (\defined('CURLOPT_TIMEOUT_MS')) {
                $options[CURLOPT_TIMEOUT_MS] = 500;
            } else {
                $options[CURLOPT_TIMEOUT] = 1;
            }

            curl_setopt_array($ch, $options);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
