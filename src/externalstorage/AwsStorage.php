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

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\CloudFront\CloudFrontClient;
use Aws\CloudFront\Exception\CloudFrontException;

use aelvan\imager\models\ConfigModel;
use aelvan\imager\services\ImagerService;

class AwsStorage implements ImagerStorageInterface
{

    public static function upload(string $file, string $uri, bool $isFinal, array $settings)
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        $clientConfig = [
            'version' => 'latest',
            'region' => $settings['region'],
            'credentials' => [
                'key' => $settings['accessKey'],
                'secret' => $settings['secretAccessKey'],
            ],
        ];

        try {
            $s3 = new S3Client($clientConfig);
        } catch (\InvalidArgumentException $e) {
            Craft::error('Invalid configuration of S3 Client: '.$e->getMessage(), __METHOD__);
            return false;
        }
        
        if (isset($settings['folder']) && $settings['folder'] !== '') {
            $uri = ltrim(FileHelper::normalizePath($settings['folder'].'/'.$uri), '/');
        }

        $opts = $settings['requestHeaders'];
        $cacheDuration = $isFinal ? $config->cacheDurationExternalStorage : $config->cacheDurationNonOptimized;

        if (!isset($opts['Cache-Control'])) {
            $opts['CacheControl'] = 'max-age='.$cacheDuration.', must-revalidate';
        }

        $opts = array_merge($opts, [
            'Bucket' => $settings['bucket'],
            'Key' => $uri,
            'Body' => fopen($file, 'r'),
            'ACL' => 'public-read',
            'StorageClass' => self::getAWSStorageClass($settings['storageType']),
        ]);

        try {
            $s3->putObject($opts);
        } catch (S3Exception $e) {
            Craft::error('An error occured while uploading to Amazon S3: '.$e->getMessage(), __METHOD__);

            return false;
        }

        // Cloudfront invalidation
        if (isset($settings['cloudfrontInvalidateEnabled'], $settings['cloudfrontDistributionId']) && $settings['cloudfrontInvalidateEnabled'] === true) {
            
            try {
                $cloudfront = new CloudFrontClient($clientConfig);
            } catch (\InvalidArgumentException $e) {
                Craft::error('Invalid configuration of CloudFront Client: '.$e->getMessage(), __METHOD__);
                return false;
            }
            
            try {
                $cloudfront->createInvalidation([
                    'DistributionId' => $settings['cloudfrontDistributionId'],
                    'InvalidationBatch' => [
                        'Paths' => [
                            'Quantity' => 1,
                            'Items' => ['/'.$uri],
                        ],
                        'CallerReference' => md5($uri . random_int(111111, 999999)),
                    ]
                ]);
            } catch (CloudFrontException $e) {
                Craft::error('An error occured while sending an Cloudfront invalidation request: ' . $e->getMessage(), __METHOD__);
            }
        }

        return true;
    }

    /**
     * @param $storageTypeString
     *
     * @return string
     */
    private static function getAWSStorageClass($storageTypeString): string
    {
        switch (mb_strtolower($storageTypeString)) {
            case 'standard':
                return 'STANDARD';
            case 'rrs':
                return 'REDUCED_REDUNDANCY';
            case 'glacier':
                return 'GLACIER';
        }

        return 'STANDARD';
    }
}
