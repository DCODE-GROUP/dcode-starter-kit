<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \DcodeStarterKit\DcodeStarterKit\DcodeStarterKit
 */
class DcodeStarterKit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \DcodeStarterKit\DcodeStarterKit\DcodeStarterKit::class;
    }
}
