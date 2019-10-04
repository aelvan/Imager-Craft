Imager for Craft 3.x
=====
Imager is a plugin for doing image transforms in Craft templates. It does all the things that the built-in image transform functionality do – but much, much more.  

### Support Open Source. Support Imager. Buy beer.

*Imager is [licensed under a MIT license](#price-license-and-support), which means that it's completely free open source software, and 
you can use it for whatever and however you wish. If you're using Imager and want to support the development, buy me a beer over at 
[Beerpay](https://beerpay.io/aelvan/Imager-Craft)!* 

[![Beerpay](https://beerpay.io/aelvan/Imager-Craft/badge.svg?style=beer)](https://beerpay.io/aelvan/Imager-Craft)

Features
---

- The most efficient template syntax for doing a bunch of image transforms in one go.
- Transforms are completely file based, no database queries needed.
- Transform Assets (local and cloud-based ones), local images, external images, or even the transformed images themselves.
- Upload and serve transforms from Amazon S3 or Google Cloud Storage. Or, write your own storage interface to use whichever cloud service you want.
- Optimize your transformed images with jpegoptim, jpegtran, mozjpeg, optipng, pngquant, gifsicle, TinyPNG, Imagemin or Kraken. Or, write your own optimizer interface for whichever post-optimization tool or service you want.
- Support for offloading all your transforms to Imgix.
- Support for interlaced/progressive images.
- Support for animated gifs.
- In addition to jpeg, gif and png, you can save images in webp format (if you have the necessary server requirements).
- Crop position is relative (in percent) not confined to edges/center (but the built-in keywords still works).  
`{ width: 600, height: 600, mode: 'crop', position: '20% 65%' }`
- New cropZoom parameter for when you want to get a little closer.  
`{ width: 600, height: 600, mode: 'crop', position: '20% 65%', cropZoom: 1.5 }`
- New croponly mode. To crop, not resize.  
`{ width: 600, height: 600, mode: 'croponly', position: '20% 65%' }`
- Easily use Craft's built in focal point when cropping.  
`{ width: 600, height: 600, position: asset.getFocalPoint() }`
- New letterbox resize mode.    
`{ width: 600, height: 600, mode: 'letterbox', letterbox: { color: '#000', opacity: 0 } }`
- If you know the aspect ratio you want, you don't have to calculate the extra height/width.    
`{ width: 800, ratio: 16/9 }`
- Basic image effects, including grayscale, negative, blur, sharpen, gamma and colorize.   
`{ effects: { sharpen: true, gamma: 1.4, colorize: '#ff9933' } }`
- Advanced effects, including color blend, tint, sepia, contrast, modulate, normalize, contrast stretch, unsharp mask, posterize and vignette (Imagick imagedriver only).  
`{ effects: { modulate: [100, 40, 100], colorBlend: ['rgb(255, 153, 51)', 0.5] } }`
- Your own choice of which resize filter to use. Speed vs. quality is up to you (Imagick imagedriver only).
- Concerned about people copying your images? You can add a watermark to them with Imager.  
`{ watermark: { image: logo, width: 80, height: 80, position: { right: 30, bottom: 30 }, opacity: 0.8, blendMode: 'multiply' } }`
- Imager also lets you get color information, dominant color and palette, from your images.
- Imager also includes a bunch of color utilities for getting brightness, hue, lightness, percieved brightness, relative luminance, saturation, brightness difference, color difference and (puh!) contrast ratio. 

---

Relevant articles
---
[What's new in Imager 2.0?](https://github.com/aelvan/Imager-Craft/wiki/What's-new-in-Imager-2.0%3F)  
[Support for Imgix in Imager](https://www.vaersaagod.no/en/support-for-imgix-in-imager-for-craftcms) (Imager 1.x/Craft 2)      
[9 tips for speeding up your Imager transforms](https://www.vaersaagod.no/en/9-tips-for-speeding-up-your-imager-transforms-in-craftcms) (Imager 1.x/Craft 2)          
[Using WebP images in Craft with Imager](https://www.vaersaagod.no/en/using-webp-images-in-craft-with-imager) (Imager 1.x/Craft 2)      

---

Installation
---
To install Imager, follow these steps:

1. Install with composer via `composer require aelvan/imager` from your project directory.
2. Install the plugin in the Craft Control Panel under Settings > Plugins, or from the command line via `./craft install/plugin imager`.

Imager 2.0 requires Craft 3.x. The Craft 2 version is available in [the master branch](https://github.com/aelvan/Imager-Craft/blob/master).

---

Configuration
---

All configuration settings can be overridden by creating an `imager.php` file in your config folder, and adding parameters as needed. Please refer to the [Settings.php file](https://github.com/aelvan/Imager-Craft/blob/craft3/src/models/Settings.php) for additional pointers regarding available parameters and defaults. 

### transformer [string]
*Default: `'craft'`*  
*Allowed values: `'craft'`, `'imgix'`*  
Transformers is a new concept in Imager 2.0, which provides an overall interface for doing image transforms. There are two built-in transformers at the moment, the default `'craft'` which transforms images on your webserver using the Imagine library that Craft ships with and GD or Imagick, and `'imgix'` which offloads your image transforms to [Imgix](https://www.imgix.com/).

If you want to use Imgix, please refer to the `imgixProfile` and `imgixConfig` config settings, which are required.

### imagerSystemPath [string]
*Default: `'@webroot/imager/'`*  
File system path to the folder where you want to store the transformed images. 

**Please note:** This folder acts as the cache for transformed images, even if you choose to upload images to some other storage (ie AWS or similar). If you use atomic deploys (ServerPilot, Opworks, or similar), you'd want this folder to be on a shared storage.

### imagerUrl [string]
*Default: `'/imager/'`*  
Url to the transformed images. The imagerUrl will be prepended to the path and filename of the transformed image. Can be a relative url, or a full url. If you upload files to AWS, you'd set the imagerUrl to the AWS/CloudFront URL, like so:

    'imagerUrl' => 'http://s3-eu-west-1.amazonaws.com/imagertransforms/',

### cacheEnabled [bool]
*Default: `true`*  
Enables or disables caching of transformed images.

### cacheRemoteFiles [bool]
*Default: `true`*  
Enables or disables caching of remote files. This includes files on asset sources that are not local, and images on remote URLs.

By default images will be downloaded to your storages folder when first encountered, and cached for the duration of `cacheDurationRemoteFiles`. If you disable it, the downloaded images will be deleted automatically _after each transform request_. 

### cacheDuration [int]
*Default: `1209600`*  
The cache duration of transformed images.

### cacheDurationRemoteFiles [int]
*Default: `1209600`*  
When a remote file is downloaded, it will be cached locally for this duration.

### cacheDurationExternalStorage [int]
*Default: `1209600`*  
The cache duration that is set on images uploaded to external storages.

### cacheDurationNonOptimized [int]
*Default: `300`*  
The cache duration that is set on images uploaded to external storages if a post optimization for the image is scheduled. 

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
By default, Imager will use the native focal point for Asset elements. For other types of source elements, like external images, this setting will be used as default when cropping an image. 

### letterbox [array]
*Default: `array('color'=>'#000', 'opacity'=>0)`*  
Specifies the color and opacity to use for the background when using the letterbox resize method. Opacity is only applicable when saving the transformed file in png format. Animated gifs will always have transparent backgrounds.

### useFilenamePattern [bool]
*Default: `true`*  
When enabled, the path of the transformed asset will be created using the `filenamePattern`.  

### filenamePattern [bool]
*Default: `'{basename}_{transformString|hash}.{extension}'`*  
Pattern that defines how the filename of the transformed file should look. The following variables are available:

**{basename}:** The base name of the original file that was transformed (everything before the extension).  
**{extension}:** The extension of the transformed file.  
**{fullname}:** The full name of the transformed file, basename plus the transform string.  
**{transformString}:** The generated transform string.  

`{basename}`, `{fullname}`, and `{transformString}` can be hashed, either with a long md5 hash, or a shorter, truncated one based the `shortHashLength` config setting.

**Examples:**

    // Show name and transform string (good for debugging
    'filenamePattern' => '{fullname}.{extension}'

    // Show name and a hashed transform string (default)
    'filenamePattern' => '{basename}_{transformString|hash}.{extension}'

    // Show name and a shorter hashed transform string 
    // (prettier, but could result in name collisions (not likely though))
    'filenamePattern' => '{basename}_{transformString|shorthash}.{extension}'

You could use this config setting in your templates to create more SEO-friendly image filenames.

**Example:**

    {% set employeeImage = craft.imager.transformImage(employee.image, { width: 400 }, {}, 
        { filenamePattern: employee.name ~ '_{fullname|shorthash}.{extension}' }) 
    %}

Make sure to always include the transform string in some way or another to avoid transforms getting the same name and overwriting each other.

### shortHashLength [int]
*Default: `10`*  
The length of the short hash when using the `shorthash` filter in `filenamePattern`. 

### hashFilename [bool|string]
*`hashFilename` has been deprecated, use `filenamePattern` instead*
*Default: `'postfix'`*  
*Allowed values: `true`, `false`, `'postfix'`*   
When doing an transform, Imager creates a filename based on the properties in the transform. By default (`'postfix'`), the generated part of the filenamed is hashed, and added as a postfix to the original filename. This makes the filename still relevant for SEO purposes, but still reasonably short.

If set to `false`, the filename will contain the generated string in clear text. This can result in really long filenames, but is great for debugging purposes.

If set to `true`, the whole filename is hashed. This will result in a short, but obscured, filename.

### hashPath [bool]
*Default: `false`*  
When enabled, the path of the transformed asset will be hashed.  

### addVolumeToPath [bool]
*Default: `true`*  
When enabled, the handle for the volume is added to the path of the transformed image. When disabled, transformes from different volumes with the exact same name and transform string could overwrite each other.

### hashRemoteUrl [bool|string]
*Default: `false`*  
*Allowed values: `true`, `false`, `'host'`*   
When tranforming remote images, the hostname and remote path will be used as path inside your imager system path. If you want to shorten or obfuscate the remote url, you can set this to `true` or `'host'`.

If you set this to `true`, the whole url will be hashed and used as the path.

If set to `'host'`, only the hostname will be hashed, while the remote path will be kept. 

### useRemoteUrlQueryString [bool]
*Default: `false`*  
By default, query strings on external urls are not used when creating the filename of the transformed file, to improve caching. When enabled, any query string on the external url will be hashed and added as a part of the filename.  

### instanceReuseEnabled [bool]
*Default: `false`*  
By default, both in Imager and Craft's built in transform functionality, the original image is loaded into memory for every transform. This ensures that the quality of the resulting transform is as good as possible. 

If set to `true`, the original image is only loaded once, and every transform will continue working on the same image instance. This significantly increases performance and memory use, but will most likely decrease quality. See [this demo page](http://imager.vaersaagod.no/?img=5&demo=batch-reuse) for an example of how it works.

### noop [bool]
*Default: `false`*  
Setting `noop` (no operation) to `true` makes Imager return the source image untouched. Useful if you don't want to do image transforms in
some environments.

### suppressExceptions [bool]
*Default: `false` if devMode is enabled, otherwise `true`*  
By default Imager throws exceptions if file based operations fail, an external image can't be downloaded, etc. If `suppressExceptions` is set to `true`, Imager will instead log errors to the log file, and return `null` to the template.   

### convertToRGB [bool]
*Default: `false`*   
Enable this setting to ensure that transformed images are saved as RGB.    

### skipExecutableExistCheck [string]
*Default: `false`*  
By default Imager will check if the executables for the post-transform optimizations exists. If you have basedir restrictions on, or for some other reason doesn't want to do this check, set this to true.

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
  
### runJobsImmediatelyOnAjaxRequests [bool]  
*Default: `true`*  
Craft automatically runs any pending tasks on normal site requests, but not on ajax-request, leaving any optimization jobs that Imager has created in a queue that is run on the next CP request. By default Imager solves this by triggering `queue/run` manually if the request was an ajax request, and a task was created (curl needed). You can disable this behavior by changing this setting to `false`.   
  
### fillTransforms [bool]
*Default: `false`*  
Enable this setting to automatically fill a transform array with additional transforms based on `fillAttribute` and `fillInterval`.    

### fillAttribute [string]
*Default: `'width'`*  
Attribute to be used when filling in the transforms array. Can be any valid numeric attribute.
     
### fillInterval [string]
*Default: `200`*  
Interval to be used when filling in the transforms array. This should always be a positive integer, Imager will automatically figure out if
the transform has been ordered in an ascending or descending order.

### clearKey [string]  
*Default: `''`*  
Key to use when clearing the transform or remote images cache with the controller actions. An empty string means clearing is disabled.

### useForNativeTransforms [bool]  
*Default: `false`*  
Overrides Craft's internal transform functionality with Imager's. When enabled, `myAsset.getUrl({ width: 400 })` will result in an Imager transform.

### useForCpThumbs [bool]  
*Default: `false`*  
Overrides Craft's internal thumb transform in the control panel with Imager's. Mostly a good idea if you're using Imgix, if not you'd probably be better off not enabling this.

### imgixProfile [string]  
*Default: `'default'`*  
Imgix config settings profile to be used. Must correspond to a key in `imgixConfig`.

### imgixApiKey [string]
*Default: `''`*  
A valid Imgix API key is required to enable the [Imgix purging](https://docs.imgix.com/setup/purging-images) features. Note that in addition to setting the `imgixApiKey` setting, you can also add per-profile API keys for specific sources inside the `imgixConfig` configuration object – useful if you're using sources belonging to different Imgix accounts.

### imgixEnableAutoPurging [bool]
*Default: `true`*  
Attempts to [purge](https://docs.imgix.com/setup/purging-images) images automatically when the Asset is replaced, or edited with the Image Editor (i.e. cropped, resized etc). _An Imgix API key is required to enable purging._  

### imgixEnablePurgeElementAction [bool]
*Default: `true`*  
Adds a "Purge from Imgix" element action to the Asset index, for manually triggering purge. Note that if purging is not possible (i.e. if there's no Imgix API key set or there are no purgable profiles in the `imgixConfig` array) the element action will not display, regardless of this setting.  

### imgixConfig [array]  
An array of configuration objects for Imgix, where the key is the profile handle. The configuration object takes the following settings:

**domains (array):** An array of Imgix source domains.  

**useHttps (bool):** Indicates if generated Imgix URLs should be https or not.  

**signKey (string):** If you've protected your source with secure URLs, you must provide the sign key/token. An empty string indicates that the source is not secure.  

**sourceIsWebProxy (bool):** Indicates if your Imgix source is a web proxy or not. Note that web proxy sources will be excluded from purging.  

**useCloudSourcePath (bool):** If enabled, Imager will prepend the Craft source path to the asset path, before passing it to the Imgix URL builder. This makes it possible to have one Imgix source pulling images from many Craft volumes when they are on the same S3 bucket, but in different subfolder. This only works on volumes that implements a path setting (AWS S3 and GCS does, local volumes does not).  

**addPath (string|array):** Prepends a path to the asset's path. Can be useful if you have several volumes that you want to serve with one Imgix web folder source. If this setting is an array, the key should be the volume handle, and the value the path to add. See example below.  

**shardStrategy (string):** etermines the sharding strategy if more than one source is used. Allowed values are `cycle` and `crc`.  

**getExternalImageDimensions (bool):** Imager does its best at determining the dimensions of the transformed images. If the supplied asset is on Craft source, it's easy because Craft records the original dimensions of the image in the database. But if the image is external, it's not that easy. Imager will try to determine the size based on the transform parameters, and if both width and height, or ratio is provided, it'll usually be able to. But if you only transform by one attribute, it may not be possible. In these cases Imager will by default download the source image and check the dimensions to calculate the missing bits.

By disabling this setting, you're telling Imager to never download external images, and to just give up on trying to figure out the dimensions. If you supplied only width to the transform, height will then be set to 0. If you don't need to use height in your code, that's totally fine, and you've managed to squeeze out a bit more performance.

**defaultParams (array):** You can use this setting to set default parameters that you want passed to all your Imgix transforms. Example:

    'defaultParams' => ['auto'=>'compress,format', 'q'=>80]

The following example shows a setup that uses two Imgix sources, one that's pointed to a Craft volume, and one that is used for external images:

    'imgixConfig' => [
        'default' => [
            'domains' => ['imager.imgix.net'],
            'useHttps' => true,
            'signKey' => 'XxXxXxXx',
            'sourceIsWebProxy' => false,
            'useCloudSourcePath' => true,
            'shardStrategy' => 'cycle',
            'getExternalImageDimensions' => true,
            'defaultParams' => ['auto'=>'compress,format', 'q'=>80],
        ],
        'external' => [
            'domains' => ['imager-external.imgix.net'],
            'useHttps' => true,
            'signKey' => 'XxXxXxXx',
            'sourceIsWebProxy' => true,
            'useCloudSourcePath' => true,
            'shardStrategy' => 'cycle',
            'getExternalImageDimensions' => true,
            'defaultParams' => ['auto'=>'compress,format', 'q'=>80],
        ]
    ]

This example shows how you can serve several Craft volumes from one Imgix web folder source using `addPath`. The Imgix source should
be set up to point to a location that lets you append the path in `addPath`, and the assets full path, to it, to create the full, public URI:

    'imgixConfig' => [
        'default' => [
            'domains' => ['imager-multi.imgix.net'],
            'useHttps' => true,
            'signKey' => 'XxXxXxXx',
            'sourceIsWebProxy' => false,
            'addPath' => [
                'images' => 'images',            
                'documents' => 'documents',            
                'otherstuff' => 'other/stuff',            
            ],
            'shardStrategy' => 'cycle',
            'getExternalImageDimensions' => true,
            'defaultParams' => ['auto'=>'compress,format', 'q'=>80],
        ]
    ]
    
To use specify which profile to use in your templates you override `imgixProfile` like this:

    {% set transform = craft.imager.transformImage(externalUrl, { width: 400 }, {}, { imgixProfile: 'external' }) %}

**excludeFromPurge (bool):** Exclude this source from purging. Note that profiles with the `sourceIsWebProxy` setting set to `true` will be excluded from purging regardless of this value. _This setting affects both automatic purging when Assets are replaced (or edited with the Image Editor) and manual purges triggered by the element action._  

**apiKey (string):** Will override the `imgixApiKey` setting when Imager attempts to purge images for a particular profile. Useful if you use sources belonging to different Imgix accounts.  

### optimizeType [string]
*Default: `'job'`*  
*Allowed values: `'job'`, `'runtime'`*   
By default all post-transform optimizations are done as a Craft queue job that is run after the request has ended. This speeds up the initial transform request, but makes non-optimized images available for a short while until the task has been run. If set to `'runtime'`, all optimizations will be run immediately.

### optimizers [array]
*Default: `[]`*  
An array of handles to enabled optimizers. Optimizers needs to be configured in `optimizerConfig`. You can enable as many optimizers as you wish. 

**Example:**

    'optimizers' => ['jpegtran', 'gifsicle', 'pngquant']

They will be run in the order specified. It probably isn't a good idea to use several optimizers that optimizes the same file types.

Imager 2.0 comes with optimizers for jpegoptim, jpegtran, mozjpeg, optipng, pngquant, gifsicle, tinypng, kraken and imageoptim. More can be added through the new optimizer interface (more info coming!).

### optimizerConfig [array]
The Settings model provides the following default `optimizerConfig`: 

    'optimizerConfig' = [
        'jpegoptim' => [
            'extensions' => ['jpg'],
            'path' => '/usr/bin/jpegoptim',
            'optionString' => '-s',
        ],
        'jpegtran' => [
            'extensions' => ['jpg'],
            'path' => '/usr/bin/jpegtran',
            'optionString' => '-optimize -copy none',
        ],
        'mozjpeg' => [
            'extensions' => ['jpg'],
            'path' => '/usr/bin/mozjpeg',
            'optionString' => '-optimize -copy none',
        ],
        'optipng' => [
            'extensions' => ['png'],
            'path' => '/usr/bin/optipng',
            'optionString' => '-o2',
        ],
        'pngquant' => [
            'extensions' => ['png'],
            'path' => '/usr/bin/pngquant',
            'optionString' => '--strip --skip-if-larger',
        ],
        'gifsicle' => [
            'extensions' => ['gif'],
            'path' => '/usr/bin/gifsicle',
            'optionString' => '--optimize=3 --colors 256',
        ],
        'tinypng' => [
            'extensions' => ['png','jpg'],
            'apiKey' => '',
        ],
        'kraken' => [
            'extensions' => ['png', 'jpg', 'gif'],
            'apiKey' => '',
            'apiSecret' => '',
            'additionalParams' => [
                'lossy' => true,
            ]
        ],
        'imageoptim' => [
            'extensions' => ['png', 'jpg', 'gif'],
            'apiUsername' => '',
            'quality' => 'medium'
        ],
    ]

_Please note, the path and options given are just suggestions. You probably need to 
change this to reflect your environment, and make sure that the options do
what you want them to do. Also, not all options is necessarily available in
the compiled version of the optimizers (for instance, pngquant doesn't necessarily
have --strip compiled in)._

Configuration for additional, custom optimizers can also be added. 

### storages [array]
*Default: `[]`*  
An array of handles to enabled storages. Storages needs to be configured in `storageConfig`.

Imager 2.0 comes with support for Amazon S3 and Google Cloud Storage.

### storageConfig [array]
The Settings model provides the following default `storageConfig`: 

    'storageConfig' => [
        'aws' => [
            'accessKey' => '',
            'secretAccessKey' => '',
            'region' => '',
            'bucket' => '',
            'folder' => '',
            'requestHeaders' => array(),
            'storageType' => 'standard',
            'cloudfrontInvalidateEnabled' => false,
            'cloudfrontDistributionId' => '',
        ],
        'gcs' => [
            'keyFile' => '',
            'bucket' => '',
            'folder' => '',
        ],
    ]

Here's an example of how this could look when populated:

    'storageConfig' => [
        'aws'  => [
            'accessKey' => 'XYXYYYY99YXYZXXX1YXY',
            'secretAccessKey' => 'xY9xXXyYxXXyX9xYxxYyxy9XXyyxxy9XX99XYX9x',
            'region' => 'eu-west-1',
            'bucket' => 'transformbucket',
            'folder' => 'transforms',
            'requestHeaders' => array(),
            'storageType' => 'standard',
            'cloudfrontInvalidateEnabled' => true,
            'cloudfrontDistributionId' => 'YXYZ99ZX99YXY',
        ],
        'gcs' => [
            'keyFile' => '/absolute/path/to/key/gcs-31a21242cf7c.json',
            'bucket' => 'transformbucket',
            'folder' => 'transforms',
        ]
                
    ]
 
This is just provided as an example, make sure you override this with your own credentials.

And, make sure that you remember to enable the storage configs you want to use by default, 
using the `storages` config setting, like so:

    'storages' => ['aws'],
    'storageConfig' => [
        'aws'  => [
            'accessKey' => 'XYXYYYY99YXYZXXX1YXY',
            'secretAccessKey' => 'xY9xXXyYxXXyX9xYxxYyxy9XXyyxxy9XX99XYX9x',
            'region' => 'eu-west-1',
            'bucket' => 'transformbucket',
            'folder' => 'transforms',
            'requestHeaders' => array(),
            'storageType' => 'standard',
            'cloudfrontInvalidateEnabled' => true,
            'cloudfrontDistributionId' => 'YXYZ99ZX99YXY',
        ],
        'gcs' => [
            'keyFile' => '/absolute/path/to/key/gcs-31a21242cf7c.json',
            'bucket' => 'transformbucket',
            'folder' => 'transforms',
        ]
                
    ]
 
For more information about the concept of storages, please refer to 
[this section](https://github.com/aelvan/Imager-Craft/wiki/What's-new-in-Imager-2.0%3F#external-storages) 
in the "What's new in Imager 2.0" article. 

---

Usage
---
If you've ever done an image transform in a Craft template, this probably looks familiar:

    {% set image = craft.assets().one() %}
    <img src="{{ image.getUrl({ width: 1000 }) }}">

The same code using Imager would look like this:

    {% set image = craft.assets().one() %}
    {% set transformedImage = craft.imager.transformImage(image, { width: 1000 }) %}
    <img src="{{ transformedImage.url }}">

So far, it's more code than the Craft way. But, let's say you need to resize the image to six different widths, because you're using <picture> and srcset to serve up responsive images (in a modern and futureproof way). And you want the crop position on all the images to be in the bottom-right corner, an aspect ratio of 16:9, and while the large images should have a high jpeg quality, the smaller ones should be more optimized. 

Here's how the code would look with Imager:

	{% set transformedImages = craft.imager.transformImage(image, [
		{ width: 1200 }, 
		{ width: 1000 }, 
		{ width: 800 }, 
		{ width: 600, jpegQuality: 65 }, 
		{ width: 400, jpegQuality: 65 }
		], { ratio: 16/9, position: '100% 100%', jpegQuality: 80 }) %}
		
Imager 1.5.0 also introduced a convenient `fillTransforms` config setting which makes the above code even simpler:
		
	{% set transformedImages = craft.imager.transformImage(image, [
		{ width: 1200 }, 
		{ width: 600, jpegQuality: 65 }, 
		{ width: 400, jpegQuality: 65 }
		], { ratio: 16/9, position: '100% 100%', jpegQuality: 80 }, 
		{ fillTransforms: true }) %}

See the `fillTransforms`, `fillAttribute` and `fillInterval` settings for more information.
		
The plugin also includes some additional methods that helps you streamline the creation of responsive images. With the above transformed images, you can output the appropriate srcset like this, with a base64-encoded placeholder in the src attribute:

    <img src="{{ craft.imager.placeholder({ width: 160, height: 90 }) }}" sizes="100vw" srcset="{{ craft.imager.srcset(transformedImages) }}">
    
Additional information about the template variables can be found in the "Template variables"-section below.    

### Transform the transform
In the above examples, an Asset element is passed to the transformImage method. You can also pass a transformed image returned by Imager, a path or an url to an image that has already been transformed by Imager. This can be useful for increasing performance, or simplifying your template code. In the below example, an image is first resized and have effects applied to it, before being resized to the final sizes:

    {% set newBaseImage = craft.imager.transformImage(selectedImage, { 
    	width: 1200, 
    	height: 1200, 
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
The main transform method. Returns either a transformed image model (see documentation below) if just one transform object were passed, or an array of transformed image models if an array were passed. Takes the following parameters:

**image**: Image to be transformed. This can be either an Asset element, a string to a previously transformed Imager image, or a string to an external image.   
**transform**: An object, or an array of objects, containing the transform parameters to be used. See the "Usage"- and "Transform parameters"-sections for more information.   
**transformDefaults**: An object containing any default transform settings that should be applied to each transform. If transform properties given here are specified in an individual transform, the property value of the individual transform will be used.    
**configOverrides**: An object containing any overrides to the default config settings that should be used for this transform. See the "Configuration"-section for information on which settings that can be overridden.

### craft.imager.srcset(images [, descriptor='w'])
Outputs a srcset string from an array of transformed images.

**images**: An array of Imager_ImageModel objects, or anything else that support the interface.  
**descriptior**: A string indicating which size descriptor should be used in the srcset. 'w', 'h' and 'w+h' is supported at the moment. Please note that 'h' isn't standards-compliant, but is useful for instance when using Lazysizes and their bgset plugin.

### craft.imager.placeholder([config = null])
Outputs an image placeholder. The config object takes the following parameters:

**type**: Type of placeholder. Defaults to 'svg'. Available types are 'svg', 'gif' and 'silhouette'.  
**width**: Width of the placeholder. Defaults to '1'.   
**height**: Height of the placeholder. Defaults to '1'.  
**color**: Color of the placeholder. Defaults to 'transparent'.  
**source**: Source image that should be used to create silhouette style svgs. _Only relevant for silhouette placeholders_.  
**fgColor**: Foreground color for silhouette style svgs. Color is used as background. _Only relevant for silhouette placeholders_.  
**size**: Size multiplicator for silhouette style svgs. _Only relevant for silhouette placeholders_.   
**silhouetteType**: Type of silhouette, available values are '' and 'curve'. _Only relevant for silhouette placeholders_.  

### craft.imager.base64Pixel([width=1, height=1, color='transparent'])
_This method has been deprecated, please use `craft.imager.placeholder` instead._  
Outputs a base64 encoded SVG image. 

**width**: Width of the placeholder. Defaults to '1'.  
**height**: Height of the placeholder. Defaults to '1'.  
**color**: Color of the placeholder. Defaults to 'transparent'.  

### craft.imager.serverSupportsWebp()
Returns `true` or `false` depending on if the server has support for webp or not. This could either indicate built in support for webp in the current image driver, GD or Imagick, or the presence of the cwebp binary if this has been enabled.  

### craft.imager.clientSupportsWebp()
Returns `true` or `false` depending on if the client has support for webp or not. This is deducted from the Accept header that the client sends.   
  
*If you use template caching, or any kind of front side cache (Varnish, Fastly, etc), make sure you create different caches based on if the client has support for webp or not. For template caching, adding a string to the key based on this variable, is one way to solve it. Example:*
  
    {% cache using key "my-content" ~ (craft.imager.clientSupportsWebp ? "-with-webp") %}  
    ...
    {% endcache %}
 
### craft.imager.isAnimated(image)
Returns `true` or `false` depending on if the supplied image is animated or not (only gif support at the moment).   

### craft.imager.imgixEnabled()
Returns `true` or `false` depending on if Imgix is enabled.   

### craft.imager.getDominantColor(image [, quality=10, colorValue='hex'])
Gets the dominant color of an image. Uses [Color Thief](https://github.com/ksubileau/color-thief-php) for all the magic.

**image**: Image to get dominant color from. Can be any of the types that transformImage can handle.  
**quality**: Calculation accuracy of the dominant color. 1 is the highest quality, 10 is the default. Be aware that there is a trade-off between quality and speed/memory consumption!    
**colorValue**: Indicates which data format the returned color is in. Allowed values are `'hex'` (default) and `'rgb'`. If rgb is selected, the value is an array with red as index 0, green as index 1 and blue as index 2.   

### craft.imager.getColorPalette(image [, colorCount=8, quality=10, colorValue='hex'])
Gets the color palette of an image. Uses [Color Thief](https://github.com/ksubileau/color-thief-php) for all the magic.

**image**: Image to get palette from. Can be any of the types that transformImage can handle.  
**colorCount**: Number of colors to include in palette.  
**quality**: Calculation accuracy of the dominant color. 1 is the highest quality, 10 is the default. Be aware that there is a trade-off between quality and speed/memory consumption!    
**colorValue**: Indicates which data format the returned color is in. Allowed values are `'hex'` (default) and `'rgb'`. If rgb is selected, the value is an array with red as index 0, green as index 1 and blue as index 2.     

### craft.imager.hex2rgb(color)
Converts a hexadecimal color value to rgb. Input value must be a string. Output value is an array with red as index 0, green as index 1 and blue as index 2.

### craft.imager.rgb2hex(color)
Converts a rgb color value to hexadecimal. Input value must be an array with red as index 0, green as index 1 and blue as index 2. Output value is a string.

### craft.imager.getBrightness(color)
Calculates [color brightness](https://www.w3.org/TR/AERT#color-contrast) on a scale from 0 (black) to 255 (white).

### craft.imager.getPercievedBrightness(color)
Calculates the [perceived brightness](http://alienryderflex.com/hsp.html) of a color on a scale from 0 (black) to 255 (white).

### craft.imager.getRelativeLuminance(color)
Calculates the [relative luminance](https://www.w3.org/TR/WCAG20/#relativeluminancedef) of a color on a scale from 0 (black) to 1 (white).

### craft.imager.getBrightnessDifference(color1, $color2)
Calculates [brightness difference](https://www.w3.org/TR/AERT#color-contrast) on a scale from 0 to 255.
 
### craft.imager.getColorDifference(color1, $color2)
Calculates [color difference](https://www.w3.org/TR/AERT#color-contrast) on a scale from 0 to 765.
 
### craft.imager.getContrastRatio(color1, $color2)
Calculates the [contrast ratio](https://www.w3.org/TR/WCAG20/#contrast-ratiodef) between two colors on a scale from 1 to 21.

### craft.imager.getHue(color)
Get the hue channel of a color.

### craft.imager.getLightness(color)
Get the lightness channel of a color.

### craft.imager.getSaturation(color)
Get the saturation channel of a color.

### craft.imager.isBright(color [, threshold=127.5])
Checks brightness($color) >= $threshold. Accepts an optional $threshold float as the last parameter with a default of 127.5. 

### craft.imager.isLight(color [, threshold=50])
Checks lightness($color) >= $threshold. Accepts an optional $threshold float as the last parameter with a default of 50.0. 

### craft.imager.looksBright(color [, threshold=127.5])
Checks perceived_brightness($color) >= $threshold. Accepts an optional $threshold float as the last parameter with a default of 127.5. 
 
 

---

Twig filters
---
### srcset([descriptor='w'])
Outputs a srcset string from an array of transformed images.  

	{% set transformedImages = craft.imager.transformImage(image, [{ width: 400 },{ width: 1200 }], { ratio: 16/9 }, { fillTransforms: true }) %}

    <img src="{{ craft.imager.placeholder({ width: 16, height: 9 }) }}" sizes="100vw" srcset="{{ transformedImages | srcset }}">

---

Transform parameters
---
The following parameters are available in the transform object.  

### format [string]
*Default: `null`*  
*Transformers: Craft, Imgix*  
*Allowed values: `null`, `'jpg'`, `'png'`, `'gif'`, `'webp'`*   
Format of the created image. If unset (default) it will be the same format as the source image.

### width [int]
*Transformers: Craft, Imgix*  
Width of the image, in pixels.

### height [int]
*Transformers: Craft, Imgix*  
Height of the image, in pixels.

### ratio [int|float]
*Transformers: Craft, Imgix*  
An aspect ratio (width/height) that is used to calculate the missing size, if width or height is not provided.

**Example:**

	{# Results in an image that is in 16:9 format, 800x450px #}
    {% set sixteenbynineImage = craft.imager.transformImage(image, { width: 800, ratio: 16/9 }) %}

### mode [string]
*Default: `'crop'`*  
*Transformers: Craft, Imgix (all except `'croponly' mode`)*  
*Allowed values: `'crop'`, `'fit'`, `'stretch'`, `'croponly'`, `'letterbox'`*   
The mode that should be used when resizing images.

**'crop'**: Crops the image to the given size, scaling the image to fill as much as possible of the size. 
**'fit'**: Scales the image to fit within the given size while maintaining the aspect ratio of the original image.  
**'stretch'**: Scales the image to the given size, stretching it if the aspect ratio is different from the original.   
**'croponly'**: Crops the image to the given size without any resizing. *Only available when using the Craft transformer*.  
**'letterbox'**: Scales the image to fit within the given size, the same way as `'fit'`. It then expands the image to the given size, adding a specified color to either the top/bottom or left/right of the image. The color (and opacity if the image format supports it) can be controlled with the `letterbox` parameter. 

**Example:**

    {% set letterboxImage = craft.imager.transformImage(image, { width: 600, height: 600, mode: 'letterbox', letterbox: { color: '#000000', opacity: 1 } }) %}

### cropZoom [float]
*Default: 1*    
*Transformers: Craft, Imgix*  
By default when cropping, the image will be resized to the smallest size needed to make the image fit the given crop size. By increasing the `cropZoom` value, the image will be resized to a bigger size before cropping. 

Example: If the original image is 1600x900px, and you resize it to 300x300px with mode 'crop' and a cropZoom value of 1.5, the image will; 1) be resized to 800x450px and 2) a crop of 300x300px will be made (the position depending on the `position` given).

### frames [string]
*Transformers: Craft*  
Let's you extract only certain frames from an animated gif. The parameter takes a string in the format `'startFrame/endFrame@frameInterval'`.
End frame and frame interval is optional. Examples:

    // Get only first frame of animated gif
    {% set transformedImage = craft.imager.transformImage(animatedGif, { width: 300, frames: '0' }) %}

    // Get the first ten frames of animated gif
    {% set transformedImage = craft.imager.transformImage(animatedGif, { width: 300, frames: '0-9' }) %}

    // Get every fifth frame between frames 0 and 40
    {% set transformedImage = craft.imager.transformImage(animatedGif, { width: 300, frames: '0-40@5' }) %}

    // Get every fifth frame between the first and the last frame
    {% set transformedImage = craft.imager.transformImage(animatedGif, { width: 300, frames: '0-*@5' }) %}


### watermark [object]
*Default: null*    
*Transformers: Craft (use `imgixParams` for Imgix watermarks)*  
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
*Transformers: Craft (use `imgixParams` for Imgix effects)*  
Adds image adjustments to the transformed image. If multiple adjustments are added, they will be applied in the order they appear in the object. All effects are documented in the Effects section below.

**Example:**

    {% set transformedImage = craft.imager.transformImage(image, { 
    	width: 600, 
    	effects: { grayscale: true, gamma: 1.5 }
    }) %}

### preEffects [object]
*Default: null*   
*Transformers: Craft (use `imgixParams` for Imgix effects)*  
As with the `effects`, this adds image adjustments to the transformed image, but do so *before* the image has been resized or otherwise modified. For some adjustments, this will yield a better end result, but usually the trade-off is performance.

### imgixParams [object]
*Default: null*   
*Transformers: Imgix*  
Additional parameters that are passed to the [Imgix URL API](https://docs.imgix.com/apis/url) if Imgix is enabled. 

**In addition, most configuration settings can be overridden for each transform.**


---

Effects
---
**The following basic adjustments works both in GD and Imagick, and are limited to what is available in [Imagine](http://imagine.readthedocs.org/en/latest/usage/effects.html):**

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

**The following advanced image adjustments are only available when using Imagick:**

### colorBlend [array]
Blends the image with the color and opacity specified. Example:
    
    {% set transformedImage = craft.imager.transformImage(image, { width: 500, effects: { colorBlend: ['rgb(255, 153, 51)', 0.5] } }) %}

### tint [array]
Tints the image using [Imagick's tintImage method](http://php.net/manual/en/imagick.tintimage.php).

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
Enhances the contrast of a color image by adjusting the pixels color to span the entire range of colors available. Uses [Imagick's contrastStretch method](http://php.net/manual/en/imagick.contraststretchimage.php). Example:

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

### clut [array]
Applies a [clut (color lookup table) effect](http://stackoverflow.com/questions/36823310/imagick-gradient-map/36825769#36825769) 
to the image, using [Imagick's clutImage method](http://php.net/manual/en/imagick.clutimage.php). You probably want to use it
together with modulate to get a real duotone effect. Example:

    {% set transformedImage = craft.imager.transformImage(image, { width: 500, effects: { modulate: [100, 0, 100], clut: 'gradient:darkblue-aqua' } }) %}

The parameter is the image definition string sent to [Imagick's newPseudoImage method](http://php.net/manual/en/imagick.newpseudoimage.php). You can use any 
valid color values. Example with rgba colors:
 
    {% set transformedImage = craft.imager.transformImage(image, { width: 500, effects: { modulate: [100, 0, 100], clut: 'gradient:rgba(255,0,0,0.8)-rgba(255,255,0,1)' } }) %}

Please note that the alpha value doesn't actually make the color transparent, it just has the effect of moving the gradient's
center point towards the color with the least transparency.  

### quantize [array|int]
Reduces the number of colors in an image, using [Imagick's quantizeImage method](http://php.net/manual/en/imagick.quantizeimage.php). This can help
to reduce filesize, especially for gif images. Example:

    {% set transformedImage = craft.imager.transformImage(image, { width: 500, effects: { quantize: 32 } }) %}

The parameter can be an int, indicating the number of colors to reduce to, or an array that corresponds to the functions $numberColors (int), 
$treedepth (int) and $dither (bool) parameters. Example with default treeDepth, but dithering:
 
    {% set transformedImage = craft.imager.transformImage(image, { width: 500, effects: { quantize: [32, 0, true] } }) %}

### levels [array]
Adjusts an image's levels, using [Imagick's levelImage method](http://php.net/manual/en/imagick.levelimage.php). The effect takes
an array corresponding to black point, gamma, white point and channel (optional). Example:

    {% set transformedImage = craft.imager.transformImage(img, { width: 500, effects: { levels: [50, 1, 200, 'blue'] }}) %}

You can use negative values for black point and white point to do a level stretch/offset:
 
    {% set transformedImage = craft.imager.transformImage(img, { width: 500, effects: { levels: [-100, 1, 255, 'blue'] }}) %}
    
Possible values for channel is `'red'`, `'green'`, and `'blue'`. Omit it all together to adjust levels for all channels.    

---

CraftTransformedImageModel
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

ImgixTransformedImageModel
---
The model returned by the craft.imager.transformImage method if Imgix is enabled. This model has the same signature as the CraftTransformedImageModel for compabilities sake, but does return empty strings for some of its attributes that are not applicable, or not possible to determine.

### Public attributes
**url**: URL to the image.   
**path**: Returns an empty string.   
**extension**: Returns an empty string.   
**mimeType**: Returns an empty string.   
**width**: Width of the image.   
**height**: Height of the image.    
**size**: Returns an empty string.    

### Public methods
**getUrl() [string]**   
URL to the image.   

**getPath() [string]**   
Returns an empty string. 

**getExtension() [string]**  
Returns an empty string.   

**getMimeType() [string]**  
Returns an empty string.   

**getWidth() [string]**  
Width of the image.   

**getHeight() [string]**  
Height of the image.   

**getSize($unit='b', $precision='2') [float]**  
Returns an empty string.  

**getDataUri() [string]**  
Returns an empty string.   

**getBase64Encoded() [string]**  
Returns an empty string.   

---

Controller actions
---
Imager has two controller actions, one for clearing the transformed images cache, and one for clearing remote images.
You can use this if you wish to clear the cache as part of your deploy process, or similar.

Both actions needs a parameter `key`, which is set with the `clearKey` config setting.
 
Clearing transform images cache:  
    
    http://yourdomain.com/actions/imager/cache/clear-transforms?key=<your_key>

Clearing external images cache:  
    
    http://yourdomain.com/actions/imager/cache/clear-remote-images?key=<your_key>

---

Caching and cache breaking
---
When caching is enabled (`cacheEnabled` configuration setting set to `true`) transformed images are cached for the duration of the `cacheDuration` configuration setting. If an image file is replaced, the existing transforms will be deleted. If a file is moved, the transforms will also be regenerated, since Imager will not find the transforms in the new location.

It is possible to manually remove the generated transforms by going to Settings > Clear Caches, selecting "Imager image transform cache" and clicking "Clear!". You can also select the images you want to clear in the Assets element list, and choose "Clear Imager transforms" from the element action dropdown menu.

When transforming a remote image, the image will be downloaded and cached for the duration of the `cacheDurationRemoteFiles` configuration setting. You can manually remove the cached remote images by going to Settings > Clear Caches, selecting "Imager remote images cache" and clicking "Clear!".

---

Price, license and support
---
The plugin is released under the MIT license, meaning you can do what ever you want with it as long as you don't  blame me. **It's free**, which means there is absolutely no support included, but you might get it anyway. Just post an issue here on github if you have one, and I'll see what I can do. It doesn't hurt to donate a beer at [Beerpay](https://beerpay.io/aelvan/Imager-Craft) either. Just saying. :)

---

Changelog
---
See [CHANGELOG.MD](https://raw.githubusercontent.com/aelvan/Imager-Craft/craft3/CHANGELOG.md).
