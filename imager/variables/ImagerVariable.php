<?php
namespace Craft;

class ImagerVariable
{
	public function transformImage($file, $transform, $configOverrides = null)
	{
    $image = craft()->imager->transformImage($file, $transform, $configOverrides);
		return $image;
	}
}