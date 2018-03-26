<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\imager\models;

use Craft;

use craft\base\LocalVolumeInterface;
use craft\base\Volume;
use craft\helpers\FileHelper;
use craft\elements\Asset;
use craft\helpers\StringHelper;


use aelvan\imager\helpers\ImagerHelpers;
use aelvan\imager\services\ImagerService;
use aelvan\imager\exceptions\ImagerException;

use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * LocalSourceImageModel
 *
 * Represents the source image for a file that need to be stored locally.
 *
 * @author    André Elvan
 * @package   Imager
 * @since     2.0.0
 */
class LocalSourceImageModel
{
    public $type = 'local';
    public $path = '';
    public $transformPath = '';
    public $url = '';
    public $filename = '';
    public $basename = '';
    public $extension = '';

    /** @var Asset|null $image */
    private $asset;

    /**
     * LocalSourceImageModel constructor.
     *
     * @param $image
     *
     * @throws ImagerException
     */
    public function __construct($image)
    {
        $this->init($image);
    }

    /**
     * Init method
     *
     * @param $image
     *
     * @throws ImagerException
     */
    private function init($image)
    {
        /** @var ConfigModel $settings */
        $settings = ImagerService::getConfig();

        if (\is_string($image)) {
            if (strncmp($image, $settings->imagerUrl, \strlen($settings->imagerUrl)) === 0) {
                // Url to a file that is in the imager library
                $this->getPathsForLocalImagerFile($image);
            } else {
                if (strncmp($image, '//', 2) === 0) {
                    // Protocol relative url, add https
                    $image = 'https:'.$image;
                }

                if (strncmp($image, 'http', 4) === 0 || strncmp($image, 'https', 5) === 0) {
                    // External url
                    $this->type = 'remoteurl';
                    $this->getPathsForUrl($image);
                } else {
                    // Relative path, assume that it's relative to document root
                    $this->getPathsForLocalFile($image);
                }
            }
        } else {
            // It's some kind of model
            if (\get_class($image) === 'aelvan\\imager\\models\\CraftTransformedImageModel') {
                $this->getPathsForLocalImagerFile($image->url);
            } else {
                if ($image instanceof Asset) {
                    $this->asset = $image;

                    // todo : Improve this check to take into account other volumes that are local?
                    try {
                        $volumeClass = \get_class($image->getVolume());
                    } catch (InvalidConfigException $e) {
                        Craft::error($e->getMessage(), __METHOD__);
                        throw new ImagerException($e->getMessage(), $e->getCode(), $e);
                    }

                    if ($volumeClass === 'craft\volumes\Local') {
                        $this->getPathsForLocalAsset($image);
                    } else {
                        $this->type = 'volume';
                        $this->getPathsForVolumeAsset($image);
                    }
                } else {
                    throw new ImagerException(Craft::t('imager', 'An unknown image object was used.'));
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return FileHelper::normalizePath($this->path.'/'.$this->filename);
    }

    /**
     * Get a local copy of source file
     *
     * @throws ImagerException
     */
    public function getLocalCopy()
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        if ($this->type !== 'local') {
            if (!file_exists($this->getFilePath()) || (($config->cacheDurationRemoteFiles !== false) && ((FileHelper::lastModifiedTime($this->getFilePath()) + $config->cacheDurationRemoteFiles) < time()))) {
                if ($this->asset && $this->type === 'volume') {
                    /** @var Volume $volume */
                    try {
                        $volume = $this->asset->getVolume();
                    } catch (InvalidConfigException $e) {
                        Craft::error($e->getMessage(), __METHOD__);
                        throw new ImagerException($e->getMessage(), $e->getCode(), $e);
                    }

                    $volume->saveFileLocally($this->asset->getPath(), $this->getFilePath());
                }

                if ($this->type === 'remoteurl') {
                    $this->downloadFile();
                }

                if (file_exists($this->getFilePath())) {
                    ImagerService::registerCachedRemoteFile($this->getFilePath());
                }
            }

            if (!file_exists($this->getFilePath())) {
                $msg = Craft::t('imager', 'File could not be downloaded and saved to “{path}”', ['path' => $this->path]);
                Craft::error($msg, __METHOD__);
                throw new ImagerException($msg);
            }
        }
    }


    /**
     * Get paths for a local asset
     *
     * @param Asset $image
     *
     * @throws ImagerException
     */
    private function getPathsForLocalAsset(Asset $image)
    {
        /** @var LocalVolumeInterface $volume */
        try {
            $volume = $image->getVolume();
            $this->transformPath = ImagerHelpers::getTransformPathForAsset($image);
        } catch (InvalidConfigException $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }

        $this->path = FileHelper::normalizePath($volume->getRootPath().'/'.$image->folderPath);
        $this->url = $image->getUrl();
        $this->filename = $image->getFilename();
        $this->basename = $image->getFilename(false);
        $this->extension = $image->getExtension();
    }

    /**
     * Get paths for an asset on an external Craft volume.
     *
     * @param Asset $image
     *
     * @throws ImagerException
     */
    private function getPathsForVolumeAsset($image)
    {
        try {
            $this->transformPath = ImagerHelpers::getTransformPathForAsset($image);
        } catch (InvalidConfigException $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }

        $runtimeImagerPath = Craft::$app->getPath()->getRuntimePath().'/imager/';

        $this->url = $image->getUrl();
        $this->path = FileHelper::normalizePath($runtimeImagerPath.$this->transformPath.'/');
        $this->filename = $image->getFilename();
        $this->basename = $image->getFilename(false);
        $this->extension = $image->getExtension();

        try {
            FileHelper::createDirectory($this->path);
        } catch (Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get paths for a local file that's in the imager path
     *
     * @param $image
     */
    private function getPathsForLocalImagerFile($image)
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        $imageString = '/'.str_replace($config->getSetting('imagerUrl'), '', $image);

        $pathParts = pathinfo($imageString);

        $this->transformPath = $pathParts['dirname'];
        $this->path = FileHelper::normalizePath($config->getSetting('imagerSystemPath').'/'.$pathParts['dirname']);
        $this->url = $image;
        $this->filename = $pathParts['basename'];
        $this->basename = $pathParts['filename'];
        $this->extension = $pathParts['extension'];
    }

    /**
     * Get paths for a local file that's not in the imager path
     *
     * @param $image
     */
    private function getPathsForLocalFile($image)
    {
        $this->transformPath = ImagerHelpers::getTransformPathForPath($image);
        $pathParts = pathinfo($image);

        $this->path = FileHelper::normalizePath(Yii::getAlias('@webroot').'/'.$pathParts['dirname']);
        $this->url = $image;
        $this->filename = $pathParts['basename'];
        $this->basename = $pathParts['filename'];
        $this->extension = $pathParts['extension'];
    }

    /**
     * Get paths for an external file (really external, or on an external source type)
     *
     * @param $image
     *
     * @throws ImagerException
     */
    private function getPathsForUrl($image)
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        $runtimeImagerPath = Craft::$app->getPath()->getRuntimePath().'/imager/';
        $convertedImageStr = StringHelper::toAscii(urldecode($image));
        $this->transformPath = ImagerHelpers::getTransformPathForUrl($convertedImageStr);

        $urlParts = parse_url($convertedImageStr);
        $pathParts = pathinfo($urlParts['path']);
        $queryString = $config->getSetting('useRemoteUrlQueryString') ? ($urlParts['query'] ?? '') : '';

        $this->path = FileHelper::normalizePath($runtimeImagerPath.$this->transformPath.'/');
        $this->url = $image;
        $this->basename = str_replace(' ', '-', $pathParts['filename']).($queryString !== '' ? '_'.md5($queryString) : '');
        $this->extension = $pathParts['extension'] ?? '';
        $this->filename = $this->basename.'.'.$this->extension;

        try {
            FileHelper::createDirectory($this->path);
        } catch (Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Downloads an external url and places it in the source path.
     *
     * @throws ImagerException
     */
    private function downloadFile()
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        // url encode filename to account for non-ascii characters in filenames.
        $imageUrlArr = explode('?', $this->url);

        $imageUrlArr[0] = preg_replace_callback('#://([^/]+)/([^?]+)#', function($match) {
            return '://'.$match[1].'/'.implode('/', array_map('rawurlencode', explode('/', $match[2])));
        }, urldecode($imageUrlArr[0]));

        $imageUrl = implode('?', $imageUrlArr);

        if (\function_exists('curl_init')) {
            $ch = curl_init($imageUrl);
            $fp = fopen($this->getFilePath(), 'wb');
            
            $defaultOptions = [
                CURLOPT_FILE => $fp,
                CURLOPT_HEADER => 0,
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_TIMEOUT => 30
            ];

            // merge default options with config setting, config overrides default.
            $options = $config->getSetting('curlOptions') + $defaultOptions;

            curl_setopt_array($ch, $options);
            curl_exec($ch);
            $curlErrorNo = curl_errno($ch);
            $curlError = curl_error($ch);
            $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose(/** @scrutinizer ignore-type */ $fp);

            if ($curlErrorNo !== 0) {
                unlink($this->getFilePath());
                $msg = Craft::t('imager', 'cURL error “{curlErrorNo}” encountered while attempting to download “{imageUrl}”. The error was: “{curlError}”', ['imageUrl' => $imageUrl, 'curlErrorNo' => $curlErrorNo, 'curlError' => $curlError]);
                Craft::error($msg, __METHOD__);
                throw new ImagerException($msg);
            }

            if ($httpStatus !== 200) {
                if (!($httpStatus === 404 && strrpos(mime_content_type($this->getFilePath()), 'image') !== false)) { // remote server returned a 404, but the contents was a valid image file
                    unlink($this->getFilePath());
                    $msg = Craft::t('imager', 'HTTP status “{httpStatus}” encountered while attempting to download “{imageUrl}”', ['imageUrl' => $imageUrl, 'httpStatus' => $httpStatus]);
                    Craft::error($msg, __METHOD__);
                    throw new ImagerException($msg);
                }
            }
        } elseif (ini_get('allow_url_fopen')) {
            if (!@copy($imageUrl, $this->getFilePath())) {
                unlink($this->getFilePath());
                $errors = error_get_last();
                $msg = Craft::t('imager', 'Error “{errorType}” encountered while attempting to download “{imageUrl}”: {errorMessage}', ['imageUrl' => $imageUrl, 'errorType' => $errors['type'], 'errorMessage' => $errors['message']]);
                Craft::error($msg, __METHOD__);
                throw new ImagerException($msg);
            }
        } else {
            $msg = Craft::t('imager', 'Looks like allow_url_fopen is off and cURL is not enabled. To download external files, one of these methods has to be enabled.');
            Craft::error($msg, __METHOD__);
            throw new ImagerException($msg);
        }
    }
}
