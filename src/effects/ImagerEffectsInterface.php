<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2018 André Elvan
 */

namespace aelvan\imager\effects;

use Imagine\Gd\Image as GdImage;
use Imagine\Imagick\Image as ImagickImage;

interface ImagerEffectsInterface
{
    /**
     * @param GdImage|ImagickImage $imageInstance
     * @param array|string|int|float|bool|null $params
     */
    public static function apply($imageInstance, $params);
}