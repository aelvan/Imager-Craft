<?php

namespace aelvan\imager\effects;

use aelvan\imager\services\ImagerService;
use Imagine\Gd\Image as GdImage;
use Imagine\Imagick\Image as ImagickImage;
use Imagine\Imagick\Imagick;

class LevelsEffect implements ImagerEffectsInterface
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

            if (\is_array($params)) {
                if (\is_array($params[0])) {
                    foreach ($params as $val) {
                        if (\count($val) >= 3) {
                            self::applyLevels($imagickInstance, $val);
                        }
                    }
                } else {
                    if (\count($params) >= 3) {
                        self::applyLevels($imagickInstance, $params);
                    }
                }
            }
        }
    }

    /**
     * @param Imagick $imagickInstance
     * @param $value
     */
    private static function applyLevels($imagickInstance, $value) {
        $quantum = $imagickInstance->getQuantum();
        $blackLevel = ($value[0]/255)*$quantum;
        $whiteLevel = ($value[2]/255)*$quantum;
        $channel = \Imagick::CHANNEL_ALL;
        
        if (\count($value)>3) {
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
}
