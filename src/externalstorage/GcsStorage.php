<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2018 André Elvan
 */

namespace aelvan\imager\externalstorage;

use Craft;
use craft\helpers\FileHelper;

use Google\Cloud\Exception\ServiceException;
use Google\Cloud\Storage\StorageClient;

use aelvan\imager\models\ConfigModel;
use aelvan\imager\services\ImagerService;

class GcsStorage implements ImagerStorageInterface
{

    public static function upload(string $file, string $uri, bool $isFinal, array $settings)
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();
        
        if (isset($settings['folder']) && $settings['folder']!=='') {
            $uri = ltrim(FileHelper::normalizePath($settings['folder'] . '/' . $uri), '/');
        }
        
        $keyFilePath = FileHelper::normalizePath($settings['keyFile']);
        
        $storage = new StorageClient([
            'keyFilePath' => $keyFilePath 
        ]);
        
        $bucket = $storage->bucket($settings['bucket']);
        $cacheDuration = $isFinal ? $config->cacheDurationExternalStorage : $config->cacheDurationNonOptimized;
        
        try {
            $bucket->upload(
                fopen($file, 'r'),
                [
                    'name' => $uri,
                    'predefinedAcl' => 'publicRead',
                    'metadata' => [
                        'cacheControl' => 'max-age=' . $cacheDuration . ', must-revalidate'
                    ]
                ]
            );
        } catch(ServiceException $e) {
            Craft::error('An error occured while uploading to Google Cloud Storage: ' . $e->getServiceException()->getMessage(), __METHOD__);
            return false;
        }

        return true;
    }

}