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