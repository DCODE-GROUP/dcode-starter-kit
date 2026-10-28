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

An installation helper for adding DCode Group's standard Laravel application dependencies to a new or existing application.

## Installation

You can install the package via Composer:

```bash
composer require --dev dcodegroup/dcode-starter-kit
```

Run the installer from the consuming Laravel application:

```bash
php artisan dcode:install
```

The command adds these regular Composer dependencies:

- `bugsnag/bugsnag-laravel`
- `dcodegroup/activity-log`
- `dcodegroup/laravel-attachments-backend`
- `spatie/cpu-load-health-check`
- `spatie/laravel-health`
- `spatie/laravel-log-dumper`
- `spatie/laravel-login-link`
- `spatie/laravel-model-status`
- `spatie/laravel-permission`
- `spatie/laravel-query-builder`
- `tightenco/ziggy`

It adds this development dependency:

- `dcode-group/composer-git-hooks`

These dependencies are added directly to the consuming application and can be managed independently after installation. Any private repositories and credentials must already be configured in that application and its environment.

This initial installer only adds dependencies. It does not yet publish package configuration or migrations, run migrations, configure Git hooks, or perform other package-specific setup.

Composer output is streamed to the terminal. If either dependency group fails, the command stops and returns a failure status; a development dependency failure means the runtime dependencies may already have been installed. Fix the application or Composer configuration and run `php artisan dcode:install` again. Composer safely handles already-installed entries when rerunning the command.

## Usage

The package currently exposes the `dcode:install` command only.

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
