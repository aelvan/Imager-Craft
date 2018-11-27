<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\imager\services;

use aelvan\imager\exceptions\ImagerException;
use aelvan\imager\models\ConfigModel;
use aelvan\imager\models\ImgixSettings;
use aelvan\imager\services\ImagerService;

use Imgix\UrlBuilder;
use GuzzleHttp\RequestOptions;

use Craft;
use craft\base\Component;
use craft\base\LocalVolumeInterface;
use craft\base\Volume;
use craft\elements\Asset;
use craft\volumes\Local;

use yii\base\InvalidConfigException;

/**
 * ImgixService Service
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Mats Mikkel Rummelhoff
 * @package   Imager
 * @since     2.1.2
 */
class ImgixService extends Component
{

    /**
     *  The Imgix API endpoint for purging images
     */
    const PURGE_ENDPOINT = 'https://api.imgix.com/v2/image/purger';

    /**
     * @var bool If purging is enabled or not
     */
    protected static $canPurge;

    /**
     * Purging is possible if there's an `imgixConfig` map, and all sources/profiles have an API key set
     * Used for determining if the ImgixPurgeElementAction element action and various related event handlers should be bootstrapped or not
     *
     * @return bool
     */
    public static function getCanPurge(): bool
    {
        if (!isset(self::$canPurge)) {
            /** @var ConfigModel $settings */
            $config = ImagerService::getConfig();
            // No Imgix config, no purging
            $imgixConfigArr = $config->getSetting('imgixConfig');
            if (!$imgixConfigArr || !\is_array($imgixConfigArr) || empty($imgixConfigArr)) {
                self::$canPurge = false;
                return false;
            }
            // Make sure there's at least one profile that is not a web proxy and that is not excluded from purging
            $hasApiKey = !!$config->getSetting('imgixApiKey');
            $hasPurgableProfile = false;
            foreach ($imgixConfigArr as $profile => $imgixConfig) {
                $imgixConfig = new ImgixSettings($imgixConfig);
                $hasApiKey = $hasApiKey || !!$imgixConfig->apiKey;
                $hasPurgableProfile = $hasPurgableProfile || (!$imgixConfig->sourceIsWebProxy && !$imgixConfig->excludeFromPurge);
                if ($hasApiKey && $hasPurgableProfile) {
                    break;
                }
            }
            self::$canPurge = $hasApiKey && $hasPurgableProfile;
        }
        return self::$canPurge;
    }

    /**
     * @param string $url The base URL to the image you wish to purge (e.g. https://your-imgix-source.imgix.net/image.jpg)
     * @param string $apiKey Imgix API key
     * @throws ImagerException
     */
    public function purgeUrlFromImgix(string $url, string $apiKey)
    {
        try {
            $client = Craft::createGuzzleClient();
            $client->post(self::PURGE_ENDPOINT, [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Basic ' . \base64_encode("{$apiKey}:")
                ],
                RequestOptions::JSON => [
                    'url' => $url
                ],
            ]);

        } catch (\Throwable $e) {
            Craft::error($e->getMessage(), __METHOD__);
            throw new ImagerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param Asset $asset The Asset you wish to purge
     * @throws ImagerException
     */
    public function purgeAssetFromImgix(Asset $asset)
    {
        /** @var ConfigModel $settings */
        $config = ImagerService::getConfig();

        $imgixApiKey = $config->getSetting('imgixApiKey');
        $imgixConfigArr = $config->getSetting('imgixConfig');

        if (!$imgixConfigArr || !\is_array($imgixConfigArr) || empty($imgixConfigArr)) {
            $msg = Craft::t('imager', 'The "imgixConfig" config setting is missing, or is not correctly set up.');
            Craft::error($msg, __METHOD__);
            throw new ImagerException($msg);
        }

        foreach ($imgixConfigArr as $profile => $imgixConfig) {

            $imgixConfig = new ImgixSettings($imgixConfig);
            if ($imgixConfig->sourceIsWebProxy || $imgixConfig->excludeFromPurge) {
                continue;
            }

            $apiKey = $imgixConfig->apiKey ?: $imgixApiKey;
            if (!$apiKey) {
                continue;
            }

            $domains = $imgixConfig->domains;

            if (!\is_array($domains) || empty($domains)) {
                $msg = Craft::t('imager', 'Imgix config setting “domains” does not appear to be correctly set up. It needs to be an array of strings representing your Imgix source\'s domains.');
                Craft::error($msg, __METHOD__);
                throw new ImagerException($msg);
            }

            // Loop over this profile's domains
            foreach ($domains as $domain) {

                try {

                    /** @var LocalVolumeInterface|Volume|Local $volume */
                    $volume = $asset->getVolume();

                    // Get the image's path
                    if (($imgixConfig->useCloudSourcePath === true) && isset($volume->subfolder) && \get_class($volume) !== 'craft\volumes\Local') {
                        $path = implode('/', [$volume->subfolder, $asset->getPath()]);
                    } else {
                        $path = $asset->getPath();
                    }

                    // Build base URL for the image on Imgix
                    $builder = new UrlBuilder([$domain], $imgixConfig->useHttps, null, null, false);
                    $url = $builder->createURL(\str_replace('%2F', '/', \urlencode($path)));

                    $this->purgeUrlFromImgix($url, $apiKey);

                } catch (\Throwable $e) {
                    Craft::error($e->getMessage(), __METHOD__);
                    throw new ImagerException($e->getMessage(), $e->getCode(), $e);
                }

            }

        }

    }

}
