<?php
namespace Craft;

class Imager_ImageModel extends BaseModel
{

    protected function defineAttributes()
    {
        return array(
          'url' => array(AttributeType::String),
          'width' => array(AttributeType::Number),
          'height' => array(AttributeType::Number),
        );
    }

    function __toString()
    {
        return Craft::t($this->url);
    }

    // todo : implement more methods to make it more similar to AssetFileModel
}
