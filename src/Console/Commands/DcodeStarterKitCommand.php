<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Console\Commands;

use Illuminate\Console\Command;

class DcodeStarterKitCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'dcode-starter-kit:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package dcode-starter-kit.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('DcodeStarterKit placeholder command executed.');

        return self::SUCCESS;
    }
}
