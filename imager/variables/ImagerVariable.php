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

class ImagerVariable
{
    /**
     * Transforms an image 
     * 
     * @param $file
     * @param $transform
     * @param $configOverrides
     * @return mixed
     */
    public function transformImage($file, $transform, $transformDefaults = null, $configOverrides = null)
    {
        $image = craft()->imager->transformImage($file, $transform, $transformDefaults, $configOverrides);
        return $image;
    }

    /**
     * Takes an array of Imager_ImageModel (or anything else that supports getUrl() and getWidth())
     * and returns a srcset string
     *
     * todo : Implement support for other descriptors
     *
     * @param Array $images
     * @param string $descriptor
     * @return string
     */
    public function srcset($images, $descriptor = 'w')
    {
        $r = '';

        foreach ($images as $image) {
            $r .= $image->getUrl() . ' ' . $image->getWidth() . 'w, ';
        }

        return substr($r, 0, strlen($r) - 2);
    }

    /**
     * Returns a base64 encoded transparent pixel. Useful for adding as src on img tags for validation when using srcset.
     * 
     * @return string
     */
    public function base64Pixel($width = 1, $height = 1)
    {
        return "data:image/svg+xml;charset=utf-8,%3Csvg xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg' viewBox%3D'0 0 $width $height'%2F%3E";
    }

    /**
     * Gets the dominant color of an image
     * 
     * @param $image
     * @param string $colorValue
     * @param int $quality
     * @return mixed
     */
    public function getDominantColor($image, $quality = 10, $colorValue='hex')
    {
        return craft()->imager->getDominantColor($image, $quality, $colorValue);
    }

    /**
     * Gets a palette of colors from an image 
     * 
     * @param $image
     * @param string $colorValue
     * @param int $colorCount
     * @param int $quality
     * @return mixed
     */
    public function getColorPalette($image, $colorCount = 8, $quality = 10, $colorValue='hex')
    {
        return craft()->imager->getColorPalette($image, $colorCount, $quality, $colorValue);
    }

    /**
     * Converts a hex color value to rgb
     * 
     * @param string $color
     * @return array
     */
    public function hex2rgb($color) 
    {
        return ImagerService::hex2rgb($color);
    }
    
    /**
     * Converts a rgb color value to hex
     * 
     * @param array $color
     * @return string
     */
    public function rgb2hex($color) 
    {
        return ImagerService::rgb2hex($color);
    }
}