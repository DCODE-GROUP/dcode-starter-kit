<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use Closure;
use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;

class InstallDcodeStarterKitFrontend
{
    public const string STEP = 'frontend setup';

    private const string PNPM_VERSION = 'pnpm@12.3.4';

    private const string ROUTE_MARKER = '// Dcode Starter Kit frontend routes';

    private const array FRONTEND_DEPENDENCIES = [
        '@inertiajs/vue3' => '^3.0',
        '@dcodegroup-au/dsg-vue' => '^1.0.15-next',
        '@vitejs/plugin-vue' => '^6.0',
        'laravel-vite-plugin' => '^3.0',
        'tailwindcss' => '^4.0',
        '@tailwindcss/vite' => '^4.0',
        'typescript' => '^5.0',
        'vue' => '^3.5',
        'vue-tsc' => '^3.0',
        'vite' => '^7.0',
        'vite-svg-loader' => '^5.1.1',
        'ziggy-js' => '^2.0',
    ];

    public function __construct(?Closure $processRunner = null)
    {
        $this->processRunner = $processRunner ?? $this->runProcess(...);
    }

    /**
     * @param  callable(string): void  $output
     */
    public function install(string $workingDirectory, callable $output): bool
    {
        try {
            if (! is_file($workingDirectory.'/package.json')) {
                throw new RuntimeException('No package.json was found. Create a frontend package manifest, then rerun dcode:install.');
            }
            $this->assertNoConflict($workingDirectory);
            $this->copyFrontend($workingDirectory);
            $this->registerHostRoutes($workingDirectory);
            $this->updatePackageJson($workingDirectory);
            $this->updatePnpmWorkspace($workingDirectory);

            if (! ($this->processRunner)(['pnpm', 'install'], $workingDirectory, $output)) {
                $output("Unable to install frontend dependencies with pnpm.\n");

                return false;
            }

            if (! ($this->processRunner)(['pnpm', 'run', 'build'], $workingDirectory, $output)) {
                $output("Unable to build the frontend with pnpm.\n");

                return false;
            }

            $output("Frontend foundation installed with pnpm.\n");

            return true;
        } catch (RuntimeException|JsonException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function assertNoConflict(string $workingDirectory): void
    {
        $conflicts = [];
        foreach ($this->frontendSourceFiles() as $relative => $source) {
            $target = $workingDirectory.'/'.$relative;

            if (is_file($target) && ! $this->filesMatch($source, $target) && ! $this->isLegacyGeneratedFile($relative, $target)) {
                $conflicts[] = $relative;
            }
        }

        foreach (['routes/web.php', 'routes/auth.php'] as $routeFile) {
            $contents = is_file($workingDirectory.'/'.$routeFile) ? file_get_contents($workingDirectory.'/'.$routeFile) : '';

            if (is_string($contents) && ! str_contains($contents, self::ROUTE_MARKER) && preg_match("/['\"]login['\"]|auth\.php|Fortify::routes/s", $contents) === 1) {
                $conflicts[] = $routeFile;
            }
        }

        $authDirectory = $workingDirectory.'/resources/js/pages/Auth';

        if (is_dir($authDirectory)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($authDirectory, FilesystemIterator::SKIP_DOTS));
            $knownFiles = $this->frontendSourceFiles();

            foreach ($files as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                    continue;
                }

                $relative = ltrim(str_replace($workingDirectory.'/', '', $file->getPathname()), DIRECTORY_SEPARATOR);

                if (! isset($knownFiles[$relative])) {
                    $conflicts[] = $relative;
                }
            }
        }

        if ($conflicts !== []) {
            throw new RuntimeException('Frontend files already exist: '.implode(', ', $conflicts).'. Review them and remove or rename them before rerunning dcode:install.');
        }
    }

