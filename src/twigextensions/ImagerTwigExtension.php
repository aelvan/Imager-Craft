<?php
/**
 * Imager plugin for Craft CMS 3.x
 *
 * Image transforms gone wild
 *
 * @link      https://www.vaersaagod.no
 * @copyright Copyright (c) 2017 André Elvan
 */

namespace aelvan\imager\twigextensions;

use Craft;

use aelvan\imager\Imager as Plugin;

/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    André Elvan
 * @package   Imager
 * @since     2.0.0
 */
class ImagerTwigExtension extends \Twig_Extension
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'Imager';
    }

    /**
     * Returns an array of Twig filters, used in Twig templates via:
     *
     *      {{ 'something' | someFilter }}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('srcset', [$this, 'srcsetFilter']),
        ];
    }
    
    /**
     * Twig filter interface for srcset
     *
     * @param array $images
     * @param string $descriptor
     *
     * @return string
     */
    public function srcsetFilter($images, $descriptor='w')
    {
        return Plugin::$plugin->imager->srcset($images, $descriptor);
    }
}
