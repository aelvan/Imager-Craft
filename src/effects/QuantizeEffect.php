<?php

namespace aelvan\imager\effects;

use aelvan\imager\services\ImagerService;
use Imagine\Gd\Image as GdImage;
use Imagine\Imagick\Image as ImagickImage;
use Imagine\Imagick\Imagick;

class QuantizeEffect implements ImagerEffectsInterface
{

    /**
     * @param GdImage|ImagickImage             $imageInstance
     * @param array|string|int|float|bool|null $params
     */
    public static function apply($imageInstance, $params)
    {
        if (ImagerService::$imageDriver === 'imagick') {
            /** @var ImagickImage $imageInstance */
            $imagickInstance = $imageInstance->getImagick();
            
            if (\is_array($params) && \count($params) === 3) {
                $imagickInstance->quantizeImage($params[0], \Imagick::COLORSPACE_RGB, $params[1], $params[2], false);
            } else if (\is_int($params)) {
                $imagickInstance->quantizeImage($params, \Imagick::COLORSPACE_RGB, 0, false, false);
            }
        }
    }
}
