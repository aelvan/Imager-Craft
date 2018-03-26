<?php

namespace aelvan\imager\models;

use craft\base\Model;
use Yii;

class Settings extends Model
{
    public $transformer = 'craft';
    public $imagerSystemPath = '@webroot/imager/';
    public $imagerUrl = '/imager/';
    public $cacheEnabled = true;
    public $cacheRemoteFiles = true;
    public $cacheDuration = 1209600;
    public $cacheDurationRemoteFiles = 1209600;
    public $cacheDurationExternalStorage = 1209600;
    public $cacheDurationNonOptimized = 300;
    public $jpegQuality = 80;
    public $pngCompressionLevel = 2;
    public $webpQuality = 80;
    public $webpImagickOptions = [];
    public $useCwebp = false;
    public $cwebpPath = '/usr/bin/cwebp';
    public $cwebpOptions = '';
    public $interlace = false;
    public $allowUpscale = true;
    public $resizeFilter = 'lanczos';
    public $smartResizeEnabled = false;
    public $removeMetadata = false;
    public $bgColor = '';
    public $position = '50% 50%';
    public $letterbox = ['color' => '#000', 'opacity' => 0];
    public $useFilenamePattern = true;
    public $filenamePattern = '{basename}_{transformString|hash}.{extension}';
    public $shortHashLength = 10;
    public $hashFilename = 'postfix'; // deprecated
    public $hashPath = false;
    public $addVolumeToPath = true;
    public $hashRemoteUrl = false;
    public $useRemoteUrlQueryString = false;
    public $instanceReuseEnabled = false;
    public $noop = false;
    public $suppressExceptions = false;
    public $convertToRGB = false;
    public $skipExecutableExistCheck = false;
    public $curlOptions = [];
    public $runJobsImmediatelyOnAjaxRequests = true;
    public $fillTransforms = false;
    public $fillAttribute = 'width';
    public $fillInterval = '200';
    public $clearKey = '';
    
    public $useForNativeTransforms = false;
    public $useForCpThumbs = false;

    public $imgixProfile = 'default';
    public $imgixConfig = [
        'default' => [
            'domains' => [],
            'useHttps' => true,
            'signKey' => '',
            'sourceIsWebProxy' => false,
            'useCloudSourcePath' => true,
            'shardStrategy' => 'cycle',
            'getExternalImageDimensions' => true,
            'defaultParams' => [],
        ]
    ];
    
    public $optimizeType = 'job';
    public $optimizers = [];
    public $optimizerConfig = [
        'jpegoptim' => [
            'extensions' => ['jpg'],
            'path' => '/usr/bin/jpegoptim',
            'optionString' => '-s',
        ],
        'jpegtran' => [
            'extensions' => ['jpg'],
            'path' => '/usr/bin/jpegtran',
            'optionString' => '-optimize -copy none',
        ],
        'mozjpeg' => [
            'extensions' => ['jpg'],
            'path' => '/usr/bin/mozjpeg',
            'optionString' => '-optimize -copy none',
        ],
        'optipng' => [
            'extensions' => ['png'],
            'path' => '/usr/bin/optipng',
            'optionString' => '-o2',
        ],
        'pngquant' => [
            'extensions' => ['png'],
            'path' => '/usr/bin/pngquant',
            'optionString' => '--strip --skip-if-larger',
        ],
        'gifsicle' => [
            'extensions' => ['gif'],
            'path' => '/usr/bin/gifsicle',
            'optionString' => '--optimize=3 --colors 256',
        ],
        'tinypng' => [
            'extensions' => ['png','jpg'],
            'apiKey' => '',
        ],
        'kraken' => [
            'extensions' => ['png', 'jpg', 'gif'],
            'apiKey' => '',
            'apiSecret' => '',
            'additionalParams' => [
                'lossy' => true,
            ]
        ],
        'imageoptim' => [
            'extensions' => ['png', 'jpg', 'gif'],
            'apiUsername' => '',
            'quality' => 'medium'
        ],
    ];
    
    public $storages = [];
    public $storageConfig = [
        'aws' => [
            'accessKey' => '',
            'secretAccessKey' => '',
            'region' => '',
            'bucket' => '',
            'folder' => '',
            'requestHeaders' => array(),
            'storageType' => 'standard',
            'cloudfrontInvalidateEnabled' => false,
            'cloudfrontDistributionId' => '',
        ],
        'gcs' => [
            'keyFile' => '',
            'bucket' => '',
            'folder' => '',
        ],
    ];

    /**
     * Settings constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        
        if (!empty($config)) {
            \Yii::configure($this, $config);
        }
        $this->init();
    }

    public function init()
    {
        // Have to set this here cause Yii::getAlias can't be used in default value
        $this->imagerSystemPath = Yii::getAlias($this->imagerSystemPath);
        $this->suppressExceptions = !\Craft::$app->getConfig()->general->devMode;
    }
}
