<?php

namespace aelvan\imager\effects;

use aelvan\imager\services\ImagerService;
use Imagine\Gd\Image as GdImage;
use Imagine\Imagick\Image as ImagickImage;
use Imagine\Imagick\Imagick;

class ContrastEffect implements ImagerEffectsInterface
{

    /**
     * @param GdImage|ImagickImage        $imageInstance
     * @param array|string|int|float|null $params
     */
    public static function apply($imageInstance, $params)
    {
        if (ImagerService::$imageDriver === 'imagick') {
            /** @var ImagickImage $imageInstance */
            $imagickInstance = $imageInstance->getImagick();
            
            if (\is_int($params)) {
                $numLoops = abs($params);
                for ($i = 0; $i < $numLoops; $i++) {
                    if ($params > 0) {
                        $imagickInstance->contrastImage(true);
                    } else {
                        $imagickInstance->contrastImage(false);
                    }
                }
            } else {
                $imagickInstance->contrastImage($params);
            }
        }
    }
}
