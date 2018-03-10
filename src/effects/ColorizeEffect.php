<?php

namespace aelvan\imager\effects;

use aelvan\imager\services\ImagerService;
use Imagine\Gd\Image as GdImage;
use Imagine\Image\Palette\Color\RGB;
use Imagine\Imagick\Image as ImagickImage;

class ColorizeEffect implements ImagerEffectsInterface
{

    /**
     * @param GdImage|ImagickImage        $imageInstance
     * @param array|string|int|float|null $params
     *
     * @throws \ImagickException
     * @throws \Imagine\Image\Palette\InvalidArgumentException
     */
    public static function apply($imageInstance, $params)
    {
        if (ImagerService::$imageDriver === 'gd') {
            $color = $imageInstance->palette()->color($params);
            $imageInstance->effects()->colorize($color);
        }

        if (ImagerService::$imageDriver === 'imagick') {
            /** @var ImagickImage $imageInstance */
            $imagickInstance = $imageInstance->getImagick();
            /** @var RGB $color */
            $color = $imageInstance->palette()->color($params);
            $imagickInstance->colorizeImage((string)$color, new \ImagickPixel(sprintf('rgba(%d, %d, %d, 1)', $color->getRed(), $color->getGreen(), $color->getBlue())));        
        }
    }
}
