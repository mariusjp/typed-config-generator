A typed config class generator for Laravel
========================================
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

### Step 2: using the module
By running the command `php artisan coderg33k:generate` the package will generate a typed config class for each config file in your `config` directory.
The command has a set of options to tweak the output.

Run `php artisan coderg33k:generate --help` to see the options.

| Option          | Description                                                                            |
|-----------------|----------------------------------------------------------------------------------------|
| `--all`         | Don't get a prompt and generate a class for all configs                                |
| `--no-strict`   | Don't add strict typing to the generated classes                                       |
| `--no-final`    | Don't make the generated classes final                                                 |
| `--no-readonly` | Don't make the generated properties readonly                                           |
| `--package=`    | A string that represents a package, like laravel or spatie to generate the classes for |
| `--config=*`    | A comma separated list of configs to generate classes for                              |

Testing
-------
This package uses [PHPUnit](https://phpunit.de) for unit and integration tests.

It can be run standalone by `composer phpunit` or within the complete checkup by `composer checkup`

Changelog
---------
See the [project changelog](https://github.com/mjtheone/typed-config-generator/blob/main/CHANGELOG.md)

Contributing
------------
Contributions are always welcome. Please see [CONTRIBUTING.md](https://github.com/mjtheone/typed-config-generator/blob/main/CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](https://github.com/mjtheone/typed-config-generator/blob/main/CODE_OF_CONDUCT.md) for details.

License
-------
The MIT License (MIT). Please see [License File](https://github.com/mjtheone/typed-config-generator/blob/main/LICENSE) for more information.

