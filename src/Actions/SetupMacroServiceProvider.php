<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use RuntimeException;

class SetupMacroServiceProvider
{
    public const string STEP = 'macro service provider setup';

    private const string PROVIDER = 'App\\Providers\\MacroServiceProvider';

    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->writeProvider($workingDirectory);
            $this->registerProvider($workingDirectory);

            $output("MacroServiceProvider created and registered.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function writeProvider(string $workingDirectory): void
    {
        $directory = $workingDirectory.'/app/Providers';
        $path = $directory.'/MacroServiceProvider.php';
        $contents = <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blueprint::macro('lastModifiedFields', function (): void {
            /** @var Blueprint $blueprint */
            $blueprint = $this;

            $blueprint->foreignId('created_by')->nullable();
            $blueprint->foreignId('updated_by')->nullable();
            $blueprint->foreign('created_by')->references('id')->on('users');
            $blueprint->foreign('updated_by')->references('id')->on('users');
        });
    }
}
PHP;

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create app/Providers for MacroServiceProvider.');
        }

        if (! is_file($path)) {
            file_put_contents($path, $contents."\n");

            return;
        }

        $existing = file_get_contents($path);

        if ($existing !== $contents."\n") {
            throw new RuntimeException('app/Providers/MacroServiceProvider.php already exists and was not created by Dcode Starter Kit. Review it before rerunning dcode:install.');
        }
    }

    private function registerProvider(string $workingDirectory): void
    {
        $path = $workingDirectory.'/bootstrap/providers.php';

        if (! is_file($path)) {
            throw new RuntimeException('bootstrap/providers.php was not found; MacroServiceProvider could not be registered safely.');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read bootstrap/providers.php.');
        }

        if (str_contains($contents, self::PROVIDER)) {
            return;
        }

        $updated = preg_replace(
            '/(return\s*\[)(.*?)(\];)/s',
            "$1$2\n    ".self::PROVIDER.'::class,$3',
            $contents,
            1,
        );

        if (! is_string($updated) || $updated === $contents) {
            throw new RuntimeException('Unable to register MacroServiceProvider safely in bootstrap/providers.php.');
        }

        file_put_contents($path, $updated);
    }
}
