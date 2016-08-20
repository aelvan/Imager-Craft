<?php namespace Craft;

class ImagerController extends BaseController
{
    protected $allowAnonymous = array('actionClearTransforms', 'actionClearRemoteImages');

    /**
     * Controller action to clear image transforms
     */
    public function actionClearTransforms()
    {
        $key = craft()->request->getParam('key');
        $setKey = craft()->imager->getSetting('clearKey');
        
        if (!$setKey || $key != $setKey) {
            print_r('Unauthorized key');
            craft()->end();
        }
        
        craft()->imager->deleteImageTransformCaches();
        
        if (craft()->request->getPost('redirect')) {
            $this->redirectToPostedUrl();
        }
        
        print_r('Your imager transforms cache was cleared!');
        craft()->end();
    }

    /**
     * Controller action to clear remote images
     */
    public function actionClearRemoteImages()
    {
        $key = craft()->request->getParam('key');
        $setKey = craft()->imager->getSetting('clearKey');
        
        if (!$setKey || $key != $setKey) {
            print_r('Unauthorized key');
            craft()->end();
        }
        
        craft()->imager->deleteRemoteImageCaches();
        
        if (craft()->request->getPost('redirect')) {
            $this->redirectToPostedUrl();
        }
        
        print_r('Your remote images cache was cleared!');
        craft()->end();
    }
}