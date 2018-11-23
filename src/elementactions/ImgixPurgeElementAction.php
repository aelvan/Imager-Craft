<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2018 André Elvan
 */

namespace aelvan\imager\elementactions;

use aelvan\imager\Imager;

use Craft;
use craft\elements\db\ElementQueryInterface;
use craft\base\ElementAction;

use aelvan\imager\Imager as Plugin;

class ImgixPurgeElementAction extends ElementAction
{

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('imager', 'Purge from Imgix');
    }

    /**
     * Purges selected image Assets from Imgix
     *
     * @param ElementQueryInterface $query
     *
     * @return bool
     */
    public function performAction(ElementQueryInterface $query): bool
    {

        $imagesToPurge = $query->kind('image')->all();

        if (empty($imagesToPurge)) {
            $this->setMessage(Craft::t('imager', 'No images to purge'));
            return true;
        }

        /** @var Imager $imagerPlugin */
        $imagerPlugin = Plugin::$plugin;

        try {
            foreach ($imagesToPurge as $imageToPurge) {
                $imagerPlugin->imgix->purgeAssetFromImgix($imageToPurge);
            }
        } catch (\Throwable $e) {
            $this->setMessage($e->getMessage());
            return false;
        }

        $numImagesToPurge = \count($imagesToPurge);
        if ($numImagesToPurge > 1) {
            $this->setMessage(Craft::t('imager', 'Purging {count} images from Imgix...', [
                'count' => $numImagesToPurge,
            ]));
            return true;
        }

        $this->setMessage(Craft::t('imager', 'Purging image from Imgix...'));
        return true;
    }
}
