<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\imager\optimizers;

interface ImagerOptimizeInterface
{
    /**
     * @param string $file
     * @param array|null $settings
     */
    public static function optimize(string $file, array $settings);
}