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

class ImagerService extends BaseApplicationComponent
{
    var $imageDriver = 'gd';
    var $imagineInstance = null;
    var $imageInstance = null;
    var $configModel = null;
    var $taskCreated = false;
    var $invalidatePaths = array();

    // translate dictionary for translating transform keys into filename markers
    public static $transformKeyTranslate = array(
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
    );

    // translate dictionary for resize method 
    public static $filterKeyTranslate = array(
      'point' => \Imagine\Image\ImageInterface::FILTER_POINT,
      'box' => \Imagine\Image\ImageInterface::FILTER_BOX,
      'triangle' => \Imagine\Image\ImageInterface::FILTER_TRIANGLE,
      'hermite' => \Imagine\Image\ImageInterface::FILTER_HERMITE,
      'hanning' => \Imagine\Image\ImageInterface::FILTER_HANNING,
      'hamming' => \Imagine\Image\ImageInterface::FILTER_HAMMING,
      'blackman' => \Imagine\Image\ImageInterface::FILTER_BLACKMAN,
      'gaussian' => \Imagine\Image\ImageInterface::FILTER_GAUSSIAN,
      'quadratic' => \Imagine\Image\ImageInterface::FILTER_QUADRATIC,
      'cubic' => \Imagine\Image\ImageInterface::FILTER_CUBIC,
      'catrom' => \Imagine\Image\ImageInterface::FILTER_CATROM,
      'mitchell' => \Imagine\Image\ImageInterface::FILTER_MITCHELL,
      'lanczos' => \Imagine\Image\ImageInterface::FILTER_LANCZOS,
      'bessel' => \Imagine\Image\ImageInterface::FILTER_BESSEL,
      'sinc' => \Imagine\Image\ImageInterface::FILTER_SINC,
    );

    // translate dictionary for interlace method 
    public static $interlaceKeyTranslate = array(
      'none' => \Imagine\Image\ImageInterface::INTERLACE_NONE,
      'line' => \Imagine\Image\ImageInterface::INTERLACE_LINE,
      'plane' => \Imagine\Image\ImageInterface::INTERLACE_PLANE,
      'partition' => \Imagine\Image\ImageInterface::INTERLACE_PARTITION,
    );

    // translate dictionary for dither method 
    public static $ditherKeyTranslate = array();

    // translate dictionary for composite modes. set in constructor if driver is imagick. 
    public static $compositeKeyTranslate = array();

    // translate dictionary for translating crafts built in position constants into relative format (width/height offset) 
    public static $craftPositionTranslate = array(
      'top-left' => '0% 0%',
      'top-center' => '50% 0%',
      'top-right' => '100% 0%',
      'center-left' => '0% 50%',
      'center-center' => '50% 50%',
      'center-right' => '100% 50%',
      'bottom-left' => '0% 100%',
      'bottom-center' => '50% 100%',
      'bottom-right' => '100% 100%'
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $extension = mb_strtolower(craft()->config->get('imageDriver'));

        if ($extension === 'gd') { // set in config
            $this->imageDriver = 'gd';
        } else {
            if ($extension === 'imagick') {
                $this->imageDriver = 'imagick';
            } else { // autodetect
                if (craft()->images->isGd()) {
                    $this->imageDriver = 'gd';
                } else {
                    $this->imageDriver = 'imagick';
                }
            }
        }

        $this->imagineInstance = $this->_createImagineInstance();

        if ($this->imageDriver === 'imagick') {
            ImagerService::$compositeKeyTranslate['blend'] = \imagick::COMPOSITE_BLEND;
            ImagerService::$compositeKeyTranslate['darken'] = \imagick::COMPOSITE_DARKEN;
            ImagerService::$compositeKeyTranslate['lighten'] = \imagick::COMPOSITE_LIGHTEN;
            ImagerService::$compositeKeyTranslate['modulate'] = \imagick::COMPOSITE_MODULATE;
            ImagerService::$compositeKeyTranslate['multiply'] = \imagick::COMPOSITE_MULTIPLY;
            ImagerService::$compositeKeyTranslate['overlay'] = \imagick::COMPOSITE_OVERLAY;
            ImagerService::$compositeKeyTranslate['screen'] = \imagick::COMPOSITE_SCREEN;

            ImagerService::$ditherKeyTranslate['no'] = \Imagick::DITHERMETHOD_NO;
            ImagerService::$ditherKeyTranslate['riemersma'] = \Imagick::DITHERMETHOD_RIEMERSMA;
            ImagerService::$ditherKeyTranslate['floydsteinberg'] = \Imagick::DITHERMETHOD_FLOYDSTEINBERG;
        }
    }


    /**
     * Creates the Imagine instance depending on the image driver
     */
    private function _createImagineInstance()
    {
        if ($this->imageDriver === 'gd') {
            return new \Imagine\Gd\Imagine();
        }
        
        if ($this->imageDriver === 'imagick') {
            return new \Imagine\Imagick\Imagine();
        }
        
        return null;
    }


    /**
     * Remove transforms for a given asset
     *
     * @param AssetFileModel $asset
     */
    public function removeTransformsForAsset(AssetFileModel $asset)
    {
        $paths = new Imager_ImagePathsModel($asset);

        if (strpos($paths->targetPath, craft()->imager->getSetting('imagerSystemPath')) !== false) {
            IOHelper::clearFolder($paths->targetPath);
            craft()->templateCache->deleteCachesByElementId($asset->id);

            if ($paths->isRemote) {
                IOHelper::deleteFile($paths->sourcePath . $paths->sourceFilename);
            }
        }
    }

    /**
     * Clear all image transforms caches
     */
    public function deleteImageTransformCaches()
    {
        IOHelper::clearFolder(craft()->imager->getSetting('imagerSystemPath'));
    }

    /**
     * Clear all remote image caches
     */
    public function deleteRemoteImageCaches()
    {
        IOHelper::clearFolder(craft()->path->getRuntimePath() . 'imager/');
    }
    
    public function srcset($images, $descriptor = 'w')
    {
        $r = '';
        $generated = array();
        
        if (!is_array($images)) {
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
                    if (!isset($generated[$image->getWidth() . 'x' . $image->getHeight()])) {
                        $r .= $image->getUrl().' '.$image->getWidth().'w ' .$image->getHeight().'h, ';
                        $generated[$image->getWidth() . 'x' . $image->getHeight()] = true;
                    }
                    break;
            }
        }
        
