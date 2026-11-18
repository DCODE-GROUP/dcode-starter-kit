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
- ensure private Composer repositories and credentials are configured before running the command
- review the application's `composer.json` after installation; all listed runtime and development dependencies belong to the application and can be managed independently
- rerun `php artisan dcode:install` after correcting a failed Composer step

The command installs Composer dependencies, publishes and normalizes the `dcodegroup/activity-log` migration to `0001_01_01_000003_create_activity_logs_tables.php`, configures permission teams, configures Composer hooks, creates and registers the host application's `App\Providers\MacroServiceProvider` with the `Blueprint::lastModifiedFields()` macro, updates the fresh application's default users migration, and installs the generic Vue/Inertia frontend foundation. The users migration update leaves password-reset tokens and sessions definitions untouched. The frontend expects pnpm and a `package.json`, configures the pnpm metadata, refuses to overwrite its own generated files, and runs `pnpm install` followed by `pnpm run build`. It does not run database migrations or configure application tenancy.

The frontend controller and Inertia middleware are generated in the consuming application's `app/` directories. The installer injects the frontend routes into the host `routes/web.php`, including the dashboard-backed `/` route; the package does not load them as package routes.

## Rules, References, and Templates

Read before executing:

- no additional resource files for this skill

## Examples

- Add the package to a new Laravel application with `composer require --dev dcodegroup/dcode-starter-kit`, then run `php artisan dcode:install`.

## Anti-patterns

- do not run the installer before the consuming application's Composer repositories and credentials are configured
- do not promise package-specific configuration or Git hook setup; those steps are not implemented yet
- do not assume the frontend can replace an existing authentication or frontend scaffold; resolve reported conflicts first
- do not copy private Dcode registry credentials into the application
