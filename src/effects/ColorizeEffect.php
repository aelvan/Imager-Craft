<?php

namespace aelvan\imager\effects;

use aelvan\imager\services\ImagerService;
use Imagine\Gd\Image as GdImage;
use Imagine\Imagick\Image as ImagickImage;

class ColorizeEffect implements ImagerEffectsInterface
{

    /**
     * @param GdImage|ImagickImage        $imageInstance
     * @param array|string|int|float|null $params
     */
    public static function apply($imageInstance, $params)
    {
        if (ImagerService::$imageDriver === 'gd') {
            $color = $imageInstance->palette()->color($params);
            $imageInstance->effects()->colorize($color);
        }

        if (ImagerService::$imageDriver === 'imagick') {
            $imagickInstance = $imageInstance->getImagick();
            $color = $imageInstance->palette()->color($params);
            $imagickInstance->colorizeImage((string)$color, new \ImagickPixel(sprintf('rgba(%d, %d, %d, 1)', $color->getRed(), $color->getGreen(), $color->getBlue())));        
        }
    }
}