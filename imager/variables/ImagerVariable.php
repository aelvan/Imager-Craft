<?php
namespace Craft;

class ImagerVariable
{
    /**
     * @param $file
     * @param $transform
     * @param $configOverrides
     * @return mixed
     */
    public function transformImage($file, $transform, $configOverrides = null)
    {
        $image = craft()->imager->transformImage($file, $transform, $configOverrides);
        return $image;
    }

    /**
     * Takes an array of Imager_ImageModel (or anything else that supports getUrl() and getWidth())
     * and returns a srcset string
     *
     * todo : Implement support for other descriptors
     *
     * @param Array $images
     * @param string $descriptor
     * @return string
     */
    public function srcset($images, $descriptor = 'w')
    {
        $r = '';

        foreach ($images as $image) {
            $r .= $image->getUrl() . ' ' . $image->getWidth() . 'w, ';
        }

        return substr($r, 0, strlen($r) - 2);
    }

    /**
     * Returns a base64 encoded transparent pixel. Useful for adding as src on img tags for validation when using srcset.
     * 
     * @return string
     */
    public function base64Pixel()
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
    }
}