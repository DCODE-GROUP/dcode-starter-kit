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

Run the installer from a fresh Laravel application:

```bash
php artisan dcode:install
```

The installer adds a generic Vue 3 + TypeScript + Inertia + Tailwind frontend with a responsive application shell, login/password-reset screens, dashboard, profile, and password pages. It also installs the minimum Inertia integration and keeps the frontend independent of the reference application's private Dcode UI packages.

The generated authentication, dashboard, profile, and password controllers plus `HandleDcodeInertiaRequests` middleware are copied into the host application so they can be customized and versioned with that application. The installer also creates the host Fortify actions/provider, login request, registration view, Fortify configuration, Sanctum token migration, two-factor migration, and adds the Fortify/Sanctum traits to the host `User` model. The installer creates host-owned authentication routes in `routes/auth.php`, requires them from `routes/web.php`, keeps dashboard/account routes in `routes/web.php`, and replaces Laravel's default `/` welcome route with the dashboard action. It does not load application routes from the package.

The installer also creates `app/Providers/MacroServiceProvider.php`, registers it in `bootstrap/providers.php`, and defines the reusable `Blueprint::lastModifiedFields()` macro. The macro adds nullable `created_by` and `updated_by` foreign keys referencing `users.id`. Existing customised providers are never overwritten.

It updates the fresh application's default users migration to add `first_name`, nullable `middle_name`, `last_name`, nullable `username`, `active`, `profile_photo_path`, `lastModifiedFields()`, and soft deletes. The password-reset tokens and sessions table definitions are preserved unchanged. The migration must still be reviewed and run with `php artisan migrate`; existing or ambiguous users migrations are not modified automatically.

The installer publishes the `dcodegroup/activity-log` package migration using its `activity-log-migrations` publish tag, then normalizes it to `0001_01_01_000003_create_activity_logs_tables.php`. Existing activity-log migrations are preserved and are not duplicated on reruns.

The supported starting point is a Laravel 12 or 13 application with a `package.json` and pnpm available. The installer configures `packageManager` and the pnpm engine, allows the required DSG Vue build script through `pnpm-workspace.yaml`'s `allowBuilds` setting, adds frontend dependencies including the private `@dcodegroup-au/dsg-vue` component library, and configures both `pnpm run dev` and `pnpm run build` to use the generated Vite config. It then runs `pnpm install` followed by `pnpm run build`. An existing yarn or npm `packageManager` declaration is reported as a conflict rather than silently changed. Configure the DCode npm registry and credentials in the host project before installing; credentials are never copied by this package.

The frontend files are namespaced with `dcode-starter-kit` and are not silently overwritten. If those files already exist, review or remove them before rerunning. Existing unrelated frontend files and Composer metadata are preserved. The command is safe to rerun after a successful install; the dependency managers handle already-installed packages.

The command adds these regular Composer dependencies:

- `bugsnag/bugsnag-laravel`
- `dcodegroup/activity-log`
- `dcodegroup/laravel-attachments-backend`
- `laravel/fortify`
- `laravel/sanctum`
- `spatie/cpu-load-health-check`
- `spatie/laravel-health`
- `spatie/laravel-log-dumper`
- `spatie/laravel-login-link`
- `spatie/laravel-model-status`
- `spatie/laravel-permission`
- `spatie/laravel-query-builder`
- `tightenco/ziggy`
- `inertiajs/inertia-laravel`

It adds this development dependency:

- `dcode-group/composer-git-hooks`

These dependencies are added directly to the consuming application and can be managed independently after installation. Fortify and Sanctum are installed and configured with the reusable starter foundation: registration, password reset, profile/password updates with profile-photo uploads, two-factor support, passkey configuration, login throttling, email-verification route wiring, and personal access tokens. The generated host-owned browser routes use standard Fortify route names (`login`, `logout`, `password.*`, `admin.dashboard`, `verification.*`, and account settings names), so they can be used by Fortify-compatible navigation and controllers without the former `dcode.*` prefix. Add product-specific API routes and Sanctum abilities to the host application as needed. Any private repositories and credentials must already be configured in that application and its environment.

After dependency installation succeeds, the installer interactively configures `spatie/laravel-permission`. It explains that teams scope roles and role assignments in a shared database, but do not implement application tenancy, then asks whether teams should be enabled. The default is the existing `permission.teams` value when available, otherwise `false`. If enabled, the tenant foreign key defaults to the existing configured value or `team_id`; names such as `tenant_id` and `organisation_id` are accepted.

The installer publishes Spatie's `config/permission.php` and initial permission migration without force-overwriting existing files, updates only the teams settings while preserving other configuration, and clears the configuration cache. It avoids duplicate permission migrations on reruns. If existing permission migrations indicate that changing teams or its foreign key may require a schema upgrade, it preserves the current configuration and reports the manual upgrade path. Non-interactive runs use the existing values or these defaults and do not wait for input.

Review the published configuration and migrations, then run `php artisan migrate`. Add `Spatie\Permission\Traits\HasRoles` to the appropriate authenticatable model. With teams enabled, call `setPermissionsTeamId($tenantId)` from the application's actual tenancy context before permission checks; when changing tenant context, clear the model's loaded `roles` and `permissions` relations. Teams configuration does not provide tenant resolution or data isolation. UUID/ULID identifiers may require migration and model adjustments before migration. The installer does not create tenant models, tenant tables, middleware, roles, permissions, or seeders, and does not claim that the application's permission or tenancy integration is complete.

If permission migrations have already run, review the schema and use Spatie's `php artisan permission:setup-teams` upgrade path when appropriate, then run `php artisan migrate`; the installer does not run it automatically.

The installer also adds the Composer `extra.hooks` configuration used by the DCode project starter to the application's `composer.json`, while preserving unrelated Composer metadata. Composer output is streamed to the terminal. If dependency installation or hook configuration fails, the command stops and returns a failure status; a development dependency failure means the runtime dependencies may already have been installed. Fix the application or Composer configuration and run `php artisan dcode:install` again. Composer safely handles already-installed entries when rerunning the command.

After installation, review the generated `vite.config.dcode.ts`, run `pnpm run dcode:typecheck`, and run the application's migrations before testing authentication. The installer expects the fresh application's `User` model and `bootstrap/providers.php`; customised Fortify configuration, provider, migrations, or user models are never silently overwritten. The package does not create users, seed roles, configure product-specific API routes, or assume application tenancy. The bundled browser routes use their own Inertia middleware and standard Fortify-compatible route names.

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
