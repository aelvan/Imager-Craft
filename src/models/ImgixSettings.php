<?php

namespace aelvan\imager\models;

use craft\base\Model;

class ImgixSettings extends Model
{
    public $domains = [];
    public $useHttps = true;
    public $signKey = '';
    public $sourceIsWebProxy = false;
    public $useCloudSourcePath = true;
    public $shardStrategy = 'cycle';
    public $getExternalImageDimensions = false;
    public $defaultParams = [];
    
    public function __construct($config = [])
    {
        if (!empty($config)) {
            \Yii::configure($this, $config);
        }
    }
}