<?php
namespace Craft;

class Imager_ImagePathsModel extends BaseModel
{

    /**
     * Constructor
     */
    public function __construct($image)
    {
        if (is_string($image)) { // Not an AssetFileModel

            // if we remove imagerUrl, we'll figure out if it is an image that's already transformed by Imager 
            $imageString = str_replace(craft()->imager->getSetting('imagerUrl'), '', $image);

            if (strrpos($imageString, 'http') !== false) { // nope, this is a remote file. download and proceed.
                $this->_getPathsForUrl($image);
            } else { // file that is in the imager library
                $this->_getPathsForLocaleImagerFile($image);
            }

        } else { // It's an AssetFileModel

            if (!$image->getSource()->getSourceType()->isSourceLocal()) { // it's a cloud source, pretend this is an external file for performance
                $this->_getPathsForUrl($image->getUrl());
            } else {  // it's a local source
                $this->_getPathsForLocalAsset($image);
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
        $this->sourcePath = craft()->config->parseEnvironmentString($image->getSource()->settings['path']) . $image->getFolder()->path;
        $this->targetPath = craft()->imager->getSetting('imagerSystemPath') . $image->getFolder()->path;
        $this->targetUrl = craft()->imager->getSetting('imagerUrl') . $image->getFolder()->path;
        $this->sourceFilename = $this->targetFilename = $image->filename;
    }

    /**
     * Get paths for a local file that's in the imager path
     * 
     * @param $image
     */
    private function _getPathsForLocaleImagerFile($image)
    {
        $imageString = str_replace(craft()->imager->getSetting('imagerUrl'), '', $image);
        $pathParts = pathinfo($imageString);
        $this->sourcePath = craft()->imager->getSetting('imagerSystemPath') . $pathParts['dirname'] . '/';
        $this->targetPath = craft()->imager->getSetting('imagerSystemPath') . $pathParts['dirname'] . '/';
        $this->targetUrl = craft()->imager->getSetting('imagerUrl') . $pathParts['dirname'] . '/';
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
            if (is_string($hashRemoteUrl) && $hashRemoteUrl=='host') {
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
            @file_put_contents($this->sourcePath . $this->sourceFilename, fopen($image, 'r'));

            if (!IOHelper::fileExists($this->sourcePath . $this->sourceFilename)) {
                throw new Exception(Craft::t('File could not be downloaded and saved to “{sourcePath}”',
                  array('sourcePath' => $this->sourcePath)));
            }
        }
    }

}
