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

use ColorThief\ColorThief;

class Imager_ColorService extends BaseApplicationComponent
{

    /**
     * Constructor
     */
    public function __construct()
    {
        
    }

    /**
     * Get dominant color of image
     *
     * @param AssetFileModel|string $image
     * @param $quality
     * @param $colorValue
     * @return bool|string
     * @throws Exception
     */
    public function getDominantColor($image, $quality, $colorValue)
    {
        $pathsModel = new Imager_ImagePathsModel($image);

        if (!IOHelper::getRealPath($pathsModel->sourcePath)) {
            $msg = Craft::t('Source folder “{sourcePath}” does not exist', array('sourcePath' => $pathsModel->sourcePath));
            
            if (craft()->imager->getSetting('suppressExceptions')===true) {
                ImagerPlugin::log($msg, LogLevel::Error);
                return null;
            } else {
                throw new Exception($msg);
            }
        }

        if (!IOHelper::fileExists($pathsModel->sourcePath . $pathsModel->sourceFilename)) {
            $msg = Craft::t('Requested image “{fileName}” does not exist in path “{sourcePath}”', array('fileName' => $pathsModel->sourceFilename, 'sourcePath' => $pathsModel->sourcePath));
            
            if (craft()->imager->getSetting('suppressExceptions')===true) {
                ImagerPlugin::log($msg, LogLevel::Error);
                return null;
            } else {
                throw new Exception($msg);
            }
        }

        $dominantColor = ColorThief::getColor($pathsModel->sourcePath . $pathsModel->sourceFilename, $quality);
        return $colorValue == 'hex' ? Imager_ColorService::rgb2hex($dominantColor) : $dominantColor;
    }

    /**
     * Gets color palette for image
     *
     * @param AssetFileModel|string $image
     * @param $colorCount
     * @param $quality
     * @param $colorValue
     * @return array
     * @throws Exception
     */
    public function getColorPalette($image, $colorCount, $quality, $colorValue)
    {
        $pathsModel = new Imager_ImagePathsModel($image);

        if (!IOHelper::getRealPath($pathsModel->sourcePath)) {
            $msg = Craft::t('Source folder “{sourcePath}” does not exist', array('sourcePath' => $pathsModel->sourcePath));
            
            if (craft()->imager->getSetting('suppressExceptions')===true) {
                ImagerPlugin::log($msg, LogLevel::Error);
                return null;
            } else {
                throw new Exception($msg);
            }
            
        }

        if (!IOHelper::fileExists($pathsModel->sourcePath . $pathsModel->sourceFilename)) {
            $msg = Craft::t('Requested image “{fileName}” does not exist in path “{sourcePath}”', array('fileName' => $pathsModel->sourceFilename, 'sourcePath' => $pathsModel->sourcePath));
            
            if (craft()->imager->getSetting('suppressExceptions')===true) {
                ImagerPlugin::log($msg, LogLevel::Error);
                return null;
            } else {
                throw new Exception($msg);
            }
        }

        $palette = ColorThief::getPalette($pathsModel->sourcePath . $pathsModel->sourceFilename, $colorCount, $quality);

        return $colorValue == 'hex' ? $this->_paletteToHex($palette) : $palette;
    }
    
    /**
     * Convert rgb color to hex
     *
     * @param array $rgb
     * @return string
     */
    static function rgb2hex($rgb)
    {
        return '#' . sprintf('%02x', $rgb[0]) . sprintf('%02x', $rgb[1]) . sprintf('%02x', $rgb[2]);
    }

    /**
     * Convert hex color to rgb
     *
     * @param string $hex
     * @return array
     */
    static function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec($hex[0] . $hex[0]);
            $g = hexdec($hex[1] . $hex[1]);
            $b = hexdec($hex[2] . $hex[2]);
        } else {
            $r = hexdec($hex[0] . $hex[1]);
            $g = hexdec($hex[2] . $hex[3]);
            $b = hexdec($hex[4] . $hex[5]);
        }

        return array($r, $g, $b);
    }
    
    /**
     * Convert palette to array of hex colors
     * 
     * @param $palette
     * @return array
     */
    private function _paletteToHex($palette)
    {
        $r = array();
        foreach ($palette as $paletteColor) {
            array_push($r, Imager_ColorService::rgb2hex($paletteColor));
        }
        return $r;
    }

}
