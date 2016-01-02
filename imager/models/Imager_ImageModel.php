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
    public function __construct($imagePath = null, $imageUrl = null)
    {
        if ($imagePath != 'null') {
            $this['path'] = $imagePath;

            $imageInfo = @getimagesize($imagePath);
            $this['width'] = $imageInfo[0];
            $this['height'] = $imageInfo[1];

            $this['extension'] = IOHelper::getExtension($imagePath);
            $this['mimeType'] = IOHelper::getMimeType($imagePath);
        }

        if ($imageUrl != 'null') {
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
        );
    }

    function __toString()
    {
        return Craft::t($this->url);
    }

    function getPath()
    {
        return $this->path;
    }

    function getUrl()
    {
        return $this->url;
    }

    function getExtension()
    {
        return $this->extension;
    }

    function getMimeType()
    {
        return $this->mimeType;
    }

    function getWidth()
    {
        return $this->width;
    }

    function getHeight()
    {
        return $this->height;
    }

}
