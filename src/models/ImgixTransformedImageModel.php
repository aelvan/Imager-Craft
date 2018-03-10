<?php

namespace aelvan\imager\models;

use craft\elements\Asset;
use craft\helpers\FileHelper;

use Imagine\Image\Box;

use aelvan\imager\helpers\ImagerHelpers;
use aelvan\imager\services\ImagerService;
use aelvan\imager\exceptions\ImagerException;

class ImgixTransformedImageModel implements TransformedImageInterface
{
    public $path;
    public $filename;
    public $url;
    public $extension;
    public $mimeType;
    public $width;
    public $height;
    public $size;

    private $profileConfig;

    /**
     * ImgixTransformedImageModel constructor.
     *
     * @param string|null        $imageUrl
     * @param Asset|string|null  $source
     * @param array|null         $params
     * @param ImgixSettings|null $config
     *
     * @throws ImagerException
     */
    public function __construct($imageUrl = null, $source = null, $params = null, $config = null)
    {
        $this->profileConfig = $config;

        $this->path = '';
        $this->extension = '';
        $this->mimeType = '';
        $this->size = 0;

        if ($imageUrl !== null) {
            $this->url = $imageUrl;
        }

        $this->width = 0;
        $this->height = 0;


        if (isset($params['w'], $params['h'])) {
            if (($source !== null) && ($params['fit'] === 'min' || $params['fit'] === 'max')) {
                list($sourceWidth, $sourceHeight) = $this->getSourceImageDimensions($source);

                $paramsW = (int)$params['w'];
                $paramsH = (int)$params['h'];

                if ($sourceWidth / $sourceHeight < $paramsW / $paramsH) {
                    $useW = min($paramsW, $sourceWidth);
                    $this->width = $useW;
                    $this->height = round($useW * ($paramsW / $paramsH));
                } else {
                    $useH = min($paramsH, $sourceHeight);
                    $this->width = round($useH * ($paramsW / $paramsH));
                    $this->height = $useH;
                }
            } else {
                $this->width = (int)$params['w'];
                $this->height = (int)$params['h'];
            }
        } else {
            if (isset($params['w']) || isset($params['h'])) {

                if ($source !== null && $params !== null) {
                    list($sourceWidth, $sourceHeight) = $this->getSourceImageDimensions($source);

                    if ((int)$sourceWidth === 0 || (int)$sourceHeight === 0) {
                        if (isset($params['w'])) {
                            $this->width = (int)$params['w'];
                        }
                        if (isset($params['h'])) {
                            $this->height = (int)$params['h'];
                        }
                    } else {
                        list($w, $h) = $this->calculateTargetSize($params, $sourceWidth, $sourceHeight);

                        $this->width = $w;
                        $this->height = $h;
                    }
                }
            } else {
                // todo : neither is set, image is not resized. What to do?
            }
        }
    }

    /**
     * @param $source
     *
     * @return array
     * @throws ImagerException
     */
    protected function getSourceImageDimensions($source): array
    {
        if ($source instanceof Asset) {
            return [$source->getWidth(), $source->getHeight()];
        }

        if ($this->profileConfig !== null && $this->profileConfig->getExternalImageDimensions) {
            $sourceModel = new LocalSourceImageModel($source);
            $sourceModel->getLocalCopy();

            $sourceImageInfo = @getimagesize($sourceModel->getFilePath());

            return [$sourceImageInfo[0], $sourceImageInfo[1]];
        }

        return [0, 0];
    }

    /**
     * @param $params
     * @param $sourceWidth
     * @param $sourceHeight
     *
     * @return array
     */
    protected function calculateTargetSize($params, $sourceWidth, $sourceHeight): array
    {
        $fit = $params['fit']; // clamp, clip, crop, facearea, fill, fillmax, max, min, and scale. 
        $ratio = $sourceWidth / $sourceHeight;

        $w = $params['w'] ?? null;
        $h = $params['h'] ?? null;

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

        return [$w ?: 0, $h ?: 0];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return (int)$this->width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return (int)$this->height;
    }

    /**
     * @param string $unit
     * @param int    $precision
     *
     * @return float|int
     */
    public function getSize($unit = 'b', $precision = 2)
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getDataUri(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getBase64Encoded(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->url;
    }
}
