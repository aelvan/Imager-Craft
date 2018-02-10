<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2018 André Elvan
 */

namespace aelvan\imager\controllers;

use Craft;
use craft\web\Controller;

use aelvan\imager\Imager as Plugin;

/**
 * Class CacheController
 *
 * @package aelvan\imager\controllers
 */
class CacheController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var array
     */
    protected $allowAnonymous = ['actionClearTransforms', 'actionClearRemoteImages'];

    // Public Methods
    // =========================================================================

    /**
     * Controller action to clear image transforms
     *
     * @throws \yii\base\ErrorException
     */
    public function actionClearTransforms()
    {
        $config = Plugin::$plugin->getSettings();
        $request = Craft::$app->getRequest();

        $key = $request->getParam('key', '');
        $setKey = $config->clearKey;

        if ($setKey === '' || $key != $setKey) {
            throw new \Exception('Unautorized key');
        }

        Plugin::$plugin->imager->deleteImageTransformCaches();

        return true;
    }

    /**
     * Controller action to clear remote images
     *
     * @throws \yii\base\ErrorException
     */
    public function actionClearRemoteImages()
    {
        $config = Plugin::$plugin->getSettings();
        $request = Craft::$app->getRequest();

        $key = $request->getParam('key', '');
        $setKey = $config->clearKey;

        if ($setKey === '' || $key != $setKey) {
            throw new \Exception('Unautorized key');
        }

        Plugin::$plugin->imager->deleteRemoteImageCaches();

        return true;
    }
}
