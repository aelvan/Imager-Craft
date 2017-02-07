<?php

/**
 * Imager by André Elvan
 *
 * @author      André Elvan <http://vaersaagod.no>
 * @package     Imager
 * @copyright   Copyright (c) 2016, André Elvan
 * @license     http://opensource.org/licenses/mit-license.php MIT License
 * @link        https://github.com/aelvan/Imager-Craft
 */


/**
 * Configuration file for Imager
 *
 * Override this by placing a file named 'imager.php' inside your config folder and override variables as needed.
 * Multi-environment settings work in this file the same way as in general.php or db.php
 */

return array(
  'imagerSystemPath' => $_SERVER['DOCUMENT_ROOT'] . '/imager/',
  'imagerUrl' => '/imager/',
  'cacheEnabled' => true,
  'cacheDuration' => 1209600, // 14 days
  'cacheDurationRemoteFiles' => 1209600, // 14 days
  'jpegQuality' => 80,
  'pngCompressionLevel' => 2,
  'webpQuality' => 80,
  'webpImagickOptions' => array(), // additional options you want to pass to Imagick via '$instance->setOption('webp:option', 'value')'.
  'useCwebp' => false,
  'cwebpPath' => '/usr/bin/cwebp',
  'cwebpOptions' => '', // additional options you want to pass to cwebp. Quality is set automatically.
  'interlace' => false, // false, true ('line'), 'none', 'line', 'plane', 'partition'
  'allowUpscale' => true,
  'resizeFilter' => 'lanczos',
  'smartResizeEnabled' => false,
  'removeMetadata' => false,
  'bgColor' => '',
  'position' => '50% 50%',
  'letterbox' => array('color'=>'#000', 'opacity'=>0),
  'hashFilename' => 'postfix', // true, false, or 'postfix' (meaning only the generated part of the filename is hashed)
  'hashPath' => false, 
  'hashRemoteUrl' => false, // true, false, or 'host' (meaning only the host part of the url is hashed) 
  'instanceReuseEnabled' => false,
  'noop' => false,
  'suppressExceptions' => false,
  
  'jpegoptimEnabled' => false,
  'jpegoptimPath' => '/usr/bin/jpegoptim',
  'jpegoptimOptionString' => '-s',
  'jpegtranEnabled' => false,
  'jpegtranPath' => '/usr/bin/jpegtran',
  'jpegtranOptionString' => '-optimize -copy none',
  'mozjpegEnabled' => false,
  'mozjpegPath' => '/usr/bin/mozjpeg',
  'mozjpegOptionString' => '-optimize -copy none',
  'optipngEnabled' => false,
  'optipngPath' => '/usr/bin/optipng',
  'optipngOptionString' => '-o5',
  'pngquantEnabled' => false,
  'pngquantPath' => '/usr/bin/pngquant',
  'pngquantOptionString' => '--strip --skip-if-larger',
  'tinyPngEnabled' => false,
  'tinyPngApiKey' => '',
  'optimizeType' => 'task',
  'logOptimizations' => false,
  
  'awsEnabled' => false,
  'awsAccessKey' => '',
  'awsSecretAccessKey' => '',
  'awsBucket' => '',
  'awsFolder' => '',
  'awsCacheDuration' => 1209600, // 14 days
  'awsRequestHeaders' => array(),
  'awsStorageType' => 'standard', // 'standard' or 'rrs' (reduced redundancy storage),

  'gcsEnabled' => false,
  'gcsAccessKey' => '',
  'gcsSecretAccessKey' => '',
  'gcsBucket' => '',
  'gcsFolder' => '',
  'gcsCacheDuration' => 1209600, // 14 days

  'cloudfrontInvalidateEnabled' => false,
  'cloudfrontDistributionId' => '',
    
  'curlOptions' => array(),
  'runTasksImmediatelyOnAjaxRequests' => true,
  'clearKey' => '', // Key that should be passed to the clear controller action. Empty string means clearing is disabled.
);
