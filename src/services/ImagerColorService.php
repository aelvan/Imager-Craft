<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\imager\services;

use craft\base\Component;
use craft\elements\Asset;

use aelvan\imager\models\LocalSourceImageModel;
use aelvan\imager\exceptions\ImagerException;

use ColorThief\ColorThief;

/**
 * ImagerColorService Service
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    André Elvan
 * @package   Imager
 * @since     2.0.0
 */
class ImagerColorService extends Component
{
    /**
     * Get dominant color of image
     *
     * @param Asset|string $image
     * @param int          $quality
     * @param string       $colorValue
     *
     * @return string|array|boolean|null
     */
    public function getDominantColor($image, $quality, $colorValue)
    {
        try {
            $source = new LocalSourceImageModel($image);
            $source->getLocalCopy();
        } catch (ImagerException $e) {
            return null;
        }

        $dominantColor = ColorThief::getColor($source->getFilePath(), $quality);
        
        ImagerService::cleanSession();

        if (!\is_array($dominantColor)) {
            return null;
        }
        
        return $colorValue === 'hex' ? self::rgb2hex($dominantColor) : $dominantColor;
    }

    /**
     * Gets color palette for image
     *
     * @param Asset|string $image
     * @param int      $colorCount
     * @param int      $quality
     * @param string       $colorValue
     *
     * @return array|null
     */
    public function getColorPalette($image, $colorCount, $quality, $colorValue)
    {
        try {
            $source = new LocalSourceImageModel($image);
            $source->getLocalCopy();
        } catch (ImagerException $e) {
            return null;
        }

        $palette = ColorThief::getPalette($source->getFilePath(), $colorCount, $quality);

        ImagerService::cleanSession();
        
        return $colorValue === 'hex' ? $this->paletteToHex($palette) : $palette;
    }

    /**
     * Convert rgb color to hex
     *
     * @param array $rgb
     *
     * @return string
     */
    public static function rgb2hex($rgb): string
    {
        return '#'.sprintf('%02x', $rgb[0]).sprintf('%02x', $rgb[1]).sprintf('%02x', $rgb[2]);
    }

    /**
     * Convert hex color to rgb
     *
     * @param string $hex
     *
     * @return array
     */
    public static function hex2rgb($hex): array
    {
        $hex = str_replace('#', '', $hex);

        if (\strlen($hex) === 3) {
            $r = hexdec($hex[0].$hex[0]);
            $g = hexdec($hex[1].$hex[1]);
            $b = hexdec($hex[2].$hex[2]);
        } else {
            $r = hexdec($hex[0].$hex[1]);
            $g = hexdec($hex[2].$hex[3]);
            $b = hexdec($hex[4].$hex[5]);
        }

        return [$r, $g, $b];
    }

    /**
     * Convert palette to array of hex colors
     *
     * @param array $palette
     *
     * @return array
     */
    private function paletteToHex($palette): array
    {
        $r = [];
        foreach ($palette as $paletteColor) {
            $r[] = self::rgb2hex($paletteColor);
        }

        return $r;
    }
}
