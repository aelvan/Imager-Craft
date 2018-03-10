<?php

namespace aelvan\imager\effects;

use aelvan\imager\services\ImagerService;
use Imagine\Gd\Image as GdImage;
use Imagine\Imagick\Image as ImagickImage;
use Imagine\Imagick\Imagick;

class ColorBlendEffect implements ImagerEffectsInterface
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
            
            if (\is_array($params)) {
                if (\count($params) > 1) {

                    self::colorBlend($imagickInstance, $params[0], $params[1]);
                } else {
                    self::colorBlend($imagickInstance, $params[0]);
                }
            } else {
                self::colorBlend($imagickInstance, $params);
            }
        }
    }
    
    /**
     * Color blend filter, more advanced version of colorize.
     *
     * Code by olav@redwall.ee on http://php.net/manual/en/imagick.colorizeimage.php
     *
     * @param Imagick $imagickInstance
     * @param $color
     * @param int|float $alpha
     * @param int $composite_flag
     */
    private static function colorBlend($imagickInstance, $color, $alpha = 1, $composite_flag = \Imagick::COMPOSITE_COLORIZE)
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
}
