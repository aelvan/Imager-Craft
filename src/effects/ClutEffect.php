<?php

namespace aelvan\imager\effects;

use aelvan\imager\services\ImagerService;
use Imagine\Gd\Image as GdImage;
use Imagine\Imagick\Image as ImagickImage;
use Imagine\Imagick\Imagick;

class ClutEffect implements ImagerEffectsInterface
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
            
            if (\is_string($params)) {
                $clut = new \Imagick();
                $clut->newPseudoImage(1, 255, $params);
                $imagickInstance->clutImage($clut);
            }
        }
    }
}
