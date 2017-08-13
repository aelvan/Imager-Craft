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

class Imager_GcsService extends BaseApplicationComponent
{
    private $gc = null;

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Upload file to Google Cloud Storage
     *
     * @param $filePath
     * @param bool $finalVersion
     */
    public function uploadToGCS($filePath, $finalVersion = true)
    {
        $gcs = $this->_getGCObject();
        $uri = ImagerService::fixSlashes(craft()->imager->getSetting('gcsFolder') . '/' . str_replace(craft()->imager->getSetting('imagerSystemPath'), '', $filePath), true, true);

        $cacheDuration = $finalVersion ? craft()->imager->getSetting('gcsCacheDuration') : craft()->imager->getSetting('gcsCacheDurationNonOptimized');

        $headers = array();
        if (!isset($headers['Cache-Control'])) {
            $headers['Cache-Control'] = 'max-age=' . $cacheDuration . ', must-revalidate';
        }

        if (!$gcs::putObject($gcs::inputFile($filePath), $this->_getBucket(), $uri, \GC::ACL_PUBLIC_READ, array(), $headers)) {
            ImagerPlugin::log("Upload to GCS failed for $filePath in ImagerService", LogLevel::Error);
        }
    }

    /**
     * Returns the GC object
     *
     * @return null|\GC
     */
    private function _getGCObject()
    {
        if (is_null($this->gc)) {
            $this->gc = new \GC(
                craft()->imager->getSetting('gcsAccessKey'),
                craft()->imager->getSetting('gcsSecretAccessKey')
            );
        }

        return $this->gc;
    }

    /**
     * @return string
     */
    private function _getBucket()
    {
        return craft()->imager->getSetting('gcsBucket');
    }

}