    private function copyFrontend(string $workingDirectory): void
    {
        foreach ($this->frontendSourceFiles() as $relative => $source) {
            $target = $workingDirectory.'/'.$relative;

            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }

            if (is_file($target) && ! $this->filesMatch($source, $target) && ! $this->isLegacyGeneratedFile($relative, $target)) {
                throw new RuntimeException("Refusing to overwrite {$relative}.");
            }

            if (! is_file($target) || $this->isLegacyGeneratedFile($relative, $target)) {
                copy($source, $target);
            }
        }
    }

    /** @return array<string, string> */
    private function frontendSourceFiles(): array
    {
        $sourceDirectory = dirname(__DIR__, 2).'/resources/frontend';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS));
        $sourceFiles = [];

        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $sourceFiles[ltrim(str_replace($sourceDirectory, '', $file->getPathname()), DIRECTORY_SEPARATOR)] = $file->getPathname();
        }

        return $sourceFiles;
    }

    private function filesMatch(string $source, string $target): bool
    {
        return hash_file('sha256', $source) === hash_file('sha256', $target);
    }

    private function isLegacyGeneratedFile(string $relative, string $target): bool
    {
        $contents = file_get_contents($target);

        if (! is_string($contents)) {
            return false;
        }

        return match ($relative) {
            'vite.config.dcode.ts' => str_contains($contents, 'laravel-vite-plugin')
                && str_contains($contents, 'dcode-starter-kit.css'),
            'tsconfig.dcode.json' => str_contains($contents, 'resources/**/*.vue')
                && str_contains($contents, 'vite/client'),
            'resources/css/dcode-starter-kit.css' => str_contains($contents, '@import "tailwindcss";')
                && str_contains($contents, 'dsg-vue'),
            'resources/js/pages/Auth/ResetPassword.vue' => str_contains($contents, '@inertiajs/vue3')
                && str_contains($contents, 'AuthLayout'),
            'resources/js/pages/Auth/Login.vue',
            'resources/js/pages/Auth/ForgotPassword.vue' => str_contains($contents, '@inertiajs/vue3')
                && str_contains($contents, 'AuthLayout')
                && str_contains($contents, 'dcode.'),
            'resources/js/pages/Account/Profile.vue',
            'resources/js/pages/Account/Password.vue',
            'resources/js/pages/Dashboard.vue' => str_contains($contents, '@inertiajs/vue3')
                && str_contains($contents, 'AppLayout')
                && str_contains($contents, 'dcode.'),
            'resources/js/components/Navigation/AppSidebar.vue' => str_contains($contents, 'sidebar__nav')
                && str_contains($contents, 'dcode.'),
            'resources/js/layouts/App.vue' => str_contains($contents, 'AppSidebar')
                || str_contains($contents, 'dcode.dashboard'),
            'resources/js/layouts/Auth.vue' => str_contains($contents, 'auth__container')
                && str_contains($contents, '@inertiajs/vue3'),
            'resources/js/dcode-starter-kit.ts' => str_contains($contents, 'createInertiaApp')
                && str_contains($contents, 'dcode-starter-kit.css'),
            'app/Http/Controllers/DashboardController.php',
            'app/Http/Controllers/Auth/AuthenticatedSessionController.php',
            'app/Http/Controllers/Auth/NewPasswordController.php' => str_contains($contents, 'namespace App\\Http\\Controllers')
                && str_contains($contents, 'dcode.'),
            default => false,
        };
    }

    private function updatePackageJson(string $workingDirectory): void
    {
        $path = $workingDirectory.'/package.json';

        if (! is_file($path)) {
            throw new RuntimeException('No package.json was found. Create a frontend package manifest, then rerun dcode:install.');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read package.json.');
        }

        $package = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($package)) {
            throw new RuntimeException('package.json must contain a JSON object.');
        }

        $packageManager = $package['packageManager'] ?? null;

        if (is_string($packageManager) && ! str_starts_with($packageManager, 'pnpm@')) {
            throw new RuntimeException('This installer requires pnpm. Remove the conflicting packageManager declaration from package.json, then rerun dcode:install.');
        }

        $package['packageManager'] ??= self::PNPM_VERSION;
        $package['engines'] = is_array($package['engines'] ?? null) ? $package['engines'] : [];
        $package['engines']['pnpm'] ??= '>=9.0.0';

        if (is_array($package['pnpm'] ?? null) && array_key_exists('onlyBuiltDependencies', $package['pnpm'])) {
            unset($package['pnpm']['onlyBuiltDependencies']);
        }

        if (($package['pnpm'] ?? []) === []) {
            unset($package['pnpm']);
        }

        $package['devDependencies'] = is_array($package['devDependencies'] ?? null) ? $package['devDependencies'] : [];
        foreach (self::FRONTEND_DEPENDENCIES as $name => $version) {
            if (isset($package['dependencies'][$name]) || isset($package['devDependencies'][$name])) {
                continue;
            }
            $package['devDependencies'][$name] = $version;
        }

        $package['scripts'] = is_array($package['scripts'] ?? null) ? $package['scripts'] : [];
        $package['scripts']['dev'] = 'vite --config vite.config.dcode.ts';
        $package['scripts']['build'] = 'vite build --config vite.config.dcode.ts';
        $package['scripts']['dcode:build'] ??= 'pnpm run build';
        $package['scripts']['dcode:typecheck'] ??= 'vue-tsc --noEmit -p tsconfig.dcode.json';

        file_put_contents($path, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    }

    private function updatePnpmWorkspace(string $workingDirectory): void
    {
        $path = $workingDirectory.'/pnpm-workspace.yaml';
        $contents = is_file($path) ? file_get_contents($path) : '';

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read pnpm-workspace.yaml.');
        }

        if (str_contains($contents, 'allowBuilds:') && str_contains($contents, '@dcodegroup-au/dsg-vue')) {
            return;
        }

        if (str_contains($contents, 'onlyBuiltDependencies:')) {
            $contents = preg_replace(
                "~onlyBuiltDependencies:\\s*\\n(?:  - ['\"]?@dcodegroup-au/dsg-vue['\"]?\\s*\\n?)~",
                "allowBuilds:\n  '@dcodegroup-au/dsg-vue': true\n",
                $contents,
                1,
            ) ?? $contents;

            if (str_contains($contents, 'allowBuilds:')) {
                file_put_contents($path, $contents);

                return;
            }
        }

        if (str_contains($contents, 'allowBuilds:')) {
            $contents = preg_replace(
                '/(allowBuilds:\s*\n)/',
                "$1  '@dcodegroup-au/dsg-vue': true\n",
                $contents,
                1,
            ) ?? $contents;
        } else {
            $contents = rtrim($contents)."\n\nallowBuilds:\n  '@dcodegroup-au/dsg-vue': true\n";
        }
        file_put_contents($path, $contents);
    }

    private function registerHostRoutes(string $workingDirectory): void
    {
        $path = $workingDirectory.'/routes/web.php';

        if (! is_file($path)) {
            throw new RuntimeException('No routes/web.php was found. Add the Laravel web route file, then rerun dcode:install.');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read routes/web.php.');
        }

        if (str_contains($contents, self::ROUTE_MARKER)) {
            $updated = $this->upgradeGeneratedRoutes($contents);

            if ($updated !== $contents) {
                file_put_contents($path, $updated);
            }

            return;
        }

        if (preg_match("/['\"]login['\"]|auth\.php|Fortify::routes/s", $contents) === 1) {
            throw new RuntimeException('Existing authentication routes were found in routes/web.php. Review them before adding the Dcode frontend routes.');
        }

        $contents = $this->replaceDefaultRootRoute($contents);
        $contents = $this->addRouteImports($contents);

        file_put_contents($path, rtrim($contents)."\n\n".$this->frontendRoutes()."\n");
    }

    private function replaceDefaultRootRoute(string $contents): string
    {
        $defaultRoute = "/Route::get\(\s*['\"]\\/['\"]\s*,\s*function\s*\(\s*\)\s*\{\s*return\s+view\(\s*['\"]welcome['\"]\s*\)\s*;\s*\}\s*\)\s*;/s";
        $viewRoute = "/Route::view\(\s*['\"]\\/['\"]\s*,\s*['\"]welcome['\"]\s*\)\s*;/s";

        $updated = preg_replace($defaultRoute, "Route::get('/', DashboardController::class);", $contents, 1, $count);

        if ($count === 1) {
            return (string) $updated;
        }

        $updated = preg_replace($viewRoute, "Route::get('/', DashboardController::class);", $contents, 1, $count);

        if ($count === 1) {
            return (string) $updated;
        }

        if (str_contains($contents, "Route::get('/',") || str_contains($contents, "Route::view('/',")) {
            throw new RuntimeException('A custom root route already exists in routes/web.php. Point it to DashboardController manually, then rerun dcode:install.');
        }

        return $contents;
    }

    private function addRouteImports(string $contents): string
    {
        $imports = 'use App\\Http\\Controllers\\DashboardController;'."\n";
        $imports .= 'use App\\Http\\Controllers\\Auth\\AuthenticatedSessionController;'."\n";
        $imports .= 'use App\\Http\\Controllers\\Auth\\NewPasswordController;'."\n";
        $imports .= 'use App\\Http\\Controllers\\Auth\\PasswordResetLinkController;'."\n";
        $imports .= 'use App\\Http\\Controllers\\Settings\\PasswordController;'."\n";
        $imports .= 'use App\\Http\\Controllers\\Settings\\ProfileController;'."\n";
        $imports .= 'use App\\Http\\Middleware\\HandleDcodeInertiaRequests;'."\n";

        if (! str_contains($contents, 'use Illuminate\\Support\\Facades\\Route;')) {
            $imports .= 'use Illuminate\\Support\\Facades\\Route;'."\n";
        }

        return preg_replace('/(<\?php\s*)/', "$1\n{$imports}", $contents, 1) ?? $contents;
    }

    private function frontendRoutes(): string
    {
        return self::ROUTE_MARKER.<<<'PHP'

Route::middleware([HandleDcodeInertiaRequests::class, 'guest'])->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware([HandleDcodeInertiaRequests::class, 'auth'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('account/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('account/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('account/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('account/password', [PasswordController::class, 'update'])->name('password.update');
});
PHP;
    }

    private function upgradeGeneratedRoutes(string $contents): string
    {
        $updated = str_replace(
            [
                ")->name('dcode.login');",
                ")->name('dcode.login.store');",
                ")->name('dcode.password.request');",
                ")->name('dcode.password.email');",
                ")->name('dcode.password.reset');",
                ")->name('dcode.password.update');",
                ")->name('dcode.dashboard');",
                ")->name('dcode.logout');",
                ")->name('dcode.profile.edit');",
                ")->name('dcode.profile.update');",
                ")->name('dcode.password.edit');",
                ")->name('dcode.password.store');",
            ],
            [
                ")->name('login');",
                ');',
                ")->name('password.request');",
                ")->name('password.email');",
                ")->name('password.reset');",
                ")->name('password.store');",
                ")->name('dashboard');",
                ")->name('logout');",
                ")->name('profile.edit');",
                ")->name('profile.update');",
                ")->name('password.edit');",
                ")->name('password.update');",
            ],
            $contents,
        );

        return $updated;
    }

    /** @param array<int, string> $arguments @param callable(string): void $output */
    private function runProcess(array $arguments, string $workingDirectory, callable $output): bool
    {
        $process = new Process($arguments, $workingDirectory, null, null, 3600);
        $process->run(function (string $type, string $buffer) use ($output): void {
            $output($buffer);
        });

        return $process->isSuccessful();
    }

    private readonly Closure $processRunner;
}
