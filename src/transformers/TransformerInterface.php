<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\imager\transformers;

use aelvan\imager\exceptions\ImagerException;
use craft\elements\Asset;

interface TransformerInterface
{
    /**
     * @param Asset|string $image
     * @param array        $transforms
     *
     * @return array|null
     *
     * @throws ImagerException
     */
    public function transform($image, $transforms);

}
