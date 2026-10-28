<div align="center">
    <h1>Dcode Starter Kit</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/dcodegroup/dcode-starter-kit"><img src="https://img.shields.io/packagist/v/dcodegroup/dcode-starter-kit.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/dcodegroup/dcode-starter-kit"><img src="https://img.shields.io/packagist/php-v/dcodegroup/dcode-starter-kit.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/dcodegroup/dcode-starter-kit"><img src="https://badge.laravel.cloud/badge/dcodegroup/dcode-starter-kit?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/dcodegroup/dcode-starter-kit/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/dcodegroup/dcode-starter-kit/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/dcodegroup/dcode-starter-kit"><img src="https://img.shields.io/packagist/dt/dcodegroup/dcode-starter-kit.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Our standard templates and auth structure for new projects

## Installation

You can install the package via Composer:

```bash
composer require dcodegroup/dcode-starter-kit
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="dcode-starter-kit"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="dcode-starter-kit-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="dcode-starter-kit-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="dcode-starter-kit-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="dcode-starter-kit-lang"
```

### Publishing the Public Assets

```bash
php artisan vendor:publish --tag="dcode-starter-kit-assets"
```

## Usage

<!-- Add a basic usage example here. -->

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Dcode Starter Kit! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [DCode Group Pty Ltd](https://github.com/dcodegroup)
- [All Contributors](../../contributors)

## License

Dcode Starter Kit is open-sourced software licensed under the [MIT license](LICENSE.md).
