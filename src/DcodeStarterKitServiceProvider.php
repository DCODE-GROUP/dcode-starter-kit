<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit;

use DcodeStarterKit\DcodeStarterKit\Console\Commands\InstallCommand;
use Illuminate\Support\ServiceProvider;

class DcodeStarterKitServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dcode-starter-kit.php', 'dcode-starter-kit');

        $this->app->singleton(DcodeStarterKit::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dcode-starter-kit');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'dcode-starter-kit');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/dcode-starter-kit.php' => config_path('dcode-starter-kit.php'),
        ], ['dcode-starter-kit', 'dcode-starter-kit-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/dcode-starter-kit'),
        ], ['dcode-starter-kit', 'dcode-starter-kit-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/dcode-starter-kit'),
        ], ['dcode-starter-kit', 'dcode-starter-kit-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/dcode-starter-kit'),
        ], ['dcode-starter-kit', 'dcode-starter-kit-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['dcode-starter-kit', 'dcode-starter-kit-migrations']);

        $this->commands([
            InstallCommand::class,
        ]);
    }
}
