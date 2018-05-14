# Imager Changelog

## 2.0.1.1 - 2018-05-14
### Fixed
– Also improved check for native transforms using Imager to make sure we're dealing only with images.   

## 2.0.1 - 2018-05-13
### Fixed
- Fixed an issue that could occur if an object was passed as a transform object instead of an array (Thanks, @Rias500!).
– Improved the check for when to create thumbnails to make sure we're dealing only with images (Thanks, @Rias500!).   

## 2.0.0 - 2018-03-30
### Added
- Documentation done, bumbed to 2.0.0. 

## 2.0.0-beta4 - 2018-03-27
### Added
- Added new placeholder template variable that replaces base64Pixel. Placeholders can now be SVG, GIF or SVG silhouettes.
- Added Yii alias support to imagerSystemPath setting, and replaces DOCUMENT_ROOT with Craft @webroot alias (Thanks, @mmikkel!).

## 2.0.0-beta3 - 2018-03-01
### Fixed
- Fixed use of `Asset::getUri()` which was deprecated in RC13.

## 2.0.0-beta2 - 2018-02-26
### Added
- Support for using Imager for native transforms (`useForNativeTransforms` config setting) and control panel thumbs (`useForCpThumbs` config setting). Very beta atm.
- Added support for native focal point in `position`.
- Added support for aliases in `imagerSystemPath` and `imagerUrl`.
- Added config setting `cacheRemoteFiles` to enable and disable caching of remote images (enabled by default).

### Changed
- The `suppressExceptions` config setting now uses `devMode` by default to determine initial value. Still possible to override though.
- Improved error handling. More annotations and code documentation.

### Fixed
- Fixed a bug where `serverSupportsWebp` would throw an error if config was not initialized.

## 2.0.0-beta1 - 2018-02-10
### Added
- Initial Craft 3 beta release
