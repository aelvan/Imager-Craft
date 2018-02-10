<?php

namespace aelvan\imager\optimizers;

use Craft;

use Tinify\Exception;

class TinypngOptimizer implements ImagerOptimizeInterface
{

    public static function optimize(string $file, array $settings)
    {
        try {
            \Tinify\setKey($settings['apiKey']);
            \Tinify\validate();
            \Tinify\fromFile($file)->toFile($file);
        } catch (Exception $e) {
            Craft::error('Could not validate connection to TinyPNG, image was not optimized.', __METHOD__);
        }
    }
}