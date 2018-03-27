<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\imager\services;

use aelvan\imager\lib\Potracio;
use aelvan\imager\models\LocalSourceImageModel;
use craft\base\Component;

use aelvan\imager\exceptions\ImagerException;
use Imagine\Exception\RuntimeException;
use Imagine\Image\Box;

/**
 * PlaceholderService Service
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    André Elvan
 * @package   Imager
 * @since     2.0.0
 */
class PlaceholderService extends Component
{
    private $defaults = [
        'type' => 'svg',
        'width' => 1,
        'height' => 1,
        'color' => null,
        'source' => null,
        'fgColor' => null,
        'size' => 1,
        'silhouetteType' => '',
    ];

    /**
     * Main public placeholder method.
     * 
     * @param null $config
     * @return string
     * @throws ImagerException
     */
    public function placeholder($config = null): string
    {
        $config = array_merge($this->defaults, $config ?? []);

        switch ($config['type']) {
            case 'svg':
                return $this->placeholderSVG($config);
            case 'gif':
                return $this->placeholderGIF($config);
            case 'silhouette':
                return $this->placeholderSilhuette($config);
        }

        return '';
    }

    /**
     * Returns a SVG placeholder
     * 
     * @param $config
     * @return string
     */
    private function placeholderSVG($config): string
    {
        $width = $config['width'];
        $height = $config['height'];
        $color = $config['color'] ?? 'transparent';

        return 'data:image/svg+xml;charset=utf-8,' . rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' width='$width' height='$height' style='background:$color'/>");
    }

    /**
     * Returns a GIF placeholder.
     * 
     * @param $config
     * @return string
     */
    private function placeholderGIF($config): string
    {
        $width = $config['width'];
        $height = $config['height'];
        $color = $config['color'] ?? 'transparent';

        if ($width === 1 && $height === 1 && $color === 'transparent') {
            return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        }

        $imagineInstance = $this->createImagineInstance();
        
        if ($imagineInstance === null) {
            return '';
        }
        
        $palette = new \Imagine\Image\Palette\RGB();
        $col = $palette->color($color);
        $image = $imagineInstance->create(new Box($width, $height), $col);
        $data = $image->get('gif');
        
        return 'data:image/gif;base64,' . base64_encode($data);
    }

    /**
     * Returns a silhouette placeholder.
     * 
     * @param $config
     * @return string
     * @throws ImagerException
     */
    private function placeholderSilhuette($config): string
    {
        $source = $config['source'] ?? null;
        $size = $config['size'];
        $color = $config['color'] ?? '#fefefe';
        $fgColor = $config['fgColor'] ?? '#e0e0e0';
        $silhouetteType = $config['silhouetteType'];
        
        if ($source === null) {
            throw new ImagerException('Placeholder of type "silhouette" needs a source image.');
        }
        
        try {
            $sourceModel = new LocalSourceImageModel($source);
            $sourceModel->getLocalCopy();
        } catch (ImagerException $e) {
            return null;
        }
        
        $tracer = new Potracio();
        $tracer->loadImageFromFile($sourceModel->getFilePath());
        $tracer->process();
        $data = $tracer->getSVG($size, $silhouetteType, $color, $fgColor);
        
        return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($data);
    }
    
    /**
     * Creates the Imagine instance depending on the chosen image driver.
     *
     * @return \Imagine\Gd\Imagine|\Imagine\Imagick\Imagine|null
     */
    private function createImagineInstance()
    {
        $extension = mb_strtolower(\Craft::$app->getConfig()->getGeneral()->imageDriver);
        
        try {
            if ($extension === 'gd') {
                return new \Imagine\Gd\Imagine();
            }

            if ($extension === 'imagick') {
                return new \Imagine\Imagick\Imagine();
            }
        } catch (RuntimeException $e) {
            // just ignore for now
        }

        return null;
    }
}
