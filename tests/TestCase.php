<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Tests;

use DcodeStarterKit\DcodeStarterKit\DcodeStarterKitServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            DcodeStarterKitServiceProvider::class,
        ];
    }
}
