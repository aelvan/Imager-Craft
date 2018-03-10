<?php

namespace aelvan\imager\optimizers;

use Craft;


class KrakenOptimizer implements ImagerOptimizeInterface
{

    public static function optimize(string $file, array $settings)
    {

        $kraken = new \Kraken($settings['apiKey'], $settings['apiSecret']);
        $params = [
            'file' => $file,
            'wait' => true
        ];

        if (isset($settings['additionalParams']) && \is_array($settings['additionalParams'])) {
            $params = array_merge($params, $settings['additionalParams']);
        }

        $data = $kraken->upload($params);

        if ($data['success'] === true) {
            self::storeOptimizedFile($file, $data);
        } else {
            Craft::error('Could not validate connection to TinyPNG, image was not optimized.', __METHOD__);
        }
    }

    private static function storeOptimizedFile(string $file, array $result)
    {
        return copy($result['kraked_url'], $file);
    }
}
