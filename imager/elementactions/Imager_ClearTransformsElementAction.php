<?php
namespace Craft;

class Imager_ClearTransformsElementAction extends BaseElementAction
{
    public function getName()
    {
        return Craft::t('Clear Imager transforms');
    }

    public function isDestructive()
    {
        return false;
    }

    public function performAction(ElementCriteriaModel $criteria)
    {
        $assets = $criteria->find();
        foreach ($assets as $asset) {
            craft()->imager->removeTransformsForAsset($asset);
        }
        $this->setMessage(Craft::t('Transforms were removed'));
        return true;
    }
}