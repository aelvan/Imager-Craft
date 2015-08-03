Imager for Craft (beta)
=====
Imager is a plugin for doing image transforms in Craft templates. It does all the things that the built-in image transform functionality do – but more. And it's faster. At least if you tell it to be. 

**Features**:

- A convenient syntax for doing a bunch of image transforms in one go.  
- Transforms are completely file-based, no database queries needed.
- Transformed images are placed in their own folder, outside of the asset source folder.
- Optimize your created images automatically with jpegoptim, optipng or TinyPNG.
- Crop position is relative (in percent) not confined to edges/center (but the built-in keywords still works).  
`{ width: 600, height: 600, mode: 'crop', position: '20% 65%' }`
- New cropZoom parameter for when you want to get a little closer.  
`{ width: 600, height: 600, mode: 'crop', position: '20% 65%', cropZoom: 1.5 }`
- New croponly mode. To crop, not resize.  
`{ width: 600, height: 600, mode: 'croponly', position: '20% 65%' }`
- Basic image effects, including grayscale, negative, blur, sharpen, gamma and colorize.   
`{ effects: { sharpen: true, gamma: 1.4, colorize: '#ff9933' } }`
- Advanced effetcs, including color blend, tint, sepia, contrast, modulate, normalize, contrast stretch, and vignette (Imagick imagedriver only).  
`{ effects: { modulate: [100, 40, 100], colorBlend: ['rgb(255, 153, 51)', 0.5] } }`
- Your own choice of which resize filter to use. Speed vs. quality is up to you (Imagick imagedriver only).  
- Crazy experiments (only one at the moment) to speed up your transforms like... crazy much.

**For a quick look at what Imager can do, [check out the demo site](http://imager.vaersaagod.no/).**

Why beta?
---
1. The plugin hasn't been battle-tested yet (development was started five days ago, at the time of writing). 
2. I have plans to implement more features (removal of generated files from the control panel, external sources, CDN upload support, custom filters, filetypes for letting the client define focal point and effects, etc). 
3. A few things doesn't work as well as it should (jpegoptim and optipng should also be called through tasks, like TinyPNG, and the vignette effect just isn't very nice).

The reason I still publish it now, is to get some early feedback from the community. I'd love to get some feedback on wether or not this is a plugin that people will use, suggestions for new features, and bug-reports. 

Installation
---
1. Download the zip from this repository, unzip, and put the imager folder in your Craft plugin folder.
2. Enable the plugin in Craft (Settings > Plugins)
3. Create a new configuration file in the craft/config folder, named imager.php. Override any settings, the defaults are found in [imager/config.php](https://github.com/aelvan/Imager-Craft/blob/master/imager/config.php). 
4. Make sure you create the folder where Imager will store the images exists, and is writable. (your public folder)/imager is the default.

Configuration
---
Information about the configuration parameters are coming soon. 

For now, see [imager/config.php](https://github.com/aelvan/Imager-Craft/blob/master/imager/config.php). 

Usage
---
If you've ever done an image transform in a Craft template, this probably looks familiar to you:

    {% set image = craft.assets().limit(1).first() %}
    <img src="{{ image.getUrl({ width: 1000 }) }}">

The same code using Imager would look like this:

    {% set image = craft.assets().limit(1).first() %}
    {% set transformedImage = craft.imager.transformImage(image, { width: 1000 })
    <img src="{{ transformedImage.url }}">

So far, it's more code than the Craft way. But, let's say you need to resize the image to six different widths, because you're using <picture> and srcset to serve up responsive images (in a modern and futureproof way). And you want the crop position on all the images to be in the bottom-right corner, and while the large images should have a high jpeg quality, the smaller ones should be more optimized. 

Here's how the code would look with Imager:

	{% set transformedImages = craft.imager.transformImage(image, [
		{ width: 1200 }, 
		{ width: 1000 }, 
		{ width: 800 }, 
		{ width: 600, jpegQuality: 65 }, 
		{ width: 400, jpegQuality: 65 }
		], { position: 'bottom-right', jpegQuality: 80 }) %}

Now you can do something smart with that array of transformed images.

Transform parameters
---
Information about the different transform parameters are coming soon. 

For now, see the above examples, and [the demo site](http://imager.vaersaagod.no/). 

Performance
---
The main motivation behind making the plugin (before I went down the rabbit hole full of image effects, crop options, etc), was to improve the performance when doing image transforms. When using the <picture> element and srcset-attribute, there is usually 50+ image transforms being done in most of our templates. That leads to alot of database queries. Did I succeed? Benchmarks coming soon (but, yes). 

Price, license and support
---
The plugin is released under the MIT license, meaning you can do what ever you want with it as long as you don't blame me. **It's free**, which means there is absolutely no support included, but you might get it anyway. Just post an issue here on github if you have one, and I'll see what I can do. :)
