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
