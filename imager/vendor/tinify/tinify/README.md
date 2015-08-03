[<img src="https://travis-ci.org/tinify/tinify-php.svg?branch=master" alt="Build Status">](https://travis-ci.org/tinify/tinify-php)

# Tinify API client for PHP

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

## License

This software is licensed under the MIT License. [View the license](LICENSE).
