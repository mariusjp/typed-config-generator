A Laravel typed config generator
========================================
[![Latest Version](http://img.shields.io/packagist/v/MJTheOne/typed-config-generator.svg?style=flat-square)](https://github.com/MJTheOne/typed-config-generator/releases)
![Build](https://github.com/MJTheOne/typed-config-generator/actions/workflows/run-tests.yml/badge.svg?event=push)
[![codecov](https://codecov.io/gh/MJTheOne/typed-config-generator/branch/main/graph/badge.svg?token=BRH4XEU1VK)](https://codecov.io/gh/MJTheOne/typed-config-generator)

Are you a PHPStan lovin' strict programmer?! Say no more! This package will generate typed config classes for you based on your config files.

We all struggle with the `mixed` return type of the `config()` helper function. This package will stop your struggle and leave all your (unnecessary?) type checks behind you!

Installation
============
Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 1: Download the module
Open a command console, enter your project directory and execute:

```console
$ composer require coderg33k/typed-config-generator
```

### Step 2: Enable the module
*@todo: Autodiscover the ServiceProvider*

Then, enable the library by adding the service provider to the list of registered providers
in the `config/app.php` file of your project:

```php
// config/app.php

'providers' => [
    // ...
    Coderg33k\TypedConfigServiceProvider::class,
    // ...
];
```

Testing
-------
This package uses [PHPUnit](https://phpunit.de) for unit and integration tests.

It can be run standalone by `composer phpunit` or within the complete checkup by `composer checkup`

### Checkup
The above-mentioned checkup runs multiple analyses of the package's code. This includes [Squizlab's Codesniffer](https://github.com/squizlabs/PHP_CodeSniffer), [PHPStan](https://phpstan.org) and a [coverage check](https://github.com/richardregeer/phpunit-coverage-check).

Continuous Integration
----------------------
[GitHub actions](https://github.com/features/actions) are used for continuous integration. Check out the [configuration file](https://github.com/mjtheone/typed-config-generator/blob/main/.github/workflows/ci.yml) if you'd like to know more.

Changelog
---------
See the [project changelog](https://github.com/mjtheone/typed-config-generator/blob/main/CHANGELOG.md)

Contributing
------------
Contributions are always welcome. Please see [CONTRIBUTING.md](https://github.com/mjtheone/typed-config-generator/blob/main/CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](https://github.com/mjtheone/typed-config-generator/blob/main/CODE_OF_CONDUCT.md) for details.

License
-------
The MIT License (MIT). Please see [License File](https://github.com/mjtheone/typed-config-generator/blob/main/LICENSE) for more information.

Credits
-------
This code is principally developed and maintained by [Marius Posthumus](https://github.com/MJTheOne)
