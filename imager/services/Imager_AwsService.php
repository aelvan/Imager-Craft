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

class Imager_AwsService extends BaseApplicationComponent
{
    var $s3 = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        
    }

    /**
     * Upload file to AWS
     * 
     * @param $filePath
     * @param bool $finalVersion
     */
    public function uploadToAWS($filePath, $finalVersion = true)
    {
        $s3 = $this->_getS3Object();

        $file = $s3->inputFile($filePath);
        $headers = craft()->imager->getSetting('awsRequestHeaders');

        $cacheDuration = $finalVersion ? craft()->imager->getSetting('awsCacheDuration') : craft()->imager->getSetting('awsCacheDurationNonOptimized');

        if (!isset($headers['Cache-Control'])) {
            $headers['Cache-Control'] = 'max-age=' . $cacheDuration . ', must-revalidate';
        }

        if (!$s3->putObject($file, craft()->imager->getSetting('awsBucket'),
          ImagerService::fixSlashes(craft()->imager->getSetting('awsFolder') . '/' . str_replace(craft()->imager->getSetting('imagerSystemPath'), '', $filePath), true, true), \S3::ACL_PUBLIC_READ, array(), $headers,
          $this->_getAWSStorageClass())
        ) //fail
        {
            ImagerPlugin::log("Upload to AWS failed for $filePath in ImagerService", LogLevel::Error);
        }
    }

    /**
     * Get AWS storage type
     * 
     * @return string
     */
    private function _getAWSStorageClass()
    {
        switch (craft()->imager->getSetting('awsStorageType')) {
            case 'standard':
                return \S3::STORAGE_CLASS_STANDARD;
            case 'rrs':
                return \S3::STORAGE_CLASS_RRS;
        }
        return \S3::STORAGE_CLASS_STANDARD;
    }


    /**
     * Invalidates paths in a cloudfront distribution 
     * 
     * @param $paths
     * @return bool
     */
    public function invalidateCloudfrontPaths($paths)
    {
        $result = \S3::invalidateDistribution(craft()->imager->getSetting('cloudfrontDistributionId'), $paths);
        return $result;
    }

    /**
     * Returns the S3 object
     * 
     * @return null|\S3
     */
    private function _getS3Object() {
        if (is_null($this->s3)) {
            $this->s3 = new \S3(craft()->imager->getSetting('awsAccessKey'), craft()->imager->getSetting('awsSecretAccessKey'));
            $this->s3->setExceptions(true);
        } 
        
        return $this->s3;
    }

}
