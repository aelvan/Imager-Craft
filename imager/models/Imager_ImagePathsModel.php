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

class Imager_ImagePathsModel extends BaseModel
{
    /**
     * Constructor
     *
     * @param $image
     * @throws Exception
     */
    public function __construct($image)
    {
        $this->isRemote = false;

        if (is_string($image)) {
            
            if (strncmp($image, craft()->imager->getSetting('imagerUrl'), strlen(craft()->imager->getSetting('imagerUrl'))) === 0) { // url to a file that is in the imager library
                $this->getPathsForLocalImagerFile($image);
            } else {
                if (strncmp($image, 'http', 4) === 0 || strncmp($image, 'https', 5) === 0 || strncmp($image, '//', 2) === 0) { // external file
                    $this->isRemote = true;
                    if (strncmp($image, '//', 2) === 0) {
                        $image = 'https:' . $image;
                    }
                    $this->_getPathsForUrl($image);
                } else { // relative path, assume that it's relative to document root
                    $this->_getPathsForLocaleFile($image);
                }
            }

        } else { // It's some kind of model

            if (get_class($image) == 'Craft\Imager_ImageModel') {
                $this->getPathsForLocalImagerFile($image->url);
            } else {
                if ($image instanceof \Craft\AssetFileModel) {
                    if (!$image->getSource()->getSourceType()->isSourceLocal()) { // it's a cloud source, pretend this is an external file for performance
                        $this->isRemote = true;
                        $this->_getPathsForUrl($image->getUrl());
                    } else {  // it's a local source
                        $this->_getPathsForLocalAsset($image);
                    }
                } else {
                    throw new Exception(Craft::t('An unknown image object was used.'));
                }
            }

        }
    }

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return array(
          'isRemote' => array(AttributeType::Bool),
          'sourcePath' => array(AttributeType::String),
          'sourceUrl' => array(AttributeType::String),
          'targetPath' => array(AttributeType::String),
          'targetUrl' => array(AttributeType::String),
          'sourceFilename' => array(AttributeType::String),
          'targetFilename' => array(AttributeType::String),
        );
    }

    /**
     * Get paths for a local asset
     *
     * @param AssetFileModel $image
     */
    private function _getPathsForLocalAsset(AssetFileModel $image)
    {
        $assetSourcePath = craft()->config->parseEnvironmentString($image->getSource()->settings['url']);

        if (strncmp($assetSourcePath, 'http', 4) === 0 || strncmp($assetSourcePath, '//', 2) === 0) {
            $parsedUrl = parse_url($assetSourcePath);
            $assetSourcePath = $parsedUrl['path'];
        }

        $hashPath = craft()->imager->getSetting('hashPath');

        if ($hashPath) {
            $targetFolder = '/' . md5($assetSourcePath . $image->getFolder()->path) . '/';
        } else {
            $targetFolder = $assetSourcePath . $image->getFolder()->path;
        }

        $this->sourcePath = ImagerService::fixSlashes(craft()->config->parseEnvironmentString($image->getSource()->settings['path']) . $image->getFolder()->path);
        $this->sourceUrl = $image->getUrl();
        $this->targetPath = ImagerService::fixSlashes(craft()->imager->getSetting('imagerSystemPath') . $targetFolder) . $image->id . '/';
        $this->targetUrl = craft()->imager->getSetting('imagerUrl') . ImagerService::fixSlashes($targetFolder, true) . $image->id . '/';
        $this->sourceFilename = $this->targetFilename = $image->filename;
    }

    /**
     * Get paths for a local file that's in the imager path
     *
     * @param $image
     */
    private function getPathsForLocalImagerFile($image)
    {
        $imageString = str_replace(craft()->imager->getSetting('imagerUrl'), '', $image);
        $pathParts = pathinfo($imageString);

        $this->sourcePath = craft()->imager->getSetting('imagerSystemPath') . $pathParts['dirname'] . '/';
        $this->sourceUrl = $image;
        $this->targetPath = ImagerService::fixSlashes(craft()->imager->getSetting('imagerSystemPath') . $pathParts['dirname'] . '/');
        $this->targetUrl = craft()->imager->getSetting('imagerUrl') . ImagerService::fixSlashes($pathParts['dirname'] . '/', true);
        $this->sourceFilename = $this->targetFilename = $pathParts['basename'];
    }

    /**
     * Get paths for a local file that's not in the imager path
     *
     * @param $image
     */
    private function _getPathsForLocaleFile($image)
    {
        $pathParts = pathinfo($image);
        $hashPath = craft()->imager->getSetting('hashPath');

        if ($hashPath) {
            $targetFolder = md5($pathParts['dirname']);
        } else {
            $targetFolder = $pathParts['dirname'];
        }

        $this->sourcePath = $_SERVER['DOCUMENT_ROOT'] . $pathParts['dirname'] . '/';
        $this->sourceUrl = $image;
        $this->targetPath = ImagerService::fixSlashes(craft()->imager->getSetting('imagerSystemPath') . $targetFolder . '/');
        $this->targetUrl = craft()->imager->getSetting('imagerUrl') . ImagerService::fixSlashes($targetFolder . '/', true);
        $this->sourceFilename = $this->targetFilename = $pathParts['basename'];
    }

    /**
     * Get paths for an external file (really external, or on an external source type)
     *
     * @param $image
     * @throws Exception
     */
    private function _getPathsForUrl($image)
    {
        $convertedImageStr = StringHelper::asciiString(urldecode($image));
        $urlParts = parse_url($convertedImageStr);
        $pathParts = pathinfo($urlParts['path']);
        $queryString = craft()->imager->getSetting('useRemoteUrlQueryString') ? (isset($urlParts['query']) ? $urlParts['query'] : '') : '';
        $hashRemoteUrl = craft()->imager->getSetting('hashRemoteUrl');
        $hashPath = craft()->imager->getSetting('hashPath');
        
        if ($hashPath) {
            $targetFolder = '/' . md5($pathParts['dirname']);
        } else {
            $targetFolder = $pathParts['dirname'];
        }

        if ($hashRemoteUrl) {
            if (is_string($hashRemoteUrl) && $hashRemoteUrl == 'host') {
                $parsedDirname = substr(md5($urlParts['host']), 0, 10) . $targetFolder;
            } else {
                $parsedDirname = md5($urlParts['host'] . $pathParts['dirname']);
            }
        } else {
            $parsedDirname = str_replace('.', '_', $urlParts['host']) . $targetFolder;
        }
        
        $runtimePath = IOHelper::getRealPath(craft()->path->getRuntimePath());
        $this->sourcePath = ImagerService::fixSlashes($runtimePath . 'imager/' . $parsedDirname . '/');
        $this->sourceUrl = $image;
        $this->targetPath = ImagerService::fixSlashes(craft()->imager->getSetting('imagerSystemPath') . '/' . $parsedDirname . '/');
        $this->targetUrl = craft()->imager->getSetting('imagerUrl') . ImagerService::fixSlashes($parsedDirname . '/');
        $this->sourceFilename = $this->targetFilename = str_replace(' ', '-', $pathParts['filename']) . ($queryString!=='' ? '_' . md5($queryString) : '') . (isset($pathParts['extension']) ? '.' . $pathParts['extension'] : '');
        
        // check if the temp path for remote files exists or can be created.
        if (!IOHelper::getRealPath($this->sourcePath)) {
            IOHelper::createFolder($this->sourcePath, craft()->config->get('defaultFolderPermissions'), true);

            if (!IOHelper::getRealPath($this->sourcePath)) {
                $msg = Craft::t('Temp folder “{sourcePath}” does not exist and could not be created', array('sourcePath' => $this->sourcePath));
                
                if (craft()->imager->getSetting('suppressExceptions')===true) {
                    ImagerPlugin::log($msg, LogLevel::Error);
                    return null;
                } else {
                    throw new Exception($msg);
                }
            }
        }

        // check if the file is already downloaded
        if (!IOHelper::fileExists($this->sourcePath . $this->sourceFilename) ||
          ((craft()->imager->getSetting('cacheDurationRemoteFiles') !== false) && (IOHelper::getLastTimeModified($this->sourcePath . $this->sourceFilename)->format('U') + craft()->imager->getSetting('cacheDurationRemoteFiles') < time()))
        ) {
            $this->_downloadFile($this->sourcePath . $this->sourceFilename, $image);

            if (!IOHelper::fileExists($this->sourcePath . $this->sourceFilename)) {
                $msg = Craft::t('File could not be downloaded and saved to “{sourcePath}”', array('sourcePath' => $this->sourcePath));
                
                if (craft()->imager->getSetting('suppressExceptions')===true) {
                    ImagerPlugin::log($msg, LogLevel::Error);
                } else {
                    throw new Exception($msg);
                }
            }
        }
    }

    /**
     * Downloads remote file. Uses cURL if available, then tries with file_get_contents() if allow_url_fopen.
     *
     * @param $destinationPath
     * @param $imageUrl
     * @throws Exception
     */
    private function _downloadFile($destinationPath, $imageUrl)
    {
        // url encode filename to account for non-ascii characters in filenames.
        $imageUrlArr = explode('?', $imageUrl);
        
        $imageUrlArr[0] = preg_replace_callback('#://([^/]+)/([^?]+)#', function ($match) {
            return '://' . $match[1] . '/' . implode('/', array_map('rawurlencode', explode('/', $match[2])));
        }, urldecode($imageUrlArr[0]));
        
        $imageUrl = implode('?', $imageUrlArr);

        if (function_exists('curl_init')) {
            $ch = curl_init($imageUrl);
            $fp = fopen($destinationPath, "wb");

            $defaultOptions = array(
              CURLOPT_FILE => $fp,
              CURLOPT_HEADER => 0,
              CURLOPT_FOLLOWLOCATION => 1,
              CURLOPT_TIMEOUT => 30
            );

            // merge default options with config setting, config overrides default.
            $options = craft()->imager->getSetting('curlOptions') + $defaultOptions;

            curl_setopt_array($ch, $options);
            curl_exec($ch);
            $curlErrorNo = curl_errno($ch);
            $curlError = curl_error($ch);
            $httpStatus = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            curl_close($ch);
            fclose($fp);

            if ($curlErrorNo !== 0) {
                unlink($destinationPath);
                $msg = Craft::t('cURL error “{curlErrorNo}” encountered while attempting to download “{imageUrl}”. The error was: “{curlError}”', array('imageUrl' => $imageUrl, 'curlErrorNo' => $curlErrorNo, 'curlError' => $curlError));
                
                if (craft()->imager->getSetting('suppressExceptions')===true) {
                    ImagerPlugin::log($msg, LogLevel::Error);
                    return null;
                } else {
                    throw new Exception($msg);
                }
            }

            if ($httpStatus !== 200) {
                if (!($httpStatus == 404 && strrpos(mime_content_type($destinationPath), 'image') !== false)) { // remote server returned a 404, but the contents was a valid image file
                    unlink($destinationPath);
                    $msg = Craft::t('HTTP status “{httpStatus}” encountered while attempting to download “{imageUrl}”', array('imageUrl' => $imageUrl, 'httpStatus' => $httpStatus));
                    
                    if (craft()->imager->getSetting('suppressExceptions')===true) {
                        ImagerPlugin::log($msg, LogLevel::Error);
                        return null;
                    } else {
                        throw new Exception($msg);
                    }
                }
            }
        } elseif (ini_get('allow_url_fopen')) {
            if (!@file_put_contents($destinationPath, file_get_contents($imageUrl))) {
                unlink($destinationPath);
                $httpStatus = $http_response_header[0];
                $msg = Craft::t('“{httpStatus}” encountered while attempting to download “{imageUrl}”', array('imageUrl' => $imageUrl, 'httpStatus' => $httpStatus));
                
                if (craft()->imager->getSetting('suppressExceptions')===true) {
                    ImagerPlugin::log($msg, LogLevel::Error);
                    return null;
                } else {
                    throw new Exception($msg);
                }
            }
        } else {
            $msg = Craft::t('Looks like allow_url_fopen is off and cURL is not enabled. To download external files, one of these methods has to be enabled.');
            
            if (craft()->imager->getSetting('suppressExceptions')===true) {
                ImagerPlugin::log($msg, LogLevel::Error);
                return null;
            } else {
                throw new Exception($msg);
            }
        }
    }

}