        return substr($r, 0, strlen($r) - 2);
    }
    
    /**
     * Do an image transform
     *
     * @param AssetFileModel|string $image
     * @param array $transform
     * @param array $transformDefaults
     * @param array $configOverrides
     *
     * @throws Exception
     * @return array|Image
     */
    public function transformImage($image, $transform, $transformDefaults, $configOverrides)
    {
        if (!$image) {
            return null; // there's nothing to see here, move along.
        }
        
        // create config model
        $this->configModel = new Imager_ConfigModel($configOverrides);
        
        // Fill missing transforms if fillTransforms is enabled
        if (craft()->imager->getSetting('fillTransforms')===true)
        {
            if (is_array($transform) && count($transform)>1) {
                $transform = $this->_fillTransforms($transform);
            }
        }

        // if imgix is enabled this is a totally different ballgame
        if (craft()->imager->getSetting('imgixEnabled')) {
            $r = null;

            if (isset($transform[0])) {
                foreach ($transform as $t) {
                    $r[] = craft()->imager_imgix->getTransformedImage($image, $transformDefaults != null ? array_merge($transformDefaults, $t) : $t);
                }
            } else {
                    $r = craft()->imager_imgix->getTransformedImage($image, $transformDefaults != null ? array_merge($transformDefaults, (array)$transform) : $transform);
            }
            
            return $r;
        }
        
        // get pathsmodel for image
        $pathsModel = new Imager_ImagePathsModel($image);

        // create imagine instance
        $this->imagineInstance = $this->_createImagineInstance();

        /**
         * Check all the things that could go wrong(tm)
         */
        if (!IOHelper::getRealPath($pathsModel->sourcePath)) {
            $msg = Craft::t('Source folder “{sourcePath}” does not exist', array('sourcePath' => $pathsModel->sourcePath));
            
            if (craft()->imager->getSetting('suppressExceptions')===true) {
                ImagerPlugin::log($msg, LogLevel::Error);
                return null;
            } else {
                throw new Exception($msg);
            }
        }

        if (!IOHelper::getRealPath($pathsModel->targetPath)) {
            IOHelper::createFolder($pathsModel->targetPath, craft()->config->get('defaultFolderPermissions'), true);

            if (!IOHelper::getRealPath($pathsModel->targetPath)) {
                $msg = Craft::t('Target folder “{targetPath}” does not exist and could not be created', array('targetPath' => $pathsModel->targetPath));
                
                if (craft()->imager->getSetting('suppressExceptions')===true) {
                    ImagerPlugin::log($msg, LogLevel::Error);
                    return null;
                } else {
                    throw new Exception($msg);
                }
            }

            $pathsModel->targetPath = IOHelper::getRealPath($pathsModel->targetPath);
        }

        if ($pathsModel->targetPath && !IOHelper::isWritable($pathsModel->targetPath)) {
            $msg = Craft::t('Target folder “{targetPath}” is not writeable', array('targetPath' => $pathsModel->targetPath));
            
            if (craft()->imager->getSetting('suppressExceptions')===true) {
                ImagerPlugin::log($msg, LogLevel::Error);
                return null;
            } else {
                throw new Exception($msg);
            }            
        }

        if (!IOHelper::fileExists($pathsModel->sourcePath . $pathsModel->sourceFilename)) {
            $msg = Craft::t('Requested image “{fileName}” does not exist in path “{sourcePath}”', array('fileName' => $pathsModel->sourceFilename, 'sourcePath' => $pathsModel->sourcePath));
            
            if (craft()->imager->getSetting('suppressExceptions')===true) {
                ImagerPlugin::log($msg, LogLevel::Error);
                return null;
            } else {
                throw new Exception($msg);
            }    
        }

        if (!craft()->images->checkMemoryForImage($pathsModel->sourcePath . $pathsModel->sourceFilename)) {
            $msg = Craft::t("Not enough memory available to perform this image operation.");

            if (craft()->imager->getSetting('suppressExceptions')===true) {
                ImagerPlugin::log($msg, LogLevel::Error);
                return null;
            } else {
                throw new Exception($msg);
            }    
        }
        
        /**
         * Transform can be either an array or just an object.
         * Act accordingly and return the results the same way to the template.
         */
        $r = null;

        if (isset($transform[0])) {
            $transformedImages = array();
            foreach ($transform as $t) {
                $transformedImage = $this->_getTransformedImage($pathsModel, $transformDefaults != null ? array_merge($transformDefaults, $t) : $t);
                $transformedImages[] = $transformedImage;
            }
            $r = $transformedImages;
        } else {
            $transformedImage = $this->_getTransformedImage($pathsModel, $transformDefaults != null ? array_merge($transformDefaults, (array)$transform) : $transform);
            $r = $transformedImage;
        }

        $this->imageInstance = null;

        /**
         * If this was an ajax request, and optimization tasks were created, trigger them now.
         */
        if (craft()->request->isAjaxRequest() && $this->taskCreated && $this->getSetting('runTasksImmediatelyOnAjaxRequests')) {
            $this->_triggerTasksNow();
        }

        if (count($this->invalidatePaths) > 0) {
            craft()->imager_aws->invalidateCloudfrontPaths($this->invalidatePaths);
            $this->invalidatePaths = array();
        }

        return $r;
    }

    /**
     * Fills in the missing transform objects
     * 
     * @param array $transforms
     * @return array
     */
    private function _fillTransforms($transforms) {
        $r = array();
        
        $attributeName = craft()->imager->getSetting('fillAttribute');
        $interval = craft()->imager->getSetting('fillInterval');
        
        $r[] = $transforms[0];
        
        for ($i = 1, $l = count($transforms); $i<$l; $i++) {
            $prevTransform = $transforms[$i-1];
            $currentTransform = $transforms[$i];
            
            if (isset($prevTransform[$attributeName]) && isset($currentTransform[$attributeName])) {
                if ($prevTransform[$attributeName] < $currentTransform[$attributeName]) {
                    for($num = $prevTransform[$attributeName] + $interval, $maxNum = $currentTransform[$attributeName]; $num<$maxNum; $num = $num + $interval) {
                        $transformCopy = $prevTransform;
                        $transformCopy[$attributeName] = $num;
                        $r[] = $transformCopy;
                    }
                } else {
                    for($num = $prevTransform[$attributeName] - $interval, $minNum = $currentTransform[$attributeName]; $num>$minNum; $num = $num - $interval) {
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
     * Loads an image from a file system path, do transform, return transformed image as an Imager_ImageModel
     *
     * @param Imager_ImagePathsModel $paths
     * @param array $transform
     *
     * @throws Exception
     * @return Imager_ImageModel
     */
    private function _getTransformedImage($paths, $transform)
    {
        if ($this->getSetting('noop')) {
            return new Imager_ImageModel($paths->sourcePath, $paths->sourceUrl, $paths, $transform);
        }

        // break up the image filename to get extension and actual filename 
        $pathParts = pathinfo($paths->targetFilename);

        if (isset($pathParts['extension'])) {
            $sourceExtension = $targetExtension = $pathParts['extension'];
        } else {
            $sourceExtension = $targetExtension = FileHelper::getExtensionByMimeType(mime_content_type($paths->sourcePath . $paths->sourceFilename));
        }

        $filename = $pathParts['filename'];

        // do we want to output in a certain format?
        if (isset($transform['format'])) {
            $targetExtension = $transform['format'];
        }

        // normalize the transform before doing anything more 
        $transform = $this->normalizeTransform($transform, $paths);

        // create target filename, path and url
        $targetFilename = $this->_createTargetFilename($filename, $targetExtension, $transform);
        $targetFilePath = $paths->targetPath . $targetFilename;
        $targetFileUrl = $paths->targetUrl . $targetFilename;

        // set save options
        $saveOptions = $this->_getSaveOptions($targetExtension, $transform);

        /**
         * Check if the image already exists, if caching is turned on or if the cache has expired.
         */

        if (!$this->getSetting('cacheEnabled', $transform) ||
          !IOHelper::fileExists($targetFilePath) ||
          (($this->getSetting('cacheDuration', $transform) !== false) && (IOHelper::getLastTimeModified($targetFilePath)->format('U') + $this->getSetting('cacheDuration', $transform) < time()))
        ) {
            // create the imageInstance. only once if reuse is enabled, or always
            if (!$this->getSetting('instanceReuseEnabled', $transform) || $this->imageInstance == null) {
                $this->imageInstance = $this->imagineInstance->open($paths->sourcePath . $paths->sourceFilename);
            }
            
            // check if this is an animated gif and we're using Imagick
            $animated = false;
            
            if ($sourceExtension === 'gif')
            {
                if ($this->imageDriver!=='gd' && $this->imageInstance->layers())
                {
                    $animated = true;
                }
            }
            
            // Run tranforms, either on each layer of an animated gif, or on the whole image.
            if ($animated) {
                $this->imageInstance->layers()->coalesce();
                
                // we need to create a new image instance with the target size, or letterboxing will be wrong.
                $originalSize = $this->imageInstance->getSize();
                $resizeSize = $this->getResizeSize($originalSize, $transform);
                $layers = $this->imageInstance->layers();
                $gif = $this->imagineInstance->create($resizeSize);
                $gif->layers()->remove(0);
                
                $startFrame = 0;
                $endFrame = count($layers)-1; 
                $interval = 1; 

                if (isset($transform['frames'])) {
                    $framesIntArr = explode('@', $transform['frames']);
                    
                    if (count($framesIntArr)>1) {
                        $interval = $framesIntArr[1];
                    }
                    
                    $framesArr = explode('-', $framesIntArr[0]);
                    
                    if (count($framesArr)>1) {
                        $startFrame = $framesArr[0];
                        if ($framesArr[1]!=='*') {
                            $endFrame = $framesArr[1];
                        }
                    } else {
                        $startFrame = $framesArr[0];
                        $endFrame = $framesArr[0];
                    }
                    
                    if ($endFrame>count($layers)-1) {
                        $endFrame = count($layers)-1;
                    }
                } 
                
                for ($i=$startFrame; $i<=$endFrame; $i+=$interval)
                {
                    $layer = $layers[$i];
                    $this->_transformLayer($layer, $transform, $sourceExtension, $targetExtension);
    				$gif->layers()->add($layer);
                }
                
    			$this->imageInstance = $gif;

            } else {
                $this->_transformLayer($this->imageInstance, $transform, $sourceExtension, $targetExtension);
            }
            
            // If Image Driver is imagick and removeMetadata is true, remove meta data
            if ($this->imageDriver === 'imagick' && $this->getSetting('removeMetadata', $transform)) {
                $this->imageInstance->strip();
            }

            // Convert the image to RGB before converting to webp/saving
            if ($this->getSetting('convertToRGB', $transform)) {
                $this->imageInstance->usePalette(new \Imagine\Image\Palette\RGB());
            }

            // save the transform
            if ($targetExtension === 'webp') {
                if ($this->hasSupportForWebP()) {
                    $this->_saveAsWebp($this->imageInstance, $targetFilePath, $sourceExtension, $saveOptions);
                } else {
                    throw new Exception(Craft::t('This version of {imageDriver} does not support the webp format. You should use “craft.imager.serverSupportsWebp” in your templates to test for it.',
                      array('imageDriver' => $this->imageDriver == 'gd' ? 'GD' : 'Imagick')));
                }
            } else {
                $this->imageInstance->save($targetFilePath, $saveOptions);
            }

            // if file was created, check if optimization should be done
            if (IOHelper::fileExists($targetFilePath)) {
                if ($targetExtension === 'jpg' || $targetExtension === 'jpeg') {
                    if ($this->getSetting('jpegoptimEnabled', $transform)) {
                        $this->postOptimize('jpegoptim', $targetFilePath);
                    }
                    if ($this->getSetting('jpegtranEnabled', $transform)) {
                        $this->postOptimize('jpegtran', $targetFilePath);
                    }
                    if ($this->getSetting('mozjpegEnabled', $transform)) {
                        $this->postOptimize('mozjpeg', $targetFilePath);
                    }
                }

                if ($targetExtension === 'png') {
                    if ($this->getSetting('optipngEnabled', $transform)) {
                        $this->postOptimize('optipng', $targetFilePath);
                    }
                    if ($this->getSetting('pngquantEnabled', $transform)) {
                        $this->postOptimize('pngquant', $targetFilePath);
                    }
                }

                if ($targetExtension === 'gif') {
                    if ($this->getSetting('gifsicleEnabled', $transform)) {
                        $this->postOptimize('gifsicle', $targetFilePath);
                    }
                }

                if ($this->getSetting('tinyPngEnabled', $transform)) {
                    $this->postOptimize('tinypng', $targetFilePath);
                }

                // Upload to AWS if enabled
                if ($this->getSetting('awsEnabled')) {
                    craft()->imager_aws->uploadToAWS($targetFilePath, $this->_checkIsFinalVersion($transform));

                    // Invalidate cloudfront distribution if enabled
                    if ($this->getSetting('cloudfrontInvalidateEnabled')) {
                        $parsedUrl = parse_url($targetFileUrl);
                        $this->invalidatePaths[] = $parsedUrl['path'];
                    }
                }

                // if GCS is enabled, upload file
                if (craft()->imager->getSetting('gcsEnabled')) {
                    craft()->imager_gcs->uploadToGCS($targetFilePath, $this->_checkIsFinalVersion($transform));
                }
            }
        }

        // create Imager_ImageModel for transformed image
        $imagerImage = new Imager_ImageModel($targetFilePath, $targetFileUrl, $paths, $transform);

        return $imagerImage;
    }

    /**
     * Apply transforms to an image or layer.
     * 
     * @param $layer
     * @param array $transform
     * @param string $sourceExtension
     * @param string $targetExtension
     */
    private function _transformLayer(&$layer, $transform, $sourceExtension, $targetExtension)
    {
        // Apply any pre resize filters
        if (isset($transform['preEffects'])) {
            $this->_applyImageEffects($layer, $transform['preEffects']);
        }

        // Get size and crop information
        $originalSize = $layer->getSize();
        $cropSize = $this->getCropSize($originalSize, $transform);
        $resizeSize = $this->getResizeSize($originalSize, $transform);
        $filterMethod = $this->_getFilterMethod($transform);

        // Do the resize
        if ($this->imageDriver === 'imagick' && $this->getSetting('smartResizeEnabled', $transform) && version_compare(craft()->getVersion(), '2.5', '>=')) {
            $layer->smartResize($resizeSize, (bool)craft()->config->get('preserveImageColorProfiles'), $this->getSetting('jpegQuality', $transform));
        } else {
            $layer->resize($resizeSize, $filterMethod);
        }

        // Do the crop
        if (!isset($transform['mode']) || mb_strtolower($transform['mode']) === 'crop' || mb_strtolower($transform['mode']) === 'croponly') {
            $cropPoint = $this->_getCropPoint($resizeSize, $cropSize, $transform);
            $layer->crop($cropPoint, $cropSize);
        }

        // letterbox, add padding
        if (isset($transform['mode']) && mb_strtolower($transform['mode']) === 'letterbox') {
            $this->_applyLetterbox($layer, $transform);
        }

        // Apply post resize filters
        if (isset($transform['effects'])) {
            $this->_applyImageEffects($layer, $transform['effects']);
        }

        // Interlace if true
        if ($this->getSetting('interlace', $transform)) {
            $interlaceVal = $this->getSetting('interlace', $transform);

            if (is_string($interlaceVal)) {
                $layer->interlace(ImagerService::$interlaceKeyTranslate[$interlaceVal]);
            } else {
                $layer->interlace(ImagerService::$interlaceKeyTranslate['line']);
            }
        }

        // apply watermark if enabled
        if (isset($transform['watermark'])) {
            $this->_applyWatermark($layer, $transform['watermark']);
        }

        // apply background color if enabled and applicable
        if (($sourceExtension !== $targetExtension) && ($sourceExtension !== 'jpg') && ($targetExtension === 'jpg') && ($this->getSetting('bgColor', $transform) !== '')) {
            $this->_applyBackgroundColor($layer, $this->getSetting('bgColor', $transform));
        }
    }
    
    /**
     * Creates the target filename
     *
     * @param string $filename
     * @param string $extension
     * @param string $transformFileString
     * @return string
     */
    private function _createTargetFilename($filename, $extension, $transform)
    {
        $hashFilename = $this->getSetting('hashFilename', $transform);

        // generate unique string base on transform
        $transformFileString = $this->_createTransformFilestring($transform);
        $configOverridesString = $this->configModel->getConfigOverrideString();

        if ($hashFilename) {
            if (is_string($hashFilename)) {
                if ($hashFilename == 'postfix') {
                    return $filename . '_' . md5($transformFileString . $configOverridesString) . '.' . $extension;
                } else {
                    return md5($filename . $transformFileString . $configOverridesString) . '.' . $extension;
                }
            } else {
                return md5($filename . $transformFileString . $configOverridesString) . '.' . $extension;
            }
        } else {
            return $filename . $transformFileString . $configOverridesString . '.' . $extension;
        }
    }


    /**
     * Normalize transform object and values
     *
     * @param $paths
     * @param $transform
     * @return mixed
     */
    public function normalizeTransform($transform, $paths = null)
    {
        // if resize mode is not crop or croponly, remove position
        if (isset($transform['mode']) && (($transform['mode'] != 'crop') && ($transform['mode'] != 'croponly'))) {
            if (isset($transform['position'])) {
                unset($transform['position']);
            }
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
        if (isset($transform['ratio']) and (is_float($transform['ratio']) or is_int($transform['ratio']))) {
            if (isset($transform['width']) && !isset($transform['height'])) {
                $transform['height'] = round($transform['width'] / $transform['ratio']);
                unset($transform['ratio']);
            } else {
                if (isset($transform['height']) && !isset($transform['width'])) {
                    $transform['width'] = round($transform['height'] * $transform['ratio']);
                    unset($transform['ratio']);
                } else {
                    // neither is set, use width from original image
                    if ($paths !== null) {
                        $originalSize = getimagesize($paths->sourcePath . $paths->sourceFilename);
                        $transform['width'] = $originalSize[0];
                        $transform['height'] = round($transform['width'] / $transform['ratio']);
                        unset($transform['ratio']);
                    }
                }
            }
        }

        // remove format, this is already in the extension, if we have
        if (isset($transform['format']) && $paths !== null) {
            unset($transform['format']);
        }

        // if transform is in Craft's named version, convert to percentage
        if (isset($transform['position'])) {
            if (isset(ImagerService::$craftPositionTranslate[$transform['position']])) {
                $transform['position'] = ImagerService::$craftPositionTranslate[$transform['position']];
            }

            $transform['position'] = str_replace('%', '', $transform['position']);
        }

        // sort keys to get them in the same order 
        ksort($transform);

        // Move certain keys around abit to make the filename a bit more sane when viewed unencoded
        $transform = $this->_moveArrayKeyToPos('mode', 0, $transform);
        $transform = $this->_moveArrayKeyToPos('height', 0, $transform);
        $transform = $this->_moveArrayKeyToPos('width', 0, $transform);
        $transform = $this->_moveArrayKeyToPos('preEffects', 99, $transform);
        $transform = $this->_moveArrayKeyToPos('effects', 99, $transform);
        $transform = $this->_moveArrayKeyToPos('watermark', 99, $transform);

        return $transform;
    }


    /**
     * Creates additional file string that is appended to filename
     *
     * @param $transform
     * @return string
     */
    private function _createTransformFilestring($transform)
    {
        $r = '';

        foreach ($transform as $k => $v) {
            if ($k == 'effects' || $k == 'preEffects') {
                $effectString = '';
                foreach ($v as $eff => $param) {
                    if (is_array($param)) {
                        if (is_array($param[0])) {
                            $effectString .= '_' . $eff;
                            foreach ($param as $paramArr) {
                                $effectString .= '-' . implode('-', $paramArr);
                            }
                        } else {
                            $effectString .= '_' . $eff . '-' . implode('-', $param);
                        }
                    } else {
                        $effectString .= '_' . $eff . '-' . $param;
                    }
                }

                $r .= '_' . (isset(ImagerService::$transformKeyTranslate[$k]) ? ImagerService::$transformKeyTranslate[$k] : $k) . $effectString;
            } else {
                if ($k == 'watermark') {
                    $watermarkString = '';
                    foreach ($v as $eff => $param) {
                        $watermarkString .= $eff . '-' . (is_array($param) ? implode("-", $param) : $param);
                    }

                    $r .= '_' . (isset(ImagerService::$transformKeyTranslate[$k]) ? ImagerService::$transformKeyTranslate[$k] : $k) . '_' . substr(md5($watermarkString),
                        0, 10);
                } elseif ($k == 'webpImagickOptions') {
                    $optString = '';
                    foreach ($v as $optK => $optV) {
                        $optString .= ($optK . '-' . $optV . '-');
                    }

                    $r .= '_' . (isset(ImagerService::$transformKeyTranslate[$k]) ? ImagerService::$transformKeyTranslate[$k] : $k) . '_' . substr($optString, 0, strlen($optString) - 1);
                } else {
                    $r .= '_' . (isset(ImagerService::$transformKeyTranslate[$k]) ? ImagerService::$transformKeyTranslate[$k] : $k) . (is_array($v) ? implode("-",
                        $v) : $v);
                }
            }
        }

        return str_replace(array('#', '(', ')'), '', str_replace(array(' ', '.', ','), '-', $r));
    }


    /**
     * Creates the destination crop size box
     *
     * @param \Imagine\Image\Box $originalSize
     * @param $transform
     * @return \Imagine\Image\Box
     */
    public function getCropSize($originalSize, $transform)
    {
        $width = $originalSize->getWidth();
        $height = $originalSize->getHeight();
        $aspect = $width / $height;

        if (isset($transform['width']) and isset($transform['height'])) {
            $width = (int)$transform['width'];
            $height = (int)$transform['height'];
        } else {
            if (isset($transform['width'])) {
                $width = (int)$transform['width'];
                $height = floor((int)$transform['width'] / $aspect);
            } else {
                if (isset($transform['height'])) {
                    $width = floor((int)$transform['height'] * $aspect);
                    $height = (int)$transform['height'];
                }
            }
        }

        // check if we want to upscale. If not, adjust the transform here 
        if (!$this->getSetting('allowUpscale', $transform)) {
            list($width, $height) = $this->_enforceMaxSize($width, $height, $originalSize, true);
        }
        
        // ensure that size is larger than 0
        if ($width<=0) { $width = 1; }
        if ($height<=0) { $height = 1; }
        
        return new \Imagine\Image\Box($width, $height);
    }


    /**
     * Creates the resize size box
     *
     * @param \Imagine\Image\Box $originalSize
     * @param $transform
     * @return \Imagine\Image\Box
     */
    public function getResizeSize($originalSize, $transform)
    {
        $width = $originalSize->getWidth();
        $height = $originalSize->getHeight();
        $aspect = $width / $height;

        $mode = isset($transform['mode']) ? mb_strtolower($transform['mode']) : 'crop';

        if ($mode == 'crop' || $mode == 'fit' || $mode == 'letterbox') {

            if (isset($transform['width']) and isset($transform['height'])) {
                $transformAspect = (int)$transform['width'] / (int)$transform['height'];

                if ($mode == 'crop') {

                    $cropZoomFactor = $this->_getCropZoomFactor($transform);

                    if ($transformAspect < $aspect) { // use height as guide
                        $height = (int)$transform['height'] * $cropZoomFactor;
                        $width = ceil($originalSize->getWidth() * ($height / $originalSize->getHeight()));
                    } else { // use width
                        $width = (int)$transform['width'] * $cropZoomFactor;
                        $height = ceil($originalSize->getHeight() * ($width / $originalSize->getWidth()));
                    }

                } else {
                    
                    if ($transformAspect === $aspect) {
                        $height = (int)$transform['height'];
                        $width = (int)$transform['width'];
                    } elseif ($transformAspect > $aspect) { // use height as guide
                        $height = (int)$transform['height'];
                        $width = ceil($originalSize->getWidth() * ($height / $originalSize->getHeight()));
                    } else { // use width
                        $width = (int)$transform['width'];
                        $height = ceil($originalSize->getHeight() * ($width / $originalSize->getWidth()));
                    }

                }

            } else {
                if (isset($transform['width'])) {

                    $width = (int)$transform['width'];
                    $height = ceil($width / $aspect);

                } else {
                    if (isset($transform['height'])) {

                        $height = (int)$transform['height'];
                        $width = ceil($height * $aspect);

                    }
                }
            }

        } else {
            if ($mode == 'croponly') {
                $width = $originalSize->getWidth();
                $height = $originalSize->getHeight();

            } else {
                if ($mode == 'stretch') {
                    $width = (int)$transform['width'];
                    $height = (int)$transform['height'];
                }
            }
        }

        // check if we want to upscale. If not, adjust the transform here 
        if (!$this->getSetting('allowUpscale', $transform)) {
            list($width, $height) = $this->_enforceMaxSize($width, $height, $originalSize, false,
              $this->_getCropZoomFactor($transform));
        }

        return new \Imagine\Image\Box($width, $height);
    }


    /**
     * Enforces a max size if allowUpscale is false
     *
     * @param $width
     * @param $height
     * @param $originalSize
     * @return array
     */
    private function _enforceMaxSize($width, $height, $originalSize, $maintainAspect, $zoomFactor = 1)
    {
        $adjustedWidth = $width;
        $adjustedHeight = $height;

        if ($adjustedWidth > $originalSize->getWidth() * $zoomFactor) {
            $adjustedWidth = floor($originalSize->getWidth() * $zoomFactor);
            if ($maintainAspect) {
                $adjustedHeight = floor($height * ($adjustedWidth / $width));
            }
        }

        if ($adjustedHeight > $originalSize->getHeight() * $zoomFactor) {

            $adjustedHeight = floor($originalSize->getHeight() * $zoomFactor);
            if ($maintainAspect) {
                $adjustedWidth = floor($width * ($adjustedHeight / $height));
            }
        }

        return array($adjustedWidth, $adjustedHeight);
    }

    /**
     * Get the crop zoom factor
     *
     * @param $transform
     * @return float|int
     */
    private function _getCropZoomFactor($transform)
    {
        if (isset($transform['cropZoom'])) {
            return (float)$transform['cropZoom'];
        }
        return 1;
    }


    /**
     * Gets crop point
     *
     * @param \Imagine\Image\Box $resizeSize
     * @param \Imagine\Image\Box $cropSize
     * @param $transform
     * @return \Imagine\Image\Point
     */
    private function _getCropPoint($resizeSize, $cropSize, $transform)
    {
        // get default crop position from the settings
        $position = $this->getSetting('position', $transform);

        // get the offsets, left and top, now as an int, representing the % offset
        list($leftOffset, $topOffset) = explode(' ', $position);
        
        // get position that crop should center around
        $leftPos = floor($resizeSize->getWidth() * ($leftOffset / 100)) - floor($cropSize->getWidth()/2);
        $topPos = floor($resizeSize->getHeight() * ($topOffset / 100)) - floor($cropSize->getHeight()/2);
        
        // make sure the point is within the boundaries and return the point
        return new \Imagine\Image\Point(
            min(max($leftPos, 0), ($resizeSize->getWidth() - $cropSize->getWidth())), 
            min(max($topPos, 0), ($resizeSize->getHeight() - $cropSize->getHeight()))
        );
    }


    /**
     * Returns the filter method for resize operations
     *
     * @return string
     */
    private function _getFilterMethod($transform)
    {
        return $this->imageDriver == 'imagick' ? ImagerService::$filterKeyTranslate[$this->getSetting('resizeFilter',
          $transform)] : \Imagine\Image\ImageInterface::FILTER_UNDEFINED;
    }


    /**
     * Get the save options based on extension and transform
     *
     * @param $extension
     * @param $transform
     * @return array
     */
    private function _getSaveOptions($extension, $transform)
    {
        switch (strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                return array('jpeg_quality' => $this->getSetting('jpegQuality', $transform));
                break;
            case 'gif':
                return array('flatten' => false);
                break;
            case 'png':
                return array('png_compression_level' => $this->getSetting('pngCompressionLevel', $transform));
                break;
            case 'webp':
                return array('webp_quality' => $this->getSetting('webpQuality', $transform), 'webp_imagick_options' => $this->getSetting('webpImagickOptions', $transform));
                break;
        }
        return array();
    }

    /**
     * Apply watermark to image
     *
     * @param $imageInstance
     * @param $effects
     */
    private function _applyWatermark($imageInstance, $watermark)
    {
        if (!isset($watermark['image'])) {
            throw new Exception(Craft::t('Watermark image property not set'));
        }

        if (!(isset($watermark['width']) && isset($watermark['width']))) {
            throw new Exception(Craft::t('Watermark image size is not set'));
        }

        $paths = new Imager_ImagePathsModel($watermark['image']);
        $watermarkInstance = $this->imagineInstance->open($paths->sourcePath . $paths->sourceFilename);
        $watermarkInstance->resize(new \Imagine\Image\Box($watermark['width'], $watermark['height']),
          \Imagine\Image\ImageInterface::FILTER_UNDEFINED);

        if (isset($watermark['position'])) {
            $position = $watermark['position'];

            if (isset($position['top'])) {
                $posY = intval($position['top']);
            } else {
                if (isset($position['bottom'])) {
                    $posY = $imageInstance->getSize()->getHeight() - intval($watermark['height']) - intval($position['bottom']);
                } else {
                    $posY = $imageInstance->getSize()->getHeight() - intval($watermark['height']) - 10;
                }
            }

            if (isset($position['left'])) {
                $posX = intval($position['left']);
            } else {
                if (isset($position['right'])) {
                    $posX = $imageInstance->getSize()->getWidth() - intval($watermark['width']) - intval($position['right']);
                } else {
                    $posX = $imageInstance->getSize()->getWidth() - intval($watermark['width']) - 10;
                }
            }
        } else {
            $posY = $imageInstance->getSize()->getHeight() - intval($watermark['height']) - 10;
            $posX = $imageInstance->getSize()->getWidth() - intval($watermark['width']) - 10;
        }

        $positionPoint = new \Imagine\Image\Point($posX, $posY);

        if ($this->imageDriver == 'imagick') {
            $watermarkImagick = $watermarkInstance->getImagick();

            if (isset($watermark['opacity'])) {
                $watermarkImagick->evaluateImage(\Imagick::EVALUATE_MULTIPLY, floatval($watermark['opacity']),
                  \Imagick::CHANNEL_ALPHA);
            }

            if (isset($watermark['blendMode']) && isset(ImagerService::$compositeKeyTranslate[$watermark['blendMode']])) {
                $blendMode = ImagerService::$compositeKeyTranslate[$watermark['blendMode']];
            } else {
                $blendMode = \Imagick::COMPOSITE_ATOP;
            }

            $imageInstance->getImagick()->compositeImage($watermarkImagick, $blendMode, $positionPoint->getX(),
              $positionPoint->getY());

        } else { // it's GD :(
            $imageInstance->paste($watermarkInstance, $positionPoint);
        }
    }

    /**
     * Apply letterbox to image
     *
     * @param $imageInstance
     * @param $transform
     */
    private function _applyLetterbox(\Imagine\Image\ImageInterface &$imageInstance, $transform)
    {
        if (isset($transform['width']) and isset($transform['height'])) { // if both isn't set, there's no need for a letterbox
            $letterboxDef = $this->getSetting('letterbox', $transform);

            $size = new \Imagine\Image\Box($transform['width'], $transform['height']);
            $position = new \Imagine\Image\Point(
              floor(((int)$transform['width'] - $imageInstance->getSize()->getWidth()) / 2),
              floor(((int)$transform['height'] - $imageInstance->getSize()->getHeight()) / 2)
            );

            $palette = new \Imagine\Image\Palette\RGB();
            $color = $palette->color(
              isset($letterboxDef['color']) ? $letterboxDef['color'] : '#000',
              isset($letterboxDef['opacity']) ? (int)($letterboxDef['opacity'] * 100) : 0
            );

            $backgroundImage = $this->imagineInstance->create($size, $color);
            $backgroundImage->paste($imageInstance, $position);
            $imageInstance = $backgroundImage;
        }
    }

    /**
     * Apply background color to image when converting from transparent to non-transparent
     *
     * @param \Imagine\Image\ImageInterface $imageInstance
     * @param $bgColor
     */
    private function _applyBackgroundColor(\Imagine\Image\ImageInterface &$imageInstance, $bgColor)
    {
        $palette = new \Imagine\Image\Palette\RGB();
        $color = $palette->color($bgColor);
        $topLeft = new \Imagine\Image\Point(0, 0);
        $backgroundImage = $this->imagineInstance->create($imageInstance->getSize(), $color);
        $backgroundImage->paste($imageInstance, $topLeft);
        $imageInstance = $backgroundImage;
    }

    /**
     * Saves image as webp
     *
     * @param $imageInstance
     * @param $path
     * @param $sourceExtension
     * @param $saveOptions
     * @throws Exception
     */
    private function _saveAsWebp($imageInstance, $path, $sourceExtension, $saveOptions)
    {
        if ($this->getSetting('useCwebp')) {

            // save temp file
            $tempFile = $this->_saveTemporaryFile($imageInstance, $sourceExtension);

            // convert to webp with cwebp
            $command = escapeshellcmd($this->getSetting('cwebpPath') . ' ' . $this->getSetting('cwebpOptions') . ' -q ' . $saveOptions['webp_quality'] . ' ' . $tempFile . ' -o ' . $path);
            $r = shell_exec($command);

            if (!IOHelper::fileExists($path)) {
                throw new Exception(Craft::t('Save operation failed'));
            }

            // delete temp file
            IOHelper::deleteFile($tempFile);

        } else {
            if ($this->imageDriver === 'gd') {
                $instance = $imageInstance->getGdResource();

                if (false === \imagewebp($instance, $path, $saveOptions['webp_quality'])) {
                    throw new Exception(Craft::t('Save operation failed'));
                }

                // Fix for corrupt file bug (http://stackoverflow.com/questions/30078090/imagewebp-php-creates-corrupted-webp-files)
                if (filesize($path) % 2 == 1) {
                    file_put_contents($path, "\0", FILE_APPEND);
                }
            }

            if ($this->imageDriver === 'imagick') {
                $instance = $imageInstance->getImagick();

                $instance->setImageFormat('webp');
                
                $hasTransparency = $instance->getImageAlphaChannel();

                if($hasTransparency){
                    $instance->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
                    $instance->setBackgroundColor(new \ImagickPixel('transparent'));
                }
                
                $instance->setImageCompressionQuality($saveOptions['webp_quality']);

                $imagickOptions = $saveOptions['webp_imagick_options'];

                if ($imagickOptions && count($imagickOptions) > 0) {
                    foreach ($imagickOptions as $key => $val) {
                        $instance->setOption('webp:' . $key, $val);
                    }
                }

                $instance->writeImage($path);
            }
        }
    }

    /**
     * Checks for webp support in image driver
     *
     * @return bool
     */
    public function hasSupportForWebP()
    {
        if ($this->imageDriver === 'gd' && function_exists('imagewebp')) {
            return true;
        }

        if ($this->imageDriver === 'imagick' && (count(\Imagick::queryformats('WEBP')) > 0)) {
            return true;
        }

        if ($this->getSetting('useCwebp') && $this->getSetting('cwebpPath') !== '' && file_exists($this->getSetting('cwebpPath'))) {
            return true;
        }

        return false;
    }


    /**
     * Save temporary file and return filename
     *
     * @param $imageInstance
     * @param $sourceExtension
     * @return string
     */
    private function _saveTemporaryFile($imageInstance, $sourceExtension)
    {
        $tempPath = craft()->path->getRuntimePath() . 'imager/temp/';

        // check if the path exists
        if (!IOHelper::getRealPath($tempPath)) {
            IOHelper::createFolder($tempPath, craft()->config->get('defaultFolderPermissions'), true);

            if (!IOHelper::getRealPath($tempPath)) {
                throw new Exception(Craft::t('Temp folder “{tempPath}” does not exist and could not be created',
                  array('tempPath' => $tempPath)));
            }
        }

        $targetFilePath = $tempPath . md5(time()) . '.' . $sourceExtension;

        $saveOptions = array(
          'jpeg_quality' => 100,
          'png_compression_level' => 1,
          'flatten' => true
        );

        $imageInstance->save($targetFilePath, $saveOptions);

        return $targetFilePath;
    }


    /**
     * ---- Effects ------------------------------------------------------------------------------------------------------
     */

    /**
     * Apply effects array to image instance
     *
     * @param $imageInstance
     * @param $effects
     */
    private function _applyImageEffects($imageInstance, $effects)
    {
        // apply effects in the order they were entered
        foreach ($effects as $effect => $value) {

            $effect = mb_strtolower($effect);

            /**
             * For GD we only apply effects that exists in Imagine
             */
            if ($this->imageDriver === 'gd') {
                if (($effect == 'grayscale' || $effect == 'greyscale') && $value) { 
                    $imageInstance->effects()->grayscale();
                }

                if ($effect == 'negative' && $value) {
                    $imageInstance->effects()->negative();
                }

                if ($effect == 'blur') {
                    $imageInstance->effects()->blur(is_int($value) || is_float($value) ? $value : 1);
                }

                if ($effect == 'sharpen' && $value) {
                    $imageInstance->effects()->sharpen();
                }

                if ($effect == 'gamma') {
                    $imageInstance->effects()->gamma(is_int($value) || is_float($value) ? $value : 1);
                }

                if ($effect == 'colorize') {
                    $color = $imageInstance->palette()->color($value);
                    $imageInstance->effects()->colorize($color);
                }
            }

            /**
             * For Imagick, we apply all effects manually. 
             * Built-in effects in Imagine is not used since they don't work with animated gif layers.
             */
            if ($this->imageDriver == 'imagick') {
                $imagickInstance = $imageInstance->getImagick();
                
                if (($effect === 'grayscale' || $effect === 'greyscale') && $value) {
                    $hasTransparency = $imagickInstance->getImageAlphaChannel();

                    $imagickInstance->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
                    
                    if($hasTransparency){
                        $imagickInstance->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
                        $imagickInstance->setBackgroundColor(new \ImagickPixel('transparent'));
                    }                
                }

                if ($effect === 'negative' && $value) {
                    $imagickInstance->negateImage(false, \Imagick::CHANNEL_ALL);
                }

                if ($effect === 'blur') {
                    $imagickInstance->gaussianBlurImage(0, is_int($value) || is_float($value) ? $value : 1);
                }

                if ($effect === 'sharpen' && $value) {
                    $imagickInstance->sharpenImage(2, 1);
                }

                if ($effect === 'gamma') {
                    $imagickInstance->gammaImage(is_int($value) || is_float($value) ? $value : 1, \Imagick::CHANNEL_ALL);
                }

                if ($effect === 'colorize') {
                    $color = $imageInstance->palette()->color($value);
                    $imagickInstance = $imageInstance->getImagick();
                    $imagickInstance->colorizeImage((string)$color, new \ImagickPixel(sprintf('rgba(%d, %d, %d, 1)', $color->getRed(), $color->getGreen(), $color->getBlue())));
                }
                
                // colorBlend is almost like colorize, but works with alpha and use blend modes.
                if ($effect == 'colorblend') {

                    if (is_array($value)) {
                        if (count($value) > 1) {

                            $this->_colorBlend($imagickInstance, $value[0], $value[1]);
                        } else {
                            $this->_colorBlend($imagickInstance, $value[0], 1);
                        }
                    } else {
                        $this->_colorBlend($imagickInstance, $value, 1);
                    }
                }

                // sepia, just because it's there.
                if ($effect == 'sepia') {
                    $imagickInstance->sepiaToneImage($value);
                }

                // tint
                if ($effect == 'tint' && is_array($value)) {
                    $tint = new \ImagickPixel($value[0]);
                    $opacity = new \ImagickPixel($value[1]);

                    $imagickInstance->tintImage($tint, $opacity);
                }

                // contrast
                if ($effect == 'contrast') {
                    if (is_int($value)) {
                        for ($i = 0; $i < abs($value); $i++) {
                            if ($value > 0) {
                                $imagickInstance->contrastImage(true);
                            } else {
                                $imagickInstance->contrastImage(false);
                            }
                        }
                    } else {
                        $imagickInstance->contrastImage($value);
                    }
                }

                // modulate
                if ($effect == 'modulate' && is_array($value) && count($value) == 3) {
                    $imagickInstance->modulateImage($value[0], $value[1], $value[2]);
                }

                // normalize
                if ($effect == 'normalize') {
                    $imagickInstance->normalizeImage();
                }

                // contrast stretch
                if ($effect == 'contraststretch' && is_array($value) && count($value) >= 2) {
                    $imagickInstance->contrastStretchImage($value[0], $value[1]);
                }

                // posterize
                if ($effect == 'posterize' && is_array($value) && count($value) == 2) {
                    $imagickInstance->posterizeImage($value[0], isset(ImagerService::$ditherKeyTranslate[$value[1]]) ? ImagerService::$ditherKeyTranslate[$value[1]] : \Imagick::DITHERMETHOD_NO);
                }

                // unsharpMask
                if ($effect == 'unsharpmask' && is_array($value) && count($value) == 4) {
                    $imagickInstance->unsharpMaskImage($value[0], $value[1], $value[2], $value[3]);
                }
                
                // clut
                if ($effect == 'clut' && is_string($value)) {
                    $clut = new \Imagick();
                    $clut->newPseudoImage(1, 255, $value);
                    $imagickInstance->clutImage($clut);
                }

                // levels
                if ($effect == 'levels' && is_array($value)) {
                    if (is_array($value[0])) {
                        foreach ($value as $val) {
                            if (count($val)>=3) {
                                $this->_applyLevels($imagickInstance, $val);
                            }
                        }
                    } else {
                        if (count($value)>=3) {
                            $this->_applyLevels($imagickInstance, $value);
                        }
                    }
                    
                }

                // quantize
                if ($effect == 'quantize' && (is_array($value) || is_int($value))) {
                    if (is_array($value) && count($value) === 3) {
                        $imagickInstance->quantizeImage($value[0], \Imagick::COLORSPACE_RGB, $value[1], $value[2], false);
                    } else {
                        $imagickInstance->quantizeImage($value, \Imagick::COLORSPACE_RGB, 0, false, false);
                    }
                }

                // vignette
                // todo : make a better vignette
                if ($effect == 'vignette' && is_array($value) && count($value) >= 3) {
                    $this->_vignette($imagickInstance, $value[0], $value[1], $value[2]);
                }

                // custom filter
                if ($effect == 'customfilter') {
                    $this->_applyCustomFilter($imagickInstance, $value);
                }

            }
        }
    }

    /**
     * Applies a custom predefined filter to image
     *
     * Heavily inspired by Dejan Marjanovics article http://code.tutsplus.com/tutorials/create-instagram-filters-with-php--net-24504
     *
     * TODO : Move this out into seperate plugins through events?
     *
     * @param $imagick
     * @param $filterName
     */
    private function _applyCustomFilter($imagick, $filterName)
    {
        $filterName = mb_strtolower($filterName);

        if ($filterName == 'gotham') {
            $imagick->modulateImage(120, 10, 100);
            $imagick->colorizeImage('#222b96', 1);
            $imagick->gammaImage(0.6);
            $imagick->contrastImage(10);
        }

        if ($filterName == 'toaster') {

            $this->_colortone($imagick, '#330000', 100, 0);
            $imagick->modulateImage(158, 80, 100);
            $imagick->gammaImage(1.1);
            $imagick->contrastImage(-100);

            $this->_vignette($imagick, 'none', 'LavenderBlush3');
            $this->_vignette($imagick, '#ff9966', 'none');
        }

    }

    private function _applyLevels($imagickInstance, $value) {
        $quantum = $imagickInstance->getQuantum();
        $blackLevel = ($value[0]/255)*$quantum;
        $whiteLevel = ($value[2]/255)*$quantum;
        $channel = \Imagick::CHANNEL_ALL;
        
        if (count($value)>3) {
            switch ($value[3]) {
                case 'red':
                    $channel = \Imagick::CHANNEL_RED;
                    break;
                case 'blue':
                    $channel = \Imagick::CHANNEL_BLUE;
                    break;
                case 'green':
                    $channel = \Imagick::CHANNEL_GREEN;
                    break;
            }
        }
        
        $imagickInstance->levelImage($blackLevel, $value[1], $whiteLevel, $channel);
    }

    /**
     * Color blend filter, more advanced version of colorize.
     *
     * Code by olav@redwall.ee on http://php.net/manual/en/imagick.colorizeimage.php
     *
     * @param $imageInstance
     * @param $color
     * @param int $alpha
     * @param int $composite_flag
     */
    private function _colorBlend($imagickInstance, $color, $alpha = 1, $composite_flag = \Imagick::COMPOSITE_COLORIZE)
    {
        $draw = new \ImagickDraw();
        $draw->setFillColor($color);

        $width = $imagickInstance->getImageWidth();
        $height = $imagickInstance->getImageHeight();

        $draw->rectangle(0, 0, $width, $height);

        $temporary = new \Imagick();
        $temporary->setBackgroundColor(new \ImagickPixel('transparent'));
        $temporary->newImage($width, $height, new \ImagickPixel('transparent'));
        $temporary->setImageFormat('png32');
        $temporary->drawImage($draw);

        if (method_exists($imagickInstance, 'setImageClipMask')) { // ImageMagick < 7
            $alphaChannel = clone $imagickInstance;
            $alphaChannel->setImageAlphaChannel(\Imagick::ALPHACHANNEL_EXTRACT);
            $alphaChannel->negateImage(false, \Imagick::CHANNEL_ALL);
            $imagickInstance->setImageClipMask($alphaChannel);
        } else {
            // need to figure out how to add support for maintaining opacity in ImageMagick 7
        }

        $clone = clone $imagickInstance;
        $clone->compositeImage($temporary, $composite_flag, 0, 0);

        if (method_exists($clone, 'setImageAlpha')) { // ImageMagick >= 7
            $clone->setImageAlpha($alpha);
        } else {
            $clone->setImageOpacity($alpha);
        }
        
        $imagickInstance->compositeImage($clone, \Imagick::COMPOSITE_DEFAULT, 0, 0);
    }


    /**
     * Create a vignette
     *
     * Heavily inspired by Dejan Marjanovics article http://code.tutsplus.com/tutorials/create-instagram-filters-with-php--net-24504
     *
     * @param $imagickInstance
     * @param string $color1
     * @param string $color2
     * @param float $crop_factor
     */

    private function _vignette($imagickInstance, $color1 = 'none', $color2 = 'black', $crop_factor = 1.5)
    {
        $vignetteWidth = floor($imagickInstance->getImageWidth() * $crop_factor);
        $vignetteHeight = floor($imagickInstance->getImageHeight() * $crop_factor);

        $radial = new \Imagick();
        $radial->newPseudoImage($vignetteWidth, $vignetteHeight, "radial-gradient:$color1-$color2");
        $radial->setImageFormat('png32');

        $imagickInstance->compositeImage($radial, \imagick::COMPOSITE_MULTIPLY,
          -($vignetteWidth - $imagickInstance->getImageWidth()) / 2,
          -($vignetteHeight - $imagickInstance->getImageHeight()) / 2);
    }


    /**
     * ---- Optimizations ------------------------------------------------------------------------------------------------------
     */

    /**
     * Set up optimization
     *
     * @param $type
     * @param $file
     */
    public function postOptimize($type, $file)
    {
        if ($this->getSetting('optimizeType') == 'task') {
            switch ($type) {
                case 'jpegoptim':
                    $this->makeTask('Imager_Jpegoptim', $file);
                    break;
                case 'jpegtran':
                    $this->makeTask('Imager_Jpegtran', $file);
                    break;
                case 'mozjpeg':
                    $this->makeTask('Imager_Mozjpeg', $file);
                    break;
                case 'optipng':
                    $this->makeTask('Imager_Optipng', $file);
                    break;
                case 'pngquant':
                    $this->makeTask('Imager_Pngquant', $file);
                    break;
                case 'gifsicle':
                    $this->makeTask('Imager_Gifsicle', $file);
                    break;
                case 'tinypng':
                    $this->makeTask('Imager_TinyPng', $file);
                    break;
            }
        } else {
            switch ($type) {
                case 'jpegoptim':
                    $this->runJpegoptim($file);
                    break;
                case 'jpegtran':
                    $this->runJpegtran($file);
                    break;
                case 'mozjpeg':
                    $this->runMozjpeg($file);
                    break;
                case 'optipng':
                    $this->runOptipng($file);
                    break;
                case 'pngquant':
                    $this->runPngquant($file);
                    break;
                case 'gifsicle':
                    $this->runGifsicle($file);
                    break;
                case 'tinypng':
                    $this->runTinyPng($file);
                    break;
            }
        }
    }

    /**
     * Run jpegoptim optimization
     *
     * @param $file
     * @param $transform
     */
    public function runJpegoptim($file)
    {
        if ($this->getSetting('skipExecutableExistCheck') || file_exists($this->getSetting('jpegoptimPath'))) {
            $cmd = $this->getSetting('jpegoptimPath');
            $cmd .= ' ';
            $cmd .= $this->getSetting('jpegoptimOptionString');
            $cmd .= ' ';
            $cmd .= '"'.$file.'"';
    
            $this->executeOptimize($cmd, $file);
        } else {
            ImagerPlugin::log("jpegoptim could not be found in the supplied path (" . $this->getSetting('jpegoptimPath') . ")", LogLevel::Error);
        }
    }

    /**
     * Run jpegtran optimization
     *
     * @param $file
     * @param $transform
     */
    public function runJpegtran($file)
    {
        if ($this->getSetting('skipExecutableExistCheck') || file_exists($this->getSetting('jpegtranPath'))) {
            $cmd = $this->getSetting('jpegtranPath');
            $cmd .= ' ';
            $cmd .= $this->getSetting('jpegtranOptionString');
            $cmd .= ' -outfile ';
            $cmd .= '"'.$file.'"';
            $cmd .= ' ';
            $cmd .= '"'.$file.'"';
    
            $this->executeOptimize($cmd, $file);
        } else {
            ImagerPlugin::log("jpegtran could not be found in the supplied path (" . $this->getSetting('jpegtranPath') . ")", LogLevel::Error);
        }
    }

    /**
     * Run mozjpeg optimization
     *
     * @param $file
     * @param $transform
     */
    public function runMozjpeg($file)
    {
        if ($this->getSetting('skipExecutableExistCheck') || file_exists($this->getSetting('mozjpegPath'))) {
            $cmd = $this->getSetting('mozjpegPath');
            $cmd .= ' ';
            $cmd .= $this->getSetting('mozjpegOptionString');
            $cmd .= ' -outfile ';
            $cmd .= '"'.$file.'"';
            $cmd .= ' ';
            $cmd .= '"'.$file.'"';
    
            $this->executeOptimize($cmd, $file);
        } else {
            ImagerPlugin::log("mozjpeg could not be found in the supplied path (" . $this->getSetting('mozjpegPath') . ")", LogLevel::Error);
        }
    }

    /**
     * Run optipng optimization
     *
     * @param $file
     * @param $transform
     */
    public function runOptipng($file)
    {
        if ($this->getSetting('skipExecutableExistCheck') || file_exists($this->getSetting('optipngPath'))) {
            $cmd = $this->getSetting('optipngPath');
            $cmd .= ' ';
            $cmd .= $this->getSetting('optipngOptionString');
            $cmd .= ' ';
            $cmd .= '"'.$file.'"';
    
            $this->executeOptimize($cmd, $file);
        } else {
            ImagerPlugin::log("optipng could not be found in the supplied path (" . $this->getSetting('optipngPath') . ")", LogLevel::Error);
        }
    }

    /**
     * Run pngquant optimization
     *
     * @param $file
     * @param $transform
     */
    public function runPngquant($file)
    {
        if ($this->getSetting('skipExecutableExistCheck') || file_exists($this->getSetting('pngquantPath'))) {
            $cmd = $this->getSetting('pngquantPath');
            $cmd .= ' ';
            $cmd .= $this->getSetting('pngquantOptionString');
            $cmd .= ' ';
            $cmd .= '-f -o ';
            $cmd .= '"'.$file.'"';
            $cmd .= ' ';
            $cmd .= '"'.$file.'"';
    
            $this->executeOptimize($cmd, $file);
        } else {
            ImagerPlugin::log("pngquant could not be found in the supplied path (" . $this->getSetting('pngquantPath') . ")", LogLevel::Error);
        }
    }

    /**
     * Run gifsicle optimization
     *
     * @param $file
     * @param $transform
     */
    public function runGifsicle($file)
    {
        if ($this->getSetting('skipExecutableExistCheck') || file_exists($this->getSetting('gifsiclePath'))) {
            $cmd = $this->getSetting('gifsiclePath');
            $cmd .= ' ';
            $cmd .= $this->getSetting('gifsicleOptionString');
            $cmd .= ' ';
            $cmd .= '-b ';
            $cmd .= '"'.$file.'"';
            
            $this->executeOptimize($cmd, $file);
        } else {
            ImagerPlugin::log("gifsicle could not be found in the supplied path (" . $this->getSetting('gifsiclePath') . ")", LogLevel::Error);
        }
    }

    /**
     * Runs TinyPNG optimization
     *
     * @param $file
     * @param $transform
     */
    public function runTinyPng($file)
    {
        try {
            \Tinify\setKey($this->getSetting('tinyPngApiKey'));
            \Tinify\validate();
            \Tinify\fromFile($file)->toFile($file);
        } catch (\Tinify\Exception $e) {
            ImagerPlugin::log("Could not validate connection to TinyPNG, image was not optimized.", LogLevel::Error);
        }
    }

    /**
     * Executes a shell command
     *
     * @param $command
     */
    public function executeOptimize($command, $file = '')
    {
        $command = escapeshellcmd($command);
        $r = shell_exec($command);

        if ($this->getSetting('logOptimizations')) {
            ImagerPlugin::log("Optimized image $file \n\n" . $r, LogLevel::Info, true);
			ImagerPlugin::log($command, LogLevel::Info, true);
        }
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
     * @return bool
     */
    public function isAnimated($asset)
    {
        $paths = new Imager_ImagePathsModel($asset);
        $pathParts = pathinfo($paths->sourceFilename);
        $extension  = $pathParts['extension'];
        
        if ($extension!=='gif') {
            return false;
        }

        if(!($fh = @fopen($paths->sourcePath . $paths->sourceFilename, 'rb'))) {
            return false;
        }
        
        $count = 0;
        
        while(!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100); //read 100kb at a time
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
        }
    
        fclose($fh);
        
        return $count > 0; 
    }
    
    /**
     * Registers a Task with Craft, taking into account if there is already one pending
     *
     * @method makeTask
     * @param string $taskName The name of the Task you want to register
     * @param array $paths An array of paths that should go in that Tasks settings
     */
    public function makeTask($taskName, $paths)
    {
        // If there are any pending tasks, just append the paths to it
        $task = craft()->tasks->getNextPendingTask($taskName);

        if ($task && is_array($task->settings)) {
            $settings = $task->settings;
            if (!is_array($settings['paths'])) {
                $settings['paths'] = array($settings['paths']);
            }
            if (is_array($paths)) {
                $settings['paths'] = array_merge($settings['paths'], $paths);
            } else {
                $settings['paths'][] = $paths;
            }

            // Make sure there aren't any duplicate paths
            $settings['paths'] = array_unique($settings['paths']);

            // Set the new settings and save the task
            $task->settings = $settings;
            craft()->tasks->saveTask($task, false);
        } else {
            craft()->tasks->createTask($taskName, null, array(
              'paths' => $paths
            ));
        }

        $this->taskCreated = true;
    }


    /**
     * Method that triggers any pending tasks immediately.
     */
    private function _triggerTasksNow()
    {
        $url = UrlHelper::getActionUrl('tasks/runPendingTasks');

        if (function_exists('curl_init')) {
            $ch = curl_init($url);

            $options = array(
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_CONNECTTIMEOUT => false,
              CURLOPT_NOSIGNAL => true
            );

            if (defined('CURLOPT_TIMEOUT_MS')) {
                $options[CURLOPT_TIMEOUT_MS] = 500;
            } else {
                $options[CURLOPT_TIMEOUT] = 1;
            }

            curl_setopt_array($ch, $options);
            curl_exec($ch);
            $curlErrorNo = curl_errno($ch);
            $curlError = curl_error($ch);
            $httpStatus = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            curl_close($ch);

            if ($curlErrorNo !== 0) {
                ImagerPlugin::log("Request for running tasks immediately failed with error number $curlErrorNo and error message: $curlError", LogLevel::Error);
            }

            if ($httpStatus !== 200) {
                ImagerPlugin::log("Request for running tasks immediately failed with http status $httpStatus", LogLevel::Error);
            }
        }
    }


    /**
     * ---- Settings ------------------------------------------------------------------------------------------------------
     */

    /**
     * Gets a plugin setting
     *
     * @param $name String Setting name
     * @return mixed Setting value
     * @author André Elvan
     */
    public function getSetting($name, $transform = null)
    {
        if ($this->configModel === null) {
            $this->configModel = new Imager_ConfigModel();
        }

        return $this->configModel->getSetting($name, $transform);
    }


    /**
     * ---- Helper functions -------------------------------------------------------------------------------------------
     */

    /**
     * Moves a named key in an associative array to a given position
     *
     * @param $key
     * @param $value
     * @param $pos
     * @param $arr
     * @return array
     */
    private function _moveArrayKeyToPos($key, $pos, $arr)
    {
        if (!isset($arr[$key])) {
            return $arr;
        }

        $tempValue = $arr[$key];
        unset($arr[$key]);

        if ($pos == 0) {
            return array($key => $tempValue) + $arr;
        }

        if ($pos > count($arr)) {
            return $arr + array($key => $tempValue);
        }

        $new_arr = array();
        $i = 1;

        foreach ($arr as $arr_key => $arr_value) {
            if ($i == $pos) {
                $new_arr[$key] = $tempValue;
            }

            $new_arr[$arr_key] = $arr_value;

            ++$i;
        }

        return $new_arr;
    }

    /**
     * Check if current file is the final version
     *
     * @param $transform
     * @return bool
     */
    private function _checkIsFinalVersion($transform)
    {
        if ($this->getSetting('optimizeType', $transform) == 'task')
        {
            if ($this->getSetting('jpegoptimEnabled', $transform) || $this->getSetting('jpegtranEnabled', $transform) || $this->getSetting('mozjpegEnabled', $transform) || $this->getSetting('optipngEnabled', $transform) || $this->getSetting('pngquantEnabled', $transform) || $this->getSetting('gifsicleEnabled', $transform) || $this->getSetting('tinyPngEnabled', $transform))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Fixes slashes in path
     *
     * @param $str
     * @param bool|false $removeInitial
     * @param bool|false $removeTrailing
     * @return mixed|string
     */
    static function fixSlashes($str, $removeInitial = false, $removeTrailing = false)
    {
        $r = str_replace('//', '/', $str);

        if (strlen($r) > 0) {
            if ($removeInitial && ($r[0] == '/')) {
                $r = substr($r, 1);
            }

            if ($removeTrailing && ($r[strlen($r) - 1] == '/')) {
                $r = substr($r, 0, strlen($r) - 1);
            }
        }

        return $r;
    }


}
