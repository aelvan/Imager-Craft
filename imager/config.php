<?php

/**
 * Configuration file for Imager
 *
 * Override this by placing a file named 'imager.php' inside your config folder and override variables as needed.
 * Multi-environment settings work in this file the same way as in general.php or db.php
 */
return array(
  'imagerSystemPath' => $_SERVER['DOCUMENT_ROOT'] . '/imager/',
  'imagerUrl' => '/imager/',
  'jpegQuality' => 80,
  'pngCompressionLevel' => 2,
  'allowUpscale' => true,
  'resizeFilter' => 'lanczos',
  'position' => '50% 50%',
  'hashFilename' => 'postfix', // true, false, or 'postfix' (meaning only the generated part of the filename)

  'cacheEnabled' => true,
  'cacheDuration' => 1209600, // 14 days
  'instanceReuseEnabled' => false,
  'jpegoptimEnabled' => false,
  'jpegoptimPath' => '/usr/bin/jpegoptim',
  'jpegoptimOptionString' => '-s',
  'jpegtranEnabled' => false,
  'jpegtranPath' => '/usr/bin/jpegtran',
  'jpegtranOptionString' => '-optimize -copy none',
  'optipngEnabled' => false,
  'optipngPath' => '/usr/bin/optipng',
  'optipngOptionString' => '-o5',
  'tinyPngEnabled' => false,
  'tinyPngApiKey' => '',
  'optimizeType' => '',
  'logOptimizations' => false,
);
