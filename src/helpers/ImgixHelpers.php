<?php

namespace aelvan\imager\helpers;

use Craft;

use aelvan\imager\models\ImgixSettings;
use craft\base\LocalVolumeInterface;
use craft\base\Volume;

use craft\elements\Asset;
use craft\helpers\FileHelper;
use craft\volumes\Local;
use aelvan\imager\exceptions\ImagerException;

use yii\base\InvalidConfigException;

class ImgixHelpers
{
    /**
     * @param Asset|string $image
     * @param ImgixSettings $config
     * @return string
     * @throws ImagerException
     */
    public static function getImgixFilePath($image, $config): string
    {
        if (\is_string($image)) { // if $image is a string, just pass it to builder, we have to assume the user knows what he's doing (sry) :)
            return $image;
        } 
        
        if ($config->sourceIsWebProxy === true) {
            return $image->url ?? '';
        } 
            
        try {
            /** @var LocalVolumeInterface|Volume|Local $volume */
            $volume = $image->getVolume();
        } catch (InvalidConfigException $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }

        if (($config->useCloudSourcePath === true) && isset($volume->subfolder) && \get_class($volume) !== 'craft\volumes\Local') {
            $path = implode('/', [\Craft::parseEnv($volume->subfolder), $image->getPath()]);
        } else {
            $path = $image->getPath();
        }
        
        if ($config->addPath) {
            if (\is_string($config->addPath) && $config->addPath !== '') {
                $path = implode('/', [$config->addPath, $path]);
            } else if (is_array($config->addPath)) {
                if (isset($config->addPath[$volume->handle])) {
                    $path = implode('/', [$config->addPath[$volume->handle], $path]);
                }
            }
        }
        
        $path = FileHelper::normalizePath($path);

        //always use forward slashes for imgix
        $path = str_replace('\\', '/', $path);

        return $path;
    }
    
}
