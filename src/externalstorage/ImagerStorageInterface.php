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

interface ImagerStorageInterface
{
    /**
     * @param string $file
     * @param string $uri
     * @param bool   $isFinal
     * @param array  $settings
     */
    public static function upload(string $file, string $uri, bool $isFinal, array $settings);
}