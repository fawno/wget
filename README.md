[![GitHub license](https://img.shields.io/github/license/fawno/wget)](LICENSE)
[![GitHub release](https://img.shields.io/github/release/fawno/wget)](https://github.com/fawno/wget/releases)
[![Packagist](https://img.shields.io/packagist/v/fawno/wget)](https://packagist.org/packages/fawno/wget)
[![Packagist Downloads](https://img.shields.io/packagist/dt/fawno/wget)](https://packagist.org/packages/fawno/wget/stats)
[![GitHub issues](https://img.shields.io/github/issues/fawno/wget)](https://github.com/fawno/wget/issues)
[![GitHub forks](https://img.shields.io/github/forks/fawno/wget)](https://github.com/fawno/wget/network)
[![GitHub stars](https://img.shields.io/github/stars/fawno/wget)](https://github.com/fawno/wget/stargazers)

# wget
PHP Class wget

# Requirements

wget requires PHP version 5.6 or higher with openssl and curl extensions enabled.

## How to Install

### Install with [`composer.phar`](http://getcomposer.org).

Add `fawno/wget` as a requirement to your project:

```sh
php composer.phar require "fawno/wget"
```

Load the class in your script:

```php
<?php
  require 'vendor/autoload.php';

  use Fawno\wget\wget;
```

### Manual installation

Download [wget.php](https://github.com/fawno/wget/raw/master/src/wget.php) and save it in an accessible route.

Load `wget.php` in your script:

```php
<?php
  require 'wget.php';

  use Fawno\wget\wget;
```
