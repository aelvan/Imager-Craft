Imager for Craft
=====
Imager is a plugin for doing image transforms in Craft templates. It does all the things that the built-in image transform functionality do – but more. And it's faster. At least if you want it to be. 

Whenever possible, Imager utilizes the image manipulation library [Imagine](http://imagine.readthedocs.org/) which Craft comes with and uses for it's own transform functionality.

**Features**:

- A convenient syntax for doing a bunch of image transforms in one go.  
- Transforms are completely file-based, no database queries needed.
- You can transform both images in your asset sources, local and cloud-based ones, and external images on any url.
- Transformed images are placed in their own folder, outside of the asset source folder.
- You can even upload and serve the transformed images from AWS.
- Optimize your created images automatically with jpegoptim, jpegtran, optipng or TinyPNG.
- You can create interlaced/progressive images.
- In addition to jpeg, gif and png, you can save images in webp format (if you have the necessary server requirements).
- Crop position is relative (in percent) not confined to edges/center (but the built-in keywords still works).  
`{ width: 600, height: 600, mode: 'crop', position: '20% 65%' }`
- New cropZoom parameter for when you want to get a little closer.  
`{ width: 600, height: 600, mode: 'crop', position: '20% 65%', cropZoom: 1.5 }`
- New croponly mode. To crop, not resize.  
`{ width: 600, height: 600, mode: 'croponly', position: '20% 65%' }`
- New letterbox resize mode.    
`{ width: 600, height: 600, mode: 'letterbox', letterbox: { color: '#000', opacity: 0 } }`
- If you know the aspect ration you want, you don't have to calculate the extra height/width.    
`{ width: 800, ratio: 16/9 }`
- Basic image effects, including grayscale, negative, blur, sharpen, gamma and colorize.   
`{ effects: { sharpen: true, gamma: 1.4, colorize: '#ff9933' } }`
- Advanced effetcs, including color blend, tint, sepia, contrast, modulate, normalize, contrast stretch, unsharp mask, posterize and vignette (Imagick imagedriver only).  
`{ effects: { modulate: [100, 40, 100], colorBlend: ['rgb(255, 153, 51)', 0.5] } }`
- Your own choice of which resize filter to use. Speed vs. quality is up to you (Imagick imagedriver only).
- Concerned about people copying your images? You can add a watermark to them with Imager.  
`{ watermark: { image: logo, width: 80, height: 80, position: { right: 30, bottom: 30 }, opacity: 0.8, blendMode: 'multiply' } }`
- Imager also lets you get color information, dominant color and palette, from your images.

**For a quick look at what Imager can do, [check out the demo site](http://imager.vaersaagod.no/).**

Contents
---
* [Installation](#installation)
* [Configuration](#configuration)
* [Usage](#usage)
* [Template variables](#template-variables)
* [Transform parameters](#transform-parameters)
* [Effects](#effects)
* [Imager_ImageModel](#imager_imagemodel)
* [Caching and cache breaking](#caching-and-cache-breaking)
* [Performance](#performance)
* [Price, license and support](#price-license-and-support)
* [Changelog](#changelog)

---

    

Installation
---
1. Download the zip from this repository, unzip, and put the imager folder in your Craft plugin folder.
2. Enable the plugin in Craft (Settings > Plugins)
3. Create a new configuration file in the craft/config folder, named imager.php. Override any settings, the defaults are found in [imager/config.php](https://github.com/aelvan/Imager-Craft/blob/master/imager/config.php). 
4. Make sure you create the folder where Imager will store the images exists, and is writable. (your public folder)/imager is the default.

---

Configuration
---
All configuration settings can be found in the `config.php` file in the plugin folder ([imager/config.php](https://github.com/aelvan/Imager-Craft/blob/master/imager/config.php)). You can override these settings by creating an `imager.php` file in your config folder, and overriding parameters as needed.

### imagerSystemPath [string]
*Default: `$_SERVER['DOCUMENT_ROOT'] . '/imager/'`*  
File system path to the folder where you want to store the transformed images.

### imagerUrl [string]
*Default: `'/imager/'`*  
Url to the transformed images. The imagerUrl will be prepended to the path and filename of the transformed image. Can be a relative url, or a full url. If you upload files to AWS, you'd set the imagerUrl to the AWS/CloudFront URL, like so:

    'imagerUrl' => 'http://s3-eu-west-1.amazonaws.com/imagertransforms/',

### cacheEnabled [bool]
*Default: `true`*  
Enables or disables caching of transformed images.

### cacheDuration [int]
*Default: `1209600`*  
The cache duration of transformed images.

### cacheDurationRemoteFiles [int]
*Default: `1209600`*  
When a remote file is downloaded, it will be cached locally for this duration.

### jpegQuality [int]
*Default: `80`*  
Defines the JPEG compression level. Higher values equals better quality and bigger filesizes.  

### pngCompressionLevel [int]
*Default: `2`*  
Defines the PNG compression level. PNG compression is always lossless, so this setting doesn't have any effect on quality. It only affects speed and filesize. A lower value means faster compression, but bigger filesizes. A value of 0 means "no compression", which is the preferred setting if you're doing any post optimizations of png images (with Optipng or TinyPNG).

### webpQuality [int]
*Default: `80`*  
Defines the WEBP compression level. Higher values equals better quality and bigger filesizes.

### webpImagickOptions [array]
*Default: `array()`*  
Additional options you want to pass to Imagick when creating webp files. See [the ImageMagick documentation](http://www.imagemagick.org/script/webp.php) for possible options (although, all are not supported by Imagick).

Example on how to use it in config files:

    'webpImagickOptions' => array(
        'lossless' => 'true',
        'method' => '5',
    ),
    
Example on how to use it in your template code:
    
    {% set webpImage = craft.imager.transformImage(image, { width: 500, format: 'webp', webpImagickOptions: { lossless: 'true', method: '1' } }) %}

### useCwebp [bool]
*Default: `false`*  
If you don't have support for webp in the image driver you're using (GD or Imagick), you can set this to `true` to use the cwebp command line tool instead. cwebp needs to be installed
on the server, but is available on most linux distros.

When using cwebp, Imager will first create a temprary, high quality, transformed image in the source format (jpeg, png or gif), and then convert this to webp.
Since this results in two images being created for each transformed image, the operation is a bit slower (~15-20%) than using the image driver directly. 

### cwebpPath [string]
*Default: `/usr/bin/cwebp`*  
Path to the cwebp binary.

### cwebpOptions [string]
*Default: ``*  
Options to pass o the cwebp binary (use `cwebp -longhelp` to see available options). Quality (-q) is automatically added based on `webpQuality`.  

### interlace [bool|string]
*Default: `false`*  
*Allowed values: `false`, `true` (`'line'`), `'none'`, `'line'`, `'plane'`, `'partition'`*   
Defines the interlace method for creating progressive images. Imagick is required for the `plane` and `partition` methods.
[See demo here](http://imager.vaersaagod.no/?img=5&demo=interlaced) (throttle your connection).  

*GD only supports one interlace mode, so it only makes sense to set a specific interlace mode if you use Imagick.*

### allowUpscale [bool]
*Default: `true`*  
If set to `false`, images will never be upscaled.

### resizeFilter [string]
*Default: `'lanczos'`*  
*Allowed values: `'point'`, `'box'`, `'triangle'`, `'hermite'`, `'hanning'`, `'hamming'`, `'blackman'`, `'gaussian'`, `'quadratic'`, `'cubic'`, `'catrom'`, `'mitchell'`, `'lanczos'`, `'bessel'`, `'sinc'`*  
Sets the resize filter. Imagick is required. By default Craft uses the lanczos filter for interpolation when resizing. It yields good visual result, but is one of the slower ones. There is a bunch of different filters available, and Imager let's you decide which one to use (lanczos is still the default).

The difference in quality and encoding speed varies a lot, so you should choose what is most important for your project, speed or quality. The difference in speed will be more pronounced the bigger the original image is. The difference in quality will be more prononounced the bigger the resulting image is.

Here's the result of a speed test [posted on php.net](http://se1.php.net/manual/en/imagick.resizeimage.php#94493):

```
FILTER_POINT took: 0.334532976151 seconds
FILTER_BOX took: 0.777871131897 seconds
FILTER_TRIANGLE took: 1.3695909977 seconds
FILTER_HERMITE took: 1.35866093636 seconds
FILTER_HANNING took: 4.88722896576 seconds
FILTER_HAMMING took: 4.88665103912 seconds
FILTER_BLACKMAN took: 4.89026689529 seconds
FILTER_GAUSSIAN took: 1.93553304672 seconds
FILTER_QUADRATIC took: 1.93322920799 seconds
FILTER_CUBIC took: 2.58396601677 seconds
FILTER_CATROM took: 2.58508896828 seconds
FILTER_MITCHELL took: 2.58368492126 seconds
FILTER_LANCZOS took: 3.74232912064 seconds
FILTER_BESSEL took: 4.03305602074 seconds
FILTER_SINC took: 4.90098690987 seconds
```

### smartResizeEnabled [bool]
*Default: `false`*  
When set to `true`, the new smartResize method that was added in Craft 2.5 will be used when Imagick is installed. This method is based on [the research done by Dave Newton](http://www.smashingmagazine.com/2015/06/efficient-image-resizing-with-imagemagick/) and yields smaller filesizes without any noticeable loss of quality.

Using smartResize may drastically decrease performance, especially when resizing pngs, and is therefore disabled by default.   

### removeMetadata [bool]
*Default: `false`*   
Strips meta data from image, resulting in smaller images. *Only available with Imagick.*

### bgColor [string]
*Default: `''`*  
If an image is converted from a format that supports transparency, to a format that doesn't, you can specify which background color (in hexadecimal) the resulting image should have.

### position [string]
*Default: `'50% 50%'`*  
The default focal point to be used when cropping an image. This can either be in percent, where the first value is along the horizontal axis, and the second value is along the vertical axis. Or one of the string values used in Craft's default image transform functionality (ie 'center-center', 'top-right', etc).

### letterbox [array]
*Default: `array('color'=>'#000', 'opacity'=>0)`*  
Specifies the color and opacity to use for the background when using the letterbox resize method. Opacity is only applicable when saving the transformed file in png format.

### hashFilename [bool|string]
*Default: `'postfix'`*  
*Allowed values: `true`, `false`, `'postfix'`*   
When doing an transform, Imager creates a filename based on the properties in the transform. By default (`'postfix'`), the generated part of the filenamed is hashed, and added as a postfix to the original filename. This makes the filename still relevant for SEO purposes, but still reasonably short.

If set to `false`, the filename will contain the generated string in clear text. This can result in really long filenames, but is great for debugging purposes.

If set to `true`, the whole filename is hashed. This will result in a short, but obscured, filename.

### hashPath [bool]
*Default: `false`*  
When enabled, the path of the transformed asset will be hashed.  

### hashRemoteUrl [bool|string]
*Default: `false`*  
*Allowed values: `true`, `false`, `'host'`*   
When tranforming remote images, the hostname and remote path will be used as path inside your imager system path. If you want to shorten or obfuscate the remote url, you can set this to `true` or `'host'`.

If you set this to `true`, the whole url will be hashed and used as the path.

If set to `'host'`, only the hostname will be hashed, while the remote path will be kept. 

### instanceReuseEnabled [bool]
*Default: `false`*  
By default, both in Imager and Craft's built in transform functionality, the original image is loaded into memory for every transform. This ensures that the quality of the resulting transform is as good as possible. 

If set to `true`, the original image is only loaded once, and every transform will continue working on the same image instance. This significantly increases performance and memory use, but will most likely decrease quality. See [this demo page](http://imager.vaersaagod.no/?img=5&demo=batch-reuse) for an example of how it works.

### jpegoptimEnabled [bool]
*Default: `false`*  
Enable or disable image optimizations with [jpegoptim](https://github.com/tjko/jpegoptim).

### jpegoptimPath [string]
*Default: `'/usr/bin/jpegoptim'`*  
Sets the path to your jpegoptim executable.

### jpegoptimOptionString [string]
*Default: `'-s'`*  
Sets the options to use when running jpegoptim. By default it only strips out meta data.

### jpegtranEnabled [bool]
*Default: `false`*  
Enable or disable image optimizations with [jpegtran](http://jpegclub.org/jpegtran/).

### jpegtranPath [string]
*Default: `'/usr/bin/jpegtran'`*  
Sets the path to your jpegtran executable.

### jpegtranOptionString [string]
*Default: `'-optimize -copy none'`*  
Sets the options to use when running jpegoptim. By default huffman tables are optimized, and no markers are copied from the source file.

### optipngEnabled [bool]
*Default: `false`*  
Enable or disable image optimizations with [optipng](http://optipng.sourceforge.net/).

### optipngPath [string]
*Default: `'/usr/bin/optipng'`*  
Sets the path to your optipng executable.

### optipngOptionString [string]
*Default: `'-o5'`*  
Sets the options to use when running optipng. By default the image file is optimized with level 5 optimizations.

### tinyPngEnabled [bool]
*Default: `false`*  
Enable or disable image optimizations with [TinyPNG](https://tinypng.com/). TinyPNG is a remote service that provides an API for optimizing images.

### tinyPngApiKey [string]
*Default: `''`*  
The TinyPNG API key.

### optimizeType [string]
*Default: `'task'`*  
*Allowed values: `'task'`, `'runtime'`*   
By default all post-transform optimizations are done as a Craft task that is run after the request has ended. This speeds up the initial transform request, but makes non-optimized images available for a short while until the task has been run. If set to `'runtime'`, all optimizations will be run immediately.

### logOptimizations [bool]
*Default: `false`*  
Logs information about optimizations.

### awsEnabled [bool]
*Default: `false`*  
Enables or disables uploading of transformed images to AWS.

*Please note; even if images are uploaded to AWS, they will also be stored in the Imager system path for caching and cache breaking purposes. *

### awsAccessKey [string]
*Default: `''`*  
AWS access key.

### awsSecretAccessKey [string]
*Default: `''`*  
AWS secret key.

### awsBucket [string]
*Default: `''`*  
AWS bucket name.

### awsFolder [string]
*Default: `''`*  
Subfolder inside the AWS bucket where you want to put the transformed images.

### awsCacheDuration [int]
*Default: `1209600`*  
Cache duration of files on AWS.

*Please note; this has nothing to do with how long a transform is cached, it is only used to tell AWS what HTTP expiry headers to set on the file.*

### awsRequestHeaders [array]
*Default: `array()`*  
Additional request headers to send to AWS.

### awsStorageType [string]
*Default: `'standard'`*  
*Allowed values: `'standard'`, `'rrs'`*   
Sets the AWS storage type. 

### curlOptions [array]
*Default: `array()`*  
Allows you to add [cURL options](http://php.net/manual/en/function.curl-setopt.php) to be used when downloading images from an external host.    

**Example:**

    'curlOptions' => array(
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSLVERSION => 1,
      CURLOPT_SSL_CIPHER_LIST => 'TLSv1'
    ),

You can also override the default options, which currently is:  
   
    $defaultOptions = array(
      CURLOPT_HEADER => 0,
      CURLOPT_FOLLOWLOCATION => 1,
      CURLOPT_TIMEOUT => 30
    );   

Make sure you **don't** set `CURLOPT_FILE`, since it is set by Imager to be the output location for the downloaded file.   
   
### runTasksImmediatelyOnAjaxRequests [bool]  
*Default: `true`*  
Craft automatically runs any pending tasks on normal site requests, but not on ajax-request, leaving any optimization tasks that Imager has created in a queue that is run on the next CP request. By default Imager solves this by triggering `runPendingTasks` manually if the request was an ajax request, and a task was created (curl needed).   

If you for some reason want to disable this behavior, change this setting to `false`.   

---

Usage
---
If you've ever done an image transform in a Craft template, this probably looks familiar:

    {% set image = craft.assets().limit(1).first() %}
    <img src="{{ image.getUrl({ width: 1000 }) }}">

The same code using Imager would look like this:

    {% set image = craft.assets().limit(1).first() %}
    {% set transformedImage = craft.imager.transformImage(image, { width: 1000 })
    <img src="{{ transformedImage.url }}">

So far, it's more code than the Craft way. But, let's say you need to resize the image to six different widths, because you're using <picture> and srcset to serve up responsive images (in a modern and futureproof way). And you want the crop position on all the images to be in the bottom-right corner, an aspect ratio of 16:9, and while the large images should have a high jpeg quality, the smaller ones should be more optimized. 

Here's how the code would look with Imager:

	{% set transformedImages = craft.imager.transformImage(image, [
		{ width: 1200 }, 
		{ width: 1000 }, 
		{ width: 800 }, 
		{ width: 600, jpegQuality: 65 }, 
		{ width: 400, jpegQuality: 65 }
		], { ratio: 16/9, position: 'bottom-right', jpegQuality: 80 }) %}

The plugin also includes some additional methods that helps you streamline the creation of responsive images. With the above transformed images, you can output the appropriate srcset like this, with a base64-encoded placeholder in the src attribute:

    <img src="{{ craft.imager.base64Pixel(16, 9) }}" sizes="100vw" srcset="{{ craft.imager.srcset(transformedImages) }}">
    
Additional information about the template variables can be found in the "Template variables"-section below.    

### Relative image paths
In the above examples, an AssetFileModel is passed to the transformImage method. You can also pass an Imager_ImageModel, path or an url to an image that has already been transformed by Imager. This can be useful for increasing performance, or simplifying your template code. In the below example, an image is first resized and have effects applied to it, before being resized to the final sizes:

    {% set newBaseImage = craft.imager.transformImage(selectedImage, { 
    	width: 1000, 
    	height: 1000, 
    	effects: { modulate: [110, 100, 100], colorBlend: ['#ffcc33', 0.3], gamma: 1.2 },
    	jpegQuality: 95 }) %}
    
    {% set transformedImages = craft.imager.transformImage(newBaseImage, [
    	{ width: 600, height: 600 },
    	{ width: 500, height: 500 },
    	{ width: 400, height: 400 }
    	]) %}

### Transforming external images
You can also transform remote images by passing an url for an image to transformImage:

	{% set externalImage = 'http://www.nasa.gov/sites/default/files/styles/full_width_feature/public/thumbnails/image/pia19808-main_tight_crop-monday.jpg' %}

    {% set transformedImages = craft.imager.transformImage(externalImage, [
    	{ width: 600, height: 600 },
    	{ width: 500, height: 500 },
    	{ width: 400, height: 400 }
    	]) %}

When transforming external images, the remote image is downloaded to your `craft/storage/runtime` folder, and cached for the duration selected in the `cacheDurationRemoteFiles` config parameter. 

---

Template variables
---
### craft.imager.transformImage(image, transform [, transformDefaults=null, configOverrides=null])
The main transform method. Returns either an Imager_ImageModel (see documentation below) if just one transform object were passed, or an array of Imager_ImageModel if an array were passed. Takes the following parameters:

**image**: Image to be transformed. This can be either an AssetFileModel, a string to a previously transformed Imager image, or a string to an external image.   
**transform**: An object, or an array of objects, containing the transform parameters to be used. See the "Usage"- and "Transform parameters"-sections for more information.   
**transformDefaults**: An object containing any default transform settings that should be applied to each transform. If transform properties given here are specified in an individual transform, the property value of the individual transform will be used.    
**configOverrides**: An object containing any overrides to the default config settings that should be used for this transform. See the "Configuration"-section for information on which settings that can be overridden.

### craft.imager.base64Pixel([width=1, height=1])
Outputs a base64 encoded SVG image. 

**width**: Width of the placeholder. Defaults to '1'.  
**height**: Height of the placeholder. Defaults to '1'.

### craft.imager.srcset(images [, descriptor='w'])
Outputs a srcset string from an array of transformed images.

**images**: An array of Imager_ImageModel objects, or anything else that support the interface.  
**descriptior**: A string indicating which size descriptor should be used in the srcset. *Only 'w' is supported at the moment.*

### craft.imager.serverSupportsWebp()
Returns `true` or `false` depending on if the server has support for webp or not. This could either indicate built in support for webp in the current image driver, GD or Imagick, or the presence of the cwebp binary if this has been enabled.  

### craft.imager.clientSupportsForWebp()
Returns `true` or `false` depending on if the client has support for webp or not. This is deducted from the Accept header that the client sends.   
  
*If you use template caching, or any kind of front side cache (Varnish, Fastly, etc), make sure you create different caches based on if the client has support for webp or not. For template caching, adding a string to the key based on this variable, is one way to solve it. Example:*
  
    {% cache using key "my-content" ~ (craft.imager.clientSupportsForWebp ? "-with-webp") %}  
    ...
    {% endcache %}
 
### craft.imager.getDominantColor(image [, quality=10, colorValue='hex'])
Gets the dominant color of an image. Uses [Color Thief](https://github.com/ksubileau/color-thief-php) for all the magic.

**image**: Image to get dominant color from. Can be any of the types that transformImage can handle.  
**quality**: Calculation accuracy of the dominant color. 1 is the highest quality, 10 is the default. Be aware that there is a trade-off between quality and speed/memory consumption!    
**colorValue**: Indicates which data format the returned color is in. Allowed values are `'hex'` (default) and `'rgb'`. If rgb is selected, the value is an array with red as index 0, green as index 1 and blue as index 2.   

### craft.imager.getColorPalette(image [, $colorCount=8, quality=10, colorValue='hex'])
Gets the color palette of an image. Uses [Color Thief](https://github.com/ksubileau/color-thief-php) for all the magic.

**image**: Image to get palette from. Can be any of the types that transformImage can handle.  
**colorCount**: Number of colors to include in palette.  
**quality**: Calculation accuracy of the dominant color. 1 is the highest quality, 10 is the default. Be aware that there is a trade-off between quality and speed/memory consumption!    
**colorValue**: Indicates which data format the returned color is in. Allowed values are `'hex'` (default) and `'rgb'`. If rgb is selected, the value is an array with red as index 0, green as index 1 and blue as index 2.     

### craft.imager.hex2rgb(color)
Converts a hexadecimal color value to rgb. Input value must be a string. Output value is an array with red as index 0, green as index 1 and blue as index 2.

### craft.imager.rgb2hex(color)
Converts a rgb color value to hexadecimal. Input value must be an array with red as index 0, green as index 1 and blue as index 2. Output value is a string.

---

Transform parameters
---
The following parameters are available in the transform object.  

### format [string]
*Default: `null`*  
*Allowed values: `null`, `'jpg'`, `'png'`, `'gif'`, `'webp'`*   
Format of the created image. If unset (default) it will be the same format as the source image.

### width [int]
Width of the image, in pixels.

### height [int]
Height of the image, in pixels.

### ratio [int|float]
An aspect ratio (width/height) that is used to calculate the missing size, if width or height is not provided.

**Example:**

	{# Results in an image that is in 16:9 format, 800x450px #}
    {% set sixteenbynineImage = craft.imager.transformImage(image, { width: 800, ratio: 16/9 }) %}

### mode [string]
*Default: `'crop'`*  
*Allowed values: `'crop'`, `'fit'`, `'stretch'`, `'croponly'`, `'letterbox'`*   
The mode that should be used when resizing images.

**'crop'**: Crops the image to the given size, scaling the image to fill as much as possible of the size. 
**'fit'**: Scales the image to fit within the given size while maintaining the aspect ratio of the original image.  
**'stretch'**: Scales the image to the given size, stretching it if the aspect ratio is different from the original.   
**'croponly'**: Crops the image to the given size without any resizing.  
**'letterbox'**: Scales the image to fit within the given size, the same way as `'fit'`. It then expands the image to the given size, adding a specified color to either the top/bottom or left/right of the image. The color (and opacity if the image format supports it) can be controlled with the `letterbox` parameter. 

**Example:**

    {% set letterboxImage = craft.imager.transformImage(image, { width: 600, height: 600, mode: 'letterbox', letterbox: { color: '#000000', opacity: 1 } }) %}

### cropZoom [float]
*Default: 1*    
By default when cropping, the image will be resized to the smallest size needed to make the image fit the given crop size. By increasing the `cropZoom` value, the image will be resized to a bigger size before cropping. 

Example: If the original image is 1600x900px, and you resize it to 300x300px with mode 'crop' and a cropZoom value of 1.5, the image will; 1) be resized to 800x450px and 2) a crop of 300x300px will be made (the position depending on the position given).

### watermark [object]
*Default: null*    
Adds a watermark to your transformed image. Imager expects an object with the following properties:

**image**: The image that is to be used as watermark. Just as the image parameter in the craft.imager.transformImage method, this can be an AssetFileModel, a string to a previously transformed Imager image, or a string to an external image.   
**width**: Width of the watermark, in pixels.   
**height**: Height of the watermark, in pixels.    
**position**: An object which specifies left or right, top or bottom, offsets for the watermark.   
**opacity**: Opacity of the watermark. *Only available in Imagick.*  
**blendMode**: The blendmode to be used for the watermark. Possible values are 'blend', 'darken', 'lighten', 'modulate', 'multiply', 'overlay', and 'screen'. *Only available in Imagick.*   

**Example:**

    {% set logo = craft.assets({ id: 11 }).first() %}
    {% set watermarkedImage = craft.imager.transformImage(image, { 
    	width: 600, 
    	watermark: { image: logo, width: 80, height: 80, position: { right: 30, bottom: 30 }, opacity: 0.8, blendMode: 'multiply' }
    }) %}

[See this demo](http://imager.vaersaagod.no/?img=6&demo=watermarks) for examples on how the watermark feature works.

### effects [object]
*Default: null*
Adds image adjustments to the transformed image. If multiple adjustments are added, they will be applied in the order they appear in the object. All effects are documented in the Effects section below.

**Example:**

    {% set transformedImage = craft.imager.transformImage(image, { 
    	width: 600, 
    	effects: { grayscale: true, gamma: 1.5 }
    }) %}

### preEffects [object]
*Default: null*   
As with the `effects`, this adds image adjustments to the transformed image, but do so *before* the image has been resized or otherwise modified. For some adjustments, this will yield a better end result, but usually the trade-off is performance.

**In addition, the following configuration settings can be overridden for each transform:**

* jpegQuality
* pngCompressionLevel
* cacheEnabled
* cacheDuration
* interlace
* allowUpscale
* resizeFilter
* smartResizeEnabled
* removeMetadata
* bgColor
* position
* letterbox
* hashFilename
* hashRemoteUrl

---

Effects
---
**The following basic adjustments ([see demo](http://imager.vaersaagod.no/?img=6&demo=basic-image-adjustments)) works both in GD and Imagick, and are limited to what is available in [Imagine](http://imagine.readthedocs.org/en/latest/usage/effects.html):**

### grayscale [bool]
Converts the image to grayscale if set to `true`.

### negative [bool]
Converts the image to negative if set to `true`.

### blur [int|float|bool]
Blurs the image. If you're using GD as image driver, blur can only be toggled on/off, either with true/false or 1/0.

### sharpen [bool]
Sharpens the image to grayscale if set to `true`.

### gamma [int|float]
Adjusts the image gamma.

### colorize [string]
Colorizes the image. Expects a hexadecimal color value.

**The following advanced image adjustments ([see demo](http://imager.vaersaagod.no/?img=5&demo=advanced-image-adjustments)) are only available when using Imagick:**

### colorBlend [array]
Blends the image with the color and opacity specified. Example:
    
    {% set transformedImage = craft.imager.transformImage(image, { width: 500, effects: { colorBlend: ['rgb(255, 153, 51)', 0.5] } }) %}

### tint [array]
Tints the image using [Imagick's tintImage method](http://php.net/manual/en/imagick.tintimage.php). Example:

    {% set transformedImage = craft.imager.transformImage(image, { width: 500, effects: { colorBlend: ['rgb(255, 153, 51)', 'rgb(212, 212, 212)'] } }) %}

### sepia [int]
Converts the image to sepia tones. 

### contrast [bool|int|float]
Increases or decreases the contrast of the image. A value greater than 0 increases contrast while a negative value decreases it.

### modulate [array]
Let's you adjust brightness, saturation and hue with [Imagick's modulateImage method](http://php.net/manual/en/imagick.modulateimage.php). Example (drops saturation by 80%):

    {% set transformedImage = craft.imager.transformImage(image, { width: 500, effects: { modulate: [100, 20, 100] } }) %}

### normalize [bool]
Enhances the contrast of the image by normalizing the colorspace. Uses [Imagick's normalizeImage method](http://php.net/manual/en/imagick.normalizeimage.php).

### contrastStretch [array]
Enhances the contrast of a color image by adjusting the pixels color to span the entire range of colors available. Uses [Imagick's colorStretch method](http://php.net/manual/en/imagick.contraststretchimage.php). Example:

    {% set transformedImage = craft.imager.transformImage(image, { width: 500, effects: { contrastStretch: [500*500*0.10, 500*500*0.90] } }) %}


### posterize [array]
Reduces image colors by applying the posterize effect. Uses [Imagick's posterizeImage method](http://php.net/manual/en/imagick.posterizeimage.php). Example:

    {% set transformedImage = craft.imager.transformImage(image, { width: 500, effects: { posterize: [136, 'no'] } }) %}

The second parameter refers to which dither method is used. Allowed values are:
    
**'no'**: No dithering.    
**'riemersma'**: [Riemersma dithering](http://www.compuphase.com/riemer.htm).    
**'floydsteinberg'**: [Floyd–Steinberg dithering](https://en.wikipedia.org/wiki/Floyd%E2%80%93Steinberg_dithering).    
    
### unsharpmask [array]
Applies an unsharp mask with [Imagick's unsharpMaskImage method](http://php.net/manual/en/imagick.unsharpmaskimage.php). Example:

    {% set transformedImage = craft.imager.transformImage(image, { width: 500, effects: { unsharpMask: [0, 0.5, 1, 0.05] } }) %}

### vignette [array]
*The vignette effect is not yet finalized.*

---

Imager_ImageModel
---
The model returned by the craft.imager.transformImage method.

### Public attributes
**url**: URL to the image.   
**path**: System path to the image file.   
**extension**: File extension.   
**mimeType**: File mime type.   
**width**: Width of the image.   
**height**: Height of the image.    
**size**: Size of the image in bytes.    

### Public methods
**getUrl() [string]**   
URL to the image.   

**getPath() [string]**   
System path to the image file.   

**getExtension() [string]**  
File extension.   

**getMimeType() [string]**  
File mime type.   

**getWidth() [string]**  
Width of the image.   

**getHeight() [string]**  
Height of the image.   

**getSize($unit='b', $precision='2') [float]**  
Size of the image. Example:  

    {{ image.getSize() }} B 
	{{ image.getSize('k') }} KB 
	{{ image.getSize('m', 10) }} MB 
	{{ image.getSize('g', 10) }} GB

**getDataUri() [string]**  
Returns a data uri with the image base64 encoded as a string.   

**getBase64Encoded() [string]**  
Returns a string of the base64 encoded image data.   


---

Caching and cache breaking
---
When caching is enabled (`cacheEnabled` configuration setting set to `true`) transformed images are cached for the duration of the `cacheDuration` configuration setting. If an image file is replaced, the existing transforms will be deleted. If a file is moved, the transforms will also be regenerated, since Imager will not find the transforms in the new location.

It is possible to manually remove the generated transforms by going to Settings > Clear Caches, selecting "Imager image transform cache" and clicking "Clear!". You can also select the images you want to clear in the Assets element list, and choose "Clear Imager transforms" from the element action dropdown menu.

When transforming a remote image, the image will be downloaded and cached for the duration of the `cacheDurationRemoteFiles` configuration setting. You can manually remove the cached remote images by going to Settings > Clear Caches, selecting "Imager remote images cache" and clicking "Clear!".

---

Price, license and support
---
The plugin is released under the MIT license, meaning you can do what ever you want with it as long as you don't blame me. **It's free**, which means there is absolutely no support included, but you might get it anyway. Just post an issue here on github if you have one, and I'll see what I can do. :)

---

Changelog
---
See [releases.json](https://raw.githubusercontent.com/aelvan/Imager-Craft/master/releases.json).
