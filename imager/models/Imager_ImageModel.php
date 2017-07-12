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

class Imager_ImageModel extends BaseModel
{

    /**
     * Constructor
     *
     * @param null $imagePath
     * @param null $imageUrl
     */
    public function __construct($imagePath = null, $imageUrl = null, $paths = null, $transform = null)
    {
        if ($imagePath !== null) {
            $this['path'] = $imagePath;

            $this['extension'] = IOHelper::getExtension($imagePath);
            $this['mimeType'] = IOHelper::getMimeType($imagePath);
            $this['size'] = IOHelper::getFileSize($imagePath);

            $imageInfo = @getimagesize($imagePath);

            if (is_array($imageInfo) && $imageInfo[0] !== '' && $imageInfo[1] !== '') {
                $this['width'] = $imageInfo[0];
                $this['height'] = $imageInfo[1];
            } else {
                // Couldn't get size. Calculate size based on source image and transform.
                $sourceImageInfo = @getimagesize($paths->sourcePath . $paths->sourceFilename);
                $sourceSize = new \Imagine\Image\Box($sourceImageInfo[0], $sourceImageInfo[1]);
                $targetCrop = craft()->imager->getCropSize($sourceSize, $transform);
                
                $this['width'] = $targetCrop->getWidth();
                $this['height'] = $targetCrop->getHeight();
            }
        }

        if ($imageUrl !== null) {
            $this['url'] = $imageUrl;
        }
    }

    protected function defineAttributes()
    {
        return array(
          'path' => array(AttributeType::String),
          'url' => array(AttributeType::String),
          'extension' => array(AttributeType::String),
          'mimeType' => array(AttributeType::String),
          'width' => array(AttributeType::Number),
          'height' => array(AttributeType::Number),
          'size' => array(AttributeType::Number),
        );
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
        $unit = strtolower($unit);

        switch ($unit) {
            case "g":
            case "gb":
                return round(((int)$this->size) / 1024 / 1024 / 1024, $precision);
                break;
            case "m":
            case "mb":
                return round(((int)$this->size) / 1024 / 1024, $precision);
                break;
            case "k":
            case "kb":
                return round(((int)$this->size) / 1024, $precision);
                break;
            default:
                return $this->size;
        }
    }

    public function getDataUri()
    {
        $imageData = $this->getBase64Encoded();
        return sprintf('data:image/%s;base64,%s', $this->extension, $imageData);
    }

    public function getBase64Encoded()
    {
        $image = IOHelper::getFileContents($this->path);
        return base64_encode($image);
    }

}
