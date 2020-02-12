Imager for Craft 3.x
=====

**Imager is dead - long live [Imager X](https://plugins.craftcms.com/imager-x)!**

Well, dead is a bit harsh, but... This version of the Craft plugin is no longer 
actively maintained. After four years, 322 commits, 255 closed issues,
352 stars and ~14700 active installs, I've decided to make this plugin commercial
to be able to continue maintaining it. To do so, I had to create a new plugin
(since P&T doesn't allow developers to convert free plugins to commercial ones, 
which makes sense) and the result is [Imager X](https://plugins.craftcms.com/imager-x).

Imager X is a drop-in replacement for Imager, all you need to do is uninstall
Imager, install Imager X, and rename the config file from `imager.php` to
`imager-x.php`. No template changes needed. 

Imager X also comes with some new features, like:

- Support for named transforms, a new way to define your transforms in a central place, and easily reuse it in your templates.
- Support for auto generating transforms on asset upload or element save.
- Console commands and element actions for generating transforms.
- Support for GraphQL.
- Support for adding a fallbackImage that is used when a transform fails.
- Support for adding a mockImage that completely overrides any used image (great for development!).
- Much improved docs built on Vuepress.

**Give it a try!**    

_And if you don't want to pay, feel free to use Imager 2.0 for as long as you wish! [You can still access the old documentation here](https://github.com/aelvan/Imager-Craft/blob/e06b24885dd194a6b0659a66f9333067975b027e/README.md)._
