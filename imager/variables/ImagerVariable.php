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

    public function srcset($images, $descriptor = 'w')
    {
        $r = '';
        
        foreach ($images as $image) {
            $r .= $image->getUrl() . ' ' . $image->getWidth() .'w, ';
        }

        return substr($r, 0, strlen($r)-2);
    }
}