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

The command currently installs dependencies only. It does not publish configuration or migrations, run migrations, configure Git hooks, or perform other package-specific setup.

## Rules, References, and Templates

Read before executing:

- no additional resource files for this skill

## Examples

- Add the package to a new Laravel application with `composer require --dev dcodegroup/dcode-starter-kit`, then run `php artisan dcode:install`.

## Anti-patterns

- do not run the installer before the consuming application's Composer repositories and credentials are configured
- do not promise package-specific configuration or Git hook setup; those steps are not implemented yet
