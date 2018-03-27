<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\imager\variables;

use Craft;

use aelvan\imager\Imager as Plugin;
use aelvan\imager\services\ImagerColorService;
use aelvan\imager\services\ImagerService;
use \aelvan\imager\exceptions\ImagerException;
use craft\elements\Asset;

class ImagerVariable
{
    /**
     * Transforms an image
     *
     * @param Asset|string $file
     * @param array        $transform
     * @param array        $transformDefaults
     * @param array        $configOverrides
     *
     * @return mixed
     *
     * @throws ImagerException
     */
    public function transformImage($file, $transform, $transformDefaults = null, $configOverrides = null)
    {
        $image = Plugin::$plugin->imager->transformImage($file, $transform, $transformDefaults, $configOverrides);

        return $image;
    }

    /**
     * Takes an array of models that supports getUrl() and getWidth(), and returns a srcset
     * and returns a srcset string
     *
     * @param array  $images
     * @param string $descriptor
     *
     * @return string
     */
    public function srcset($images, $descriptor = 'w'): string
    {
        return Plugin::$plugin->imager->srcset($images, $descriptor);
    }

    /**
     * Returns a base64 encoded transparent pixel.
     *
     * @param int    $width
     * @param int    $height
     * @param string $color
     *
     * @return string
     */
    public function base64Pixel($width = 1, $height = 1, $color = 'transparent'): string
    {
        return 'data:image/svg+xml;charset=utf-8,'.rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' width='$width' height='$height' style='background:$color'/>");
    }

    /**
     * Returns an image placeholder.
     *
     * @param array|null $config
     *
     * @return string
     * @throws ImagerException
     */
    public function placeholder($config = null): string
    {
        return Plugin::$plugin->placeholder->placeholder($config);
    }

    /**
     * Gets the dominant color of an image
     *
     * @param Asset|string $image
     * @param string       $colorValue
     * @param int          $quality
     *
     * @return mixed
     */
    public function getDominantColor($image, $quality = 10, $colorValue = 'hex')
    {
        return Plugin::$plugin->color->getDominantColor($image, $quality, $colorValue);
    }

    /**
     * Gets a palette of colors from an image
     *
     * @param Asset|string $image
     * @param string       $colorValue
     * @param int          $colorCount
     * @param int          $quality
     *
     * @return mixed
     */
    public function getColorPalette($image, $colorCount = 8, $quality = 10, $colorValue = 'hex')
    {
        return Plugin::$plugin->color->getColorPalette($image, $colorCount, $quality, $colorValue);
    }

    /**
     * Converts a hex color value to rgb
     *
     * @param string $color
     *
     * @return array
     */
    public function hex2rgb($color): array
    {
        return ImagerColorService::hex2rgb($color);
    }

    /**
     * Converts a rgb color value to hex
     *
     * @param array $color
     *
     * @return string
     */
    public function rgb2hex($color): string
    {
        return ImagerColorService::rgb2hex($color);
    }

    /**
     * Checks for webp support in image driver
     *
     * @return bool
     */
    public function serverSupportsWebp(): bool
    {
        return ImagerService::hasSupportForWebP();
    }

    /**
     * Checks for webp support in browser
     *
     * @return bool
     */
    public function clientSupportsWebp(): bool
    {
        $request = Craft::$app->getRequest();

        return $request->accepts('image/webp');
    }

    /**
     * Checks if asset is animated (only gif support atm)
     *
     * @param Asset|string $asset
     *
     * @return bool
     *
     * @throws ImagerException
     */
    public function isAnimated($asset): bool
    {
        return Plugin::$plugin->imager->isAnimated($asset);
    }

    /**
     * Checks if Imgix is enabled
     *
     * @return bool
     */
    public function imgixEnabled(): bool
    {
        return Plugin::$plugin->getSettings()->transformer === 'imgix';
    }
}
