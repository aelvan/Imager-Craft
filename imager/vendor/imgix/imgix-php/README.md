![imgix logo](https://assets.imgix.net/imgix-logo-web-2014.pdf?page=2&fm=png&w=200&h=200)

[![Build Status](https://travis-ci.org/imgix/imgix-php.png?branch=master)](https://travis-ci.org/imgix/imgix-php)

A PHP client library for generating URLs with imgix. imgix is a high-performance
distributed image processing service. More information can be found at
[http://www.imgix.com](http://www.imgix.com).


## Dependencies

The tests have a few external dependencies. To install those:

```bash
phpunit --bootstrap src/autoload.php tests/tests.php
```

## Installation

### Standalone

Just copy the files to your project, and include the `src/autoload.php` file. We recommend using Composer if at all possible.

### Using Composer

Define the following requirement in your `composer.json` file:

```json
{
  "require": {
    "imgix/imgix-php": "dev-master"
  }
}
```

And include the global `vendor/autoload.php` autoloader.

## Basic Usage

To begin creating imgix URLs programmatically, simply add the php files to your project (an example autoloader is also provided). The URL builder can be reused to create URLs for any
images on the domains it is provided.

```php
use Imgix\UrlBuilder;

$builder = new UrlBuilder("demos.imgix.net");
$params = array("w" => 100, "h" => 100);
echo $builder->createURL("bridge.png", $params);

// Prints out:
// http://demos.imgix.net/bridge.png?h=100&w=100
```

For HTTPS support, simply use the setter `setUseHttps` on the builder

```php
use Imgix\UrlBuilder;

$builder = new UrlBuilder("demos.imgix.net");
$builder->setUseHttps(true);
$params = array("w" => 100, "h" => 100);
echo $builder->createURL("bridge.png", $params);

// Prints out
// https://demos.imgix.net/bridge.png?h=100&w=100
```

## Signed URLs

To produce a signed URL, you must enable secure URLs on your source and then
provide your signature key to the URL builder.

```php
use Imgix\UrlBuilder;

$builder = new UrlBuilder("demos.imgix.net");
$builder->setSignKey("test1234");
$params = array("w" => 100, "h" => 100);
echo $builder->createURL("bridge.png", $params);

// Prints out:
// http://demos.imgix.net/bridge.png?h=100&w=100&s=bb8f3a2ab832e35997456823272103a4
```

## Domain Sharded URLs

Domain sharding enables you to spread image requests across multiple domains.
This allows you to bypass the requests-per-host limits of browsers. We
recommend 2-3 domain shards maximum if you are going to use domain sharding.

In order to use domain sharding, you need to add multiple domains to your
source. You then provide an array of these domains to a builder.

```php
use Imgix\UrlBuilder;

$domains = array("demos-1.imgix.net", "demos-2.imgix.net", "demos-3.imgix.net");
$builder = new URLBuilder($domains);
$params = array("w" => 100, "h" => 100);
echo $builder->createURL("bridge.png", $params);
echo $builder->createURL("flower.png", $params);

// Prints out:
// http://demos-1.imgix.net/bridge.png?h=100&w=100
// http://demos-2.imgix.net/flower.png?h=100&w=100
```


By default, shards are calculated using a checksum so that the image path
always resolves to the same domain. This improves caching in the browser.
However, you can supply a different strategy that cycles through domains
instead. For example:

```php
use Imgix\UrlBuilder;
use Imgix\ShardStrategy;

$domains = array("demos-1.imgix.net", "demos-2.imgix.net", "demos-3.imgix.net");
$builder = new URLBuilder($domains);
$builder->setShardStrategy(ShardStrategy::CYCLE);
$params = array("w" => 100, "h" => 100);
echo $builder->createURL("bridge.png", $params);
echo $builder->createURL("bridge.png", $params);
echo $builder->createURL("bridge.png", $params);
echo $builder->createURL("bridge.png", $params);

// Prints out:
// http://demos-1.imgix.net/bridge.png?h=100&w=100
// http://demos-2.imgix.net/bridge.png?h=100&w=100
// http://demos-3.imgix.net/bridge.png?h=100&w=100
// http://demos-1.imgix.net/bridge.png?h=100&w=100
```
