<?php

namespace aelvan\imager\models;

use craft\helpers\FileHelper;

use aelvan\imager\helpers\ImagerHelpers;
use aelvan\imager\services\ImagerService;
use aelvan\imager\exceptions\ImagerException;

use Imagine\Exception\InvalidArgumentException;
use Imagine\Image\Box;

use yii\base\InvalidConfigException;

class NoopImageModel implements TransformedImageInterface
{
    public $path;
    public $filename;
    public $url;
    public $extension;
    public $mimeType;
    
    /**
     * @var int
     */
    public $width;
    
    /**
     * @var int
     */
    public $height;
    
    /**
     * @var int|float
     */
    public $size;
    
    /**
     * @var bool
     */
    public $isNew;

    /**
     * Constructor
     *
     * @param LocalSourceImageModel $sourceModel
     * @param array $transform
     *
     * @throws ImagerException
     */
    public function __construct($sourceModel, $transform)
    {
        $this->path = $sourceModel->getFilePath();
        $this->filename = $sourceModel->filename;
        $this->url = $sourceModel->url;
        $this->isNew = false;

        $this->extension = $sourceModel->extension;
        $this->size = @filesize($sourceModel->getFilePath());

        try {
            $this->mimeType = FileHelper::getMimeType($sourceModel->getFilePath());
        } catch (InvalidConfigException $e) {
            // just ignore
        }

        $imageInfo = @getimagesize($sourceModel->getFilePath());

        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        $sourceImageInfo = @getimagesize($sourceModel->getFilePath());

        try {
            $sourceSize = new Box($sourceImageInfo[0], $sourceImageInfo[1]);
            $targetCrop = ImagerHelpers::getCropSize($sourceSize, $transform, $config->getSetting('allowUpscale', $transform));
            $this->width = $targetCrop->getWidth();
            $this->height = $targetCrop->getHeight();
        } catch (InvalidArgumentException $e) {
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }
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
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return bool
     */
    public function getIsNew(): bool
    {
        return $this->isNew;
    }

    /**
     * @param string $unit
     * @param int $precision
     *
     * @return float|int
     */
    public function getSize($unit = 'b', $precision = 2)
    {
        $unit = strtolower($unit);

        switch ($unit) {
            case 'g':
            case 'gb':
                return round(((int)$this->size) / 1024 / 1024 / 1024, $precision);
            case 'm':
            case 'mb':
                return round(((int)$this->size) / 1024 / 1024, $precision);
            case 'k':
            case 'kb':
                return round(((int)$this->size) / 1024, $precision);
        }

        return $this->size;
    }

    /**
     * @return string
     */
    public function getDataUri(): string
    {
        $imageData = $this->getBase64Encoded();
        return sprintf('data:image/%s;base64,%s', $this->extension, $imageData);
    }

    /**
     * @return string
     */
    public function getBase64Encoded(): string
    {
        $image = @file_get_contents($this->path);
        return base64_encode($image);
    }

    public function __toString()
    {
        return (string)$this->url;
    }
}
