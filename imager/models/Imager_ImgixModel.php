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

class Imager_ImgixModel extends BaseModel
{

    /**
     * Imager_ImageModel constructor.
     *
     * @param null $imageUrl
     * @param null $source
     * @param null $params
     * @param null $transform
     */
    public function __construct($imageUrl = null, $source = null, $params = null)
    {
        $this['path'] = '';
        $this['extension'] = '';
        $this['mimeType'] = '';
        $this['size'] = '';

        if ($imageUrl !== null) {
            $this['url'] = $imageUrl;
        }

        $this['width'] = 0;
        $this['height'] = 0;

        if (isset($params['w']) && isset($params['h'])) {
            if (($params['fit'] === 'min' || $params['fit'] === 'max') && ($source !== null)) {
                list($sourceWidth, $sourceHeight) = $this->getSourceImageDimensions($source, $params);

                $paramsW = (int)$params['w'];
                $paramsH = (int)$params['h'];

                if ($sourceWidth / $sourceHeight < $paramsW / $paramsH) {
                    $useW = min($paramsW, $sourceWidth);
                    $this['width'] = $useW;
                    $this['height'] = round($useW * ($paramsW / $paramsH));
                } else {
                    $useH = min($paramsH, $sourceHeight);
                    $this['width'] = round($useH * ($paramsW / $paramsH));
                    $this['height'] = $useH;
                }
            } else {
                $this['width'] = (int)$params['w'];
                $this['height'] = (int)$params['h'];
            }
        } else {
            if (isset($params['w']) || isset($params['h'])) {

                if ($source !== null && $params !== null) {
                    list($sourceWidth, $sourceHeight) = $this->getSourceImageDimensions($source, $params);

                    if ($sourceWidth === 0 || $sourceHeight === 0) {
                        if (isset($params['w'])) {
                            $this['width'] = (int)$params['w'];
                        }
                        if (isset($params['h'])) {
                            $this['height'] = (int)$params['h'];
                        }
                    } else {
                        list($w, $h) = $this->calculateTargetSize($params, $sourceWidth, $sourceHeight);

                        $this['width'] = $w;
                        $this['height'] = $h;
                    }
                }
            } else {
                // todo : neither is set, image is not resized. What to do?
            }
        }
    }

    protected function defineAttributes()
    {
        return [
            'path' => [AttributeType::String],
            'url' => [AttributeType::String],
            'extension' => [AttributeType::String],
            'mimeType' => [AttributeType::String],
            'width' => [AttributeType::Number],
            'height' => [AttributeType::Number],
            'size' => [AttributeType::Number],
        ];
    }

    protected function getSourceImageDimensions($source, $params)
    {
        if ($source instanceof \Craft\AssetFileModel) {
            return [$source->getWidth(), $source->getHeight()];
        } 
        
        if (craft()->imager->getSetting('imgixGetExternalImageDimensions')) {
            $pathsModel = new Imager_ImagePathsModel($source);
            $sourceImageInfo = @getimagesize($pathsModel->sourcePath.$pathsModel->sourceFilename);
            return [$sourceImageInfo[0], $sourceImageInfo[1]];
        }

        return [0, 0];
    }

    protected function calculateTargetSize($params, $sourceWidth, $sourceHeight)
    {
        $fit = $params['fit']; // clamp, clip, crop, facearea, fill, fillmax, max, min, and scale. 
        $ratio = $sourceWidth / $sourceHeight;

        $w = isset($params['w']) ? $params['w'] : null;
        $h = isset($params['h']) ? $params['h'] : null;

        switch ($fit) {
            case 'clip':
            case 'fill':
            case 'crop':
            case 'clamp':
            case 'scale':
                if ($w) {
                    return [$w, round($w / $ratio)];
                }
                if ($h) {
                    return [round($h * $ratio), $h];
                }
                break;
            case 'min':
            case 'max':
                if ($w) {
                    $useWidth = min($w, $sourceWidth);

                    return [$useWidth, round($useWidth / $ratio)];
                }
                if ($h) {
                    $useHeigth = min($h, $sourceHeight);

                    return [round($useHeigth * $ratio), $useHeigth];
                }
                break;
        }

        return [$w ? $w : 0, $h ? $h : 0];
    }

    public function __toString()
    {
        return (string)$this->url;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getSize($unit = 'b', $precision = 2)
    {
        return $this->size;
    }

    public function getDataUri()
    {
        return '';
    }

    public function getBase64Encoded()
    {
        return '';
    }

}
