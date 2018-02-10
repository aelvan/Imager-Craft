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

use Craft;

use craft\base\Component;
use craft\elements\Asset;

use aelvan\imager\Imager;
use aelvan\imager\models\LocalSourceImageModel;

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
     * @param AssetFileModel|string $image
     * @param                       $quality
     * @param                       $colorValue
     *
     * @return bool|string
     * @throws Exception
     */
    public function getDominantColor($image, $quality, $colorValue)
    {
        $source = new LocalSourceImageModel($image);
        $source->getLocalCopy();

        $dominantColor = ColorThief::getColor($source->getFilePath(), $quality);

        return $colorValue === 'hex' ? self::rgb2hex($dominantColor) : $dominantColor;
    }

    /**
     * Gets color palette for image
     *
     * @param Asset|string $image
     * @param integer      $colorCount
     * @param integer      $quality
     * @param string       $colorValue
     *
     * @return array
     * @throws Exception
     */
    public function getColorPalette($image, $colorCount, $quality, $colorValue)
    {
        $source = new LocalSourceImageModel($image);
        $source->getLocalCopy();

        $palette = ColorThief::getPalette($source->getFilePath(), $colorCount, $quality);

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