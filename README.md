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

After dependency installation succeeds, the installer interactively configures `spatie/laravel-permission`. It explains that teams scope roles and role assignments in a shared database, but do not implement application tenancy, then asks whether teams should be enabled. The default is the existing `permission.teams` value when available, otherwise `false`. If enabled, the tenant foreign key defaults to the existing configured value or `team_id`; names such as `tenant_id` and `organisation_id` are accepted.

The installer publishes Spatie's `config/permission.php` and initial permission migration without force-overwriting existing files, updates only the teams settings while preserving other configuration, and clears the configuration cache. It avoids duplicate permission migrations on reruns. If existing permission migrations indicate that changing teams or its foreign key may require a schema upgrade, it preserves the current configuration and reports the manual upgrade path. Non-interactive runs use the existing values or these defaults and do not wait for input.

Review the published configuration and migrations, then run `php artisan migrate`. Add `Spatie\Permission\Traits\HasRoles` to the appropriate authenticatable model. With teams enabled, call `setPermissionsTeamId($tenantId)` from the application's actual tenancy context before permission checks; when changing tenant context, clear the model's loaded `roles` and `permissions` relations. Teams configuration does not provide tenant resolution or data isolation. UUID/ULID identifiers may require migration and model adjustments before migration. The installer does not create tenant models, tenant tables, middleware, roles, permissions, or seeders, and does not claim that the application's permission or tenancy integration is complete.

If permission migrations have already run, review the schema and use Spatie's `php artisan permission:setup-teams` upgrade path when appropriate, then run `php artisan migrate`; the installer does not run it automatically.

The installer also adds the Composer `extra.hooks` configuration used by the DCode project starter to the application's `composer.json`, while preserving unrelated Composer metadata. Composer output is streamed to the terminal. If dependency installation or hook configuration fails, the command stops and returns a failure status; a development dependency failure means the runtime dependencies may already have been installed. Fix the application or Composer configuration and run `php artisan dcode:install` again. Composer safely handles already-installed entries when rerunning the command.

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
