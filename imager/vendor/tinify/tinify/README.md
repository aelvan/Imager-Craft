[<img src="https://travis-ci.org/tinify/tinify-php.svg?branch=master" alt="Build Status">](https://travis-ci.org/tinify/tinify-php)

# Tinify API client for PHP

PHP client for the Tinify API. Tinify compresses your images intelligently. Read more at https://tinify.com.

## Documentation

[Go to the documentation for the PHP client](https://tinypng.com/developers/reference/php).

## Installation

Install the API client with Composer. Add this to your `composer.json`:

```json
{
  "require": {
    "tinify/tinify": "*"
  }
}
```

Then install with:

```
composer install
```

Use autoloading to make the client available in PHP:

```php
require_once("vendor/autoload.php");
```

## Usage

```php
Tinify\setKey("YOUR_API_KEY");
Tinify\fromFile("unoptimized.png")->toFile("optimized.png");
```

## Running tests

```
composer install
vendor/bin/phpunit
```

## License

This software is licensed under the MIT License. [View the license](LICENSE).
