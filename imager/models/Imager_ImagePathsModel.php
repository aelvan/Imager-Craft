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
        if (is_string($image)) {

            if (strpos($image, craft()->imager->getSetting('imagerUrl')) !== false) { // url to a file that is in the imager library
                $this->getPathsForLocalImagerFile($image);
            } else {
                if (strrpos($image, 'http') !== false) { // external file
                    $this->_getPathsForUrl($image);
                } else { // relative path, assume that it's relative to document root
                    $this->_getPathsForLocaleFile($image);
                }
            }

        } else { // It's some kind of model

            if (get_class($image) == 'Craft\Imager_ImageModel') {
                $this->getPathsForLocalImagerFile($image->url);
            } else {
                if (get_class($image) == 'Craft\AssetFileModel') {
                    if (!$image->getSource()->getSourceType()->isSourceLocal()) { // it's a cloud source, pretend this is an external file for performance
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
          'sourcePath' => array(AttributeType::String),
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

        if (strrpos($assetSourcePath, 'http') !== false) {
            $parsedUrl = parse_url($assetSourcePath);
            $assetSourcePath = $parsedUrl['path'];
        }

        $this->sourcePath = ImagerService::fixSlashes(craft()->config->parseEnvironmentString($image->getSource()->settings['path']) . $image->getFolder()->path);
        $this->targetPath = ImagerService::fixSlashes(craft()->imager->getSetting('imagerSystemPath') . $assetSourcePath . $image->getFolder()->path) . $image->id . '/';
        $this->targetUrl = craft()->imager->getSetting('imagerUrl') . ImagerService::fixSlashes($assetSourcePath . $image->getFolder()->path, true) . $image->id . '/';
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
        $this->targetPath = ImagerService::fixSlashes(craft()->imager->getSetting('imagerSystemPath') . $pathParts['dirname'] . '/');
        $this->targetUrl = craft()->imager->getSetting('imagerUrl') . ImagerService::fixSlashes($pathParts['dirname'] . '/', true);
        $this->sourceFilename = $this->targetFilename = $pathParts['basename'];
    }

    /**
     * Get paths for a local file that's in the imager path
     *
     * @param $image
     */
    private function _getPathsForLocaleFile($image)
    {
        $pathParts = pathinfo($image);

        $this->sourcePath = $_SERVER['DOCUMENT_ROOT'] . $pathParts['dirname'] . '/';
        $this->targetPath = ImagerService::fixSlashes(craft()->imager->getSetting('imagerSystemPath') . $pathParts['dirname'] . '/');
        $this->targetUrl = craft()->imager->getSetting('imagerUrl') . ImagerService::fixSlashes($pathParts['dirname'] . '/', true);
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
        $urlParts = parse_url($image);
        $pathParts = pathinfo($urlParts['path']);
        $hashRemoteUrl = craft()->imager->getSetting('hashRemoteUrl');

        if ($hashRemoteUrl) {
            if (is_string($hashRemoteUrl) && $hashRemoteUrl == 'host') {
                $parsedDirname = substr(md5($urlParts['host']), 0, 10) . $pathParts['dirname'];
            } else {
                $parsedDirname = md5($urlParts['host'] . $pathParts['dirname']);
            }
        } else {
            $parsedDirname = str_replace('.', '_', $urlParts['host']) . $pathParts['dirname'];
        }

        $this->sourcePath = craft()->path->getRuntimePath() . 'imager/' . $parsedDirname . '/';
        $this->targetPath = craft()->imager->getSetting('imagerSystemPath') . $parsedDirname . '/';
        $this->targetUrl = craft()->imager->getSetting('imagerUrl') . $parsedDirname . '/';
        $this->sourceFilename = $this->targetFilename = $pathParts['basename'];

        // check if the temp path for remote files exists or can be created.
        if (!IOHelper::getRealPath($this->sourcePath)) {
            IOHelper::createFolder($this->sourcePath, craft()->config->get('defaultFolderPermissions'), true);

            if (!IOHelper::getRealPath($this->sourcePath)) {
                throw new Exception(Craft::t('Temp folder “{sourcePath}” does not exist and could not be created',
                  array('sourcePath' => $this->sourcePath)));
            }
        }

        // check if the file is already downloaded
        if (!IOHelper::fileExists($this->sourcePath . $this->sourceFilename) || (IOHelper::getLastTimeModified($this->sourcePath . $this->sourceFilename)->format('U') + craft()->imager->getSetting('cacheDurationRemoteFiles') < time())) {
            $this->_downloadFile($this->sourcePath . $this->sourceFilename, $image);

            if (!IOHelper::fileExists($this->sourcePath . $this->sourceFilename)) {
                throw new Exception(Craft::t('File could not be downloaded and saved to “{sourcePath}”',
                  array('sourcePath' => $this->sourcePath)));
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
        if (function_exists('curl_init')) {
            $ch = curl_init($imageUrl);
            $fp = fopen($destinationPath, "wb");

            $options = array(
              CURLOPT_FILE => $fp,
              CURLOPT_HEADER => 0,
              CURLOPT_FOLLOWLOCATION => 1,
              CURLOPT_TIMEOUT => 30
            );

            curl_setopt_array($ch, $options);
            curl_exec($ch);
            $httpStatus = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            curl_close($ch);
            fclose($fp);

            if ($httpStatus != 200) {
                if (!($httpStatus == 404 && strrpos(mime_content_type($destinationPath), 'image') !== false)) { // remote server returned a 404, but the contents was a valid image file.
                    unlink($destinationPath);
                    throw new Exception(Craft::t('HTTP status “{httpStatus}” encountered while attempting to download “{imageUrl}”',
                      array('imageUrl' => $imageUrl, 'httpStatus' => $httpStatus)));
                }
            }
        } elseif (ini_get('allow_url_fopen')) {
            if (!@file_put_contents($destinationPath, file_get_contents($imageUrl))) {
                unlink($destinationPath);
                $httpStatus = $http_response_header[0];
                throw new Exception(Craft::t('“{httpStatus}” encountered while attempting to download “{imageUrl}”',
                  array('imageUrl' => $imageUrl, 'httpStatus' => $httpStatus)));
            }
        } else {
            throw new Exception(Craft::t('Looks like allow_url_fopen is off and cURL is not enabled. To download external files, one of these methods has to be enabled.'));
        }
    }

}
