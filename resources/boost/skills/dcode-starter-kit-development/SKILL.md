---
name: dcode-starter-kit-development
description: >
  Configure and apply the Dcode Starter Kit package in Laravel applications.
license: MIT
metadata:
  author: DCode Group Pty Ltd
---

# Dcode Starter Kit

Use this skill when a Laravel application needs to integrate the Dcode Starter Kit package.

## Primary Goal

- apply the `dcodegroup/dcode-starter-kit` package's public API in the smallest correct way

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project
- inspect the target code paths where the package should be applied

### 2. Install the package and its application dependencies

- install the package as a development dependency:

  ```bash
  composer require --dev dcodegroup/dcode-starter-kit
  ```

- run `php artisan dcode:install` from the consuming application's root
- for non-interactive setup, pass `--tenancy=true` or `--tenancy=false` to select the permission teams setting without prompting
- ensure private Composer repositories and credentials are configured before running the command
- review the application's `composer.json` after installation; all listed runtime and development dependencies belong to the application and can be managed independently
- rerun `php artisan dcode:install` after correcting a failed Composer step

The command installs Composer dependencies including Laravel Fortify and Sanctum, publishes and normalizes the `dcodegroup/activity-log` migration to `0001_01_01_000003_create_activity_logs_tables.php`, publishes the activity-log config, integrates the host `User` model with `HasActivityUser` and `ActivityLoggable`, configures permission teams, configures Composer hooks, creates and registers the host application's `App\Providers\MacroServiceProvider` with the `Blueprint::lastModifiedFields()` macro, updates the fresh application's default users migration, and installs the generic Vue/Inertia frontend foundation. It also creates and registers the host Fortify provider/actions, login request, Fortify configuration, Sanctum personal-token migration, Fortify two-factor migration, profile-photo handling, and email-verification routes, then adds `HasApiTokens` and `TwoFactorAuthenticatable` to the host `User` model. The users migration update leaves password-reset tokens and sessions definitions untouched. The generated browser routes remain host-owned and use standard Fortify-compatible names (`login`, `logout`, `password.*`, `admin.dashboard`, `verification.*`, and account settings names); a rerun upgrades the package's older `dcode.*` route names. The frontend expects pnpm and a `package.json`, configures the pnpm metadata, refuses to overwrite its own generated files, and runs `pnpm install` followed by `pnpm run build`. As the final installation step it runs `php artisan migrate --force` and `php artisan db:seed --force`. It does not create product-specific API routes or configure application tenancy.

The frontend controllers and Inertia middleware are generated in the consuming application's `app/` directories. The installer creates host-owned authentication routes in `routes/auth.php`, requires them from `routes/web.php`, and injects the dashboard/account routes into `routes/web.php`, including the dashboard-backed `/` route; the package does not load them as package routes.

The installer also configures the reusable Laravel Attachments backend. It publishes `config/attachments.php` and `config/media-library.php`, configures the attachment media model and path generator, publishes the three attachment migrations, registers authenticated attachment routes, creates a host attachment service provider and generic media policy, and adds `HasMediaUser` to `App\Models\User`. The attachment backend requires PHP `imagick`. Review the generated `app/Policies/MediaPolicy.php` and storage settings before production use; the package does not provide product-specific attachment UI, storage credentials, or tenancy behavior.

The installer also installs `dcodegroup/laravel-dsg-table` and creates permission-based users and roles management in the host application. The generated index screens use `DsgTable` against authenticated host-owned table endpoints for server-side pagination, sorting, searching, and row actions. It replaces the fresh Laravel `DatabaseSeeder` with logic that creates the `admin-portal-users-*` and `admin-portal-roles-permissions-*` permissions, an `Admin` role with all of them, and the verified bootstrap user `forge@dcodegroup.com` with password `Dcode123!!`. Customized `DatabaseSeeder` files are not overwritten. The installer runs the seeder automatically as its final step; change or remove that bootstrap credential outside local development. The generated screens are intentionally generic and do not include Embedded Health user types, facilities, pharmacist data, or tenancy behavior.

## Rules, References, and Templates

Read before executing:

- no additional resource files for this skill

## Examples

- Add the package to a new Laravel application with `composer require --dev dcodegroup/dcode-starter-kit`, then run `php artisan dcode:install`.

## Anti-patterns

- do not run the installer before the consuming application's Composer repositories and credentials are configured
- do not promise package-specific configuration or Git hook setup; those steps are not implemented yet
- do not assume the frontend can replace an existing authentication or frontend scaffold; resolve reported conflicts first
- do not assume the generated attachment policy matches the application's authorization model; review it before production use
- do not retain the seeded bootstrap password outside local development
- do not copy private Dcode registry credentials into the application
