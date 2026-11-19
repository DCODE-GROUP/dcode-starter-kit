<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitFrontend;

function frontendFixture(array $package = ['name' => 'fixture/app']): string
{
    $directory = sys_get_temp_dir().'/dcode-frontend-'.bin2hex(random_bytes(5));
    mkdir($directory, 0777, true);
    mkdir($directory.'/routes', 0777, true);
    file_put_contents($directory.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::get('/', function () {\n    return view('welcome');\n});\n");
    file_put_contents($directory.'/package.json', json_encode($package, JSON_PRETTY_PRINT)."\n");

    return $directory;
}

it('sets up pnpm, installs dependencies, and builds the frontend', function () {
    $directory = frontendFixture(['name' => 'fixture/app', 'packageManager' => 'pnpm@9.0.0', 'scripts' => ['build' => 'vite build']]);
    $calls = [];
    $action = new InstallDcodeStarterKitFrontend(function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = [$arguments, $workingDirectory];

        return true;
    });

    expect($action->install($directory, fn () => null))->toBeTrue()
        ->and($calls)->toHaveCount(2)
        ->and($calls[0][0])->toBe(['pnpm', 'install'])
        ->and($calls[1][0])->toBe(['pnpm', 'run', 'build'])
        ->and(is_file($directory.'/resources/js/app.ts'))->toBeTrue()
        ->and(is_file($directory.'/resources/js/components/Navigation/AppSidebar.vue'))->toBeTrue()
        ->and(is_file($directory.'/app/Services/NavigationService.php'))->toBeTrue()
        ->and(is_file($directory.'/vite.config.ts'))->toBeTrue()
        ->and(is_file($directory.'/tsconfig.json'))->toBeTrue();

    $viteConfig = (string) file_get_contents($directory.'/vite.config.ts');
    expect($viteConfig)->toContain('import.meta.dirname')
        ->toContain('vue/dist/vue.esm-bundler.js')
        ->and($viteConfig)->toContain('resources/js/types/index.ts')
        ->toContain('dsg-vue.table.esm.js')
        ->toContain('node_modules/@dcodegroup-au/dsg-vue/dist/dsg-vue.esm.js')
        ->toContain('vite-svg-loader')
        ->toContain('svgLoader()');
    expect(file_get_contents($directory.'/resources/css/app.css'))
        ->toContain('../../node_modules/@dcodegroup-au/dsg-vue/dist/dsg-vue.css');
    expect(file_get_contents($directory.'/resources/js/app.ts'))
        ->not->toContain('import { trans }');
    expect(file_get_contents($directory.'/resources/js/layouts/App.vue'))
        ->toContain('AppSidebar');
    expect(file_get_contents($directory.'/resources/js/components/Navigation/AppSidebar.vue'))
        ->toContain('sidebar__nav')
        ->toContain('DsgDropdown')
        ->toContain('sidebar__submenu')
        ->toContain('hasActiveSubmenu')
        ->toContain('isNavOpen')
        ->toContain('page.props.navigation?.menu')
        ->not->toContain('admin.users.index')
        ->not->toContain('admin.roles.index');
    expect(file_get_contents($directory.'/resources/css/app.css'))
        ->toContain('.sidebar__nav--closed .dsg-avatar__info')
        ->toContain('.dsg-avatar__subtext')
        ->toContain('.sidebar .sidebar__nav .sidebar__nav-bottom .dsg-avatar .dsg-avatar__link .dsg-avatar__info .dsg-avatar__title');
    expect(file_get_contents($directory.'/resources/js/pages/Auth/Login.vue'))
        ->toContain('auth__content-actions')
        ->toContain('DsgCheckbox');

    $package = json_decode((string) file_get_contents($directory.'/package.json'), true);
    expect($package['devDependencies']['vue'])->toBe('^3.5')
        ->and($package['devDependencies']['@dcodegroup-au/dsg-vue'])->toBe('^1.0.15-next')
        ->and($package['devDependencies']['vite-svg-loader'])->toBe('^5.1.1')
        ->and($package['scripts']['dev'])->toBe('vite')
        ->and($package['scripts']['build'])->toBe('vite build')
        ->and($package['scripts']['typecheck'])->toBe('vue-tsc --noEmit -p tsconfig.json')
        ->and($package['packageManager'])->toBe('pnpm@9.0.0')
        ->and($package['engines']['pnpm'])->toBe('>=9.0.0')
        ->and($package['pnpm'] ?? null)->toBeNull();

    expect(file_get_contents($directory.'/pnpm-workspace.yaml'))->toContain("allowBuilds:\n  '@dcodegroup-au/dsg-vue': true");

    $routes = (string) file_get_contents($directory.'/routes/web.php');
    $authRoutes = (string) file_get_contents($directory.'/routes/auth.php');
    expect(substr_count($routes, '// Dcode Starter Kit frontend routes'))->toBe(1)
        ->and($routes)->toContain("Route::get('/', DashboardController::class);")
        ->and($routes)->toContain("Route::get('admin/dashboard', DashboardController::class)->name('admin.dashboard');")
        ->and($routes)->toContain('ProfileController::class')
        ->and($routes)->toContain("require __DIR__.'/auth.php';")
        ->and($routes)->not->toContain('AuthenticatedSessionController::class')
        ->and($authRoutes)->toContain('// Dcode Starter Kit authentication routes')
        ->and($authRoutes)->toContain('AuthenticatedSessionController::class')
        ->and($authRoutes)->toContain('NewPasswordController::class')
        ->and($authRoutes)->toContain("Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');")
        ->and($authRoutes)->toContain("Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])")
        ->and($authRoutes)->toContain("->name('login')")
        ->and($authRoutes)->toContain("->name('password.request')")
        ->and($authRoutes)->toContain("->name('password.store')")
        ->and($routes)->toContain("->name('admin.dashboard')")
        ->and($routes)->toContain("->name('profile.edit')")
        ->and($routes)->toContain("->name('password.update')")
        ->not->toContain("->name('dcode.login')")
        ->not->toContain("require __DIR__.'/dcode-starter-kit.php';");
});

it('upgrades the default Laravel frontend files to the standard starter entry points', function () {
    $directory = frontendFixture();
    mkdir($directory.'/resources/css', 0777, true);
    mkdir($directory.'/resources/js', 0777, true);
    file_put_contents($directory.'/resources/css/app.css', "@import 'tailwindcss';\n");
    file_put_contents($directory.'/resources/js/app.js', "import './bootstrap';\n");
    file_put_contents($directory.'/vite.config.js', <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [laravel({
        input: ['resources/css/app.css', 'resources/js/app.js'],
        refresh: true,
    })],
});
JS);
    file_put_contents($directory.'/tsconfig.json', json_encode([
        'compilerOptions' => ['allowJs' => true],
        'include' => ['resources/js/**/*.js'],
    ], JSON_PRETTY_PRINT)."\n");

    expect((new InstallDcodeStarterKitFrontend(fn () => true))->install($directory, fn () => null))->toBeTrue()
        ->and(is_file($directory.'/vite.config.js'))->toBeFalse()
        ->and(is_file($directory.'/resources/js/app.js'))->toBeFalse()
        ->and(file_get_contents($directory.'/resources/js/app.ts'))->toContain('createInertiaApp')
        ->and(json_decode((string) file_get_contents($directory.'/tsconfig.json'), true)['compilerOptions']['allowJs'])->toBeTrue()
        ->and(file_get_contents($directory.'/vite.config.ts'))->toContain('resources/js/app.ts');
});

it('replaces an existing application stylesheet with the starter stylesheet', function () {
    $directory = frontendFixture();
    mkdir($directory.'/resources/css', 0777, true);
    file_put_contents($directory.'/resources/css/app.css', '/* Laravel application styles */');

    expect((new InstallDcodeStarterKitFrontend(fn () => true))->install($directory, fn () => null))->toBeTrue()
        ->and(file_get_contents($directory.'/resources/css/app.css'))
        ->toContain('@import "tailwindcss";')
        ->toContain('dsg-vue');
});

it('migrates its older special frontend filenames', function () {
    $directory = frontendFixture();
    mkdir($directory.'/resources/css', 0777, true);
    mkdir($directory.'/resources/js', 0777, true);
    file_put_contents($directory.'/resources/css/dcode-starter-kit.css', "@import \"tailwindcss\";\n@import \"../../node_modules/@dcodegroup-au/dsg-vue/dist/dsg-vue.css\";\n");
    file_put_contents($directory.'/resources/js/dcode-starter-kit.ts', "import { createInertiaApp } from '@inertiajs/vue3';\nimport \"../css/dcode-starter-kit.css\";\n");
    file_put_contents($directory.'/vite.config.dcode.ts', "import laravel from 'laravel-vite-plugin';\nexport default { plugins: [laravel({ input: ['resources/css/dcode-starter-kit.css'] })] };\n");
    file_put_contents($directory.'/tsconfig.dcode.json', json_encode([
        'compilerOptions' => ['types' => ['vite/client']],
        'include' => ['resources/**/*.vue'],
    ], JSON_PRETTY_PRINT)."\n");

    expect((new InstallDcodeStarterKitFrontend(fn () => true))->install($directory, fn () => null))->toBeTrue()
        ->and(is_file($directory.'/resources/css/dcode-starter-kit.css'))->toBeFalse()
        ->and(is_file($directory.'/resources/js/dcode-starter-kit.ts'))->toBeFalse()
        ->and(is_file($directory.'/vite.config.dcode.ts'))->toBeFalse()
        ->and(is_file($directory.'/tsconfig.dcode.json'))->toBeFalse()
        ->and(is_file($directory.'/resources/css/app.css'))->toBeTrue()
        ->and(is_file($directory.'/resources/js/app.ts'))->toBeTrue()
        ->and(is_file($directory.'/vite.config.ts'))->toBeTrue()
        ->and(is_file($directory.'/tsconfig.json'))->toBeTrue();
});

it('rejects a conflicting package manager', function () {
    $directory = frontendFixture(['name' => 'fixture/app', 'packageManager' => 'yarn@1.22.0']);
    $messages = [];

    expect((new InstallDcodeStarterKitFrontend(fn () => true))->install($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('requires pnpm');
});

it('refuses to overwrite generated frontend files', function () {
    $directory = frontendFixture();
    mkdir($directory.'/resources/js', 0777, true);
    file_put_contents($directory.'/resources/js/app.ts', 'custom');
    $messages = [];

    expect((new InstallDcodeStarterKitFrontend(fn () => true))->install($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Frontend files already exist');
});

it('can be rerun without treating its own generated files as conflicts', function () {
    $directory = frontendFixture();
    $calls = 0;
    $action = new InstallDcodeStarterKitFrontend(function () use (&$calls): bool {
        $calls++;

        return true;
    });

    expect($action->install($directory, fn () => null))->toBeTrue()
        ->and(file_put_contents($directory.'/pnpm-workspace.yaml', "onlyBuiltDependencies:\n  - @dcodegroup-au/dsg-vue\n"))->toBeGreaterThan(0)
        ->and($action->install($directory, fn () => null))->toBeTrue()
        ->and(file_get_contents($directory.'/pnpm-workspace.yaml'))->toContain("allowBuilds:\n  '@dcodegroup-au/dsg-vue': true")
        ->and($calls)->toBe(4);
});

it('upgrades the generated dcode route names when rerun', function () {
    $directory = frontendFixture();
    $action = new InstallDcodeStarterKitFrontend(fn () => true);

    expect($action->install($directory, fn () => null))->toBeTrue();

    $routePath = $directory.'/routes/web.php';
    $routes = (string) file_get_contents($routePath);
    $routes = str_replace(
        [
            "->name('login')",
            "->name('password.request')",
            "->name('password.email')",
            "->name('password.reset')",
            "->name('password.store')",
            "->name('admin.dashboard')",
            "->name('logout')",
            "->name('profile.edit')",
            "->name('profile.update')",
            "->name('password.edit')",
            "->name('password.update')",
        ],
        [
            "->name('dcode.login')",
            "->name('dcode.password.request')",
            "->name('dcode.password.email')",
            "->name('dcode.password.reset')",
            "->name('dcode.password.update')",
            "->name('dcode.dashboard')",
            "->name('dcode.logout')",
            "->name('dcode.profile.edit')",
            "->name('dcode.profile.update')",
            "->name('dcode.password.edit')",
            "->name('dcode.password.store')",
        ],
        $routes,
    );
    file_put_contents($routePath, $routes);

    $authPath = $directory.'/routes/auth.php';
    $authRoutes = (string) file_get_contents($authPath);
    $authRoutes = str_replace(
        [
            "->name('login')",
            "->name('password.request')",
            "->name('password.email')",
            "->name('password.reset')",
            "->name('password.store')",
            "->name('logout')",
        ],
        [
            "->name('dcode.login')",
            "->name('dcode.password.request')",
            "->name('dcode.password.email')",
            "->name('dcode.password.reset')",
            "->name('dcode.password.update')",
            "->name('dcode.logout')",
        ],
        $authRoutes,
    );
    file_put_contents($authPath, $authRoutes);

    expect($action->install($directory, fn () => null))->toBeTrue()
        ->and(file_get_contents($routePath))->not->toContain('dcode.')
        ->and(file_get_contents($authPath))->not->toContain('dcode.')
        ->and(file_get_contents($authPath))->toContain("->name('login')")
        ->toContain("->name('password.store')")
        ->and(file_get_contents($routePath))->toContain("->name('password.update')")
        ->toContain("Route::get('admin/dashboard', DashboardController::class)->name('admin.dashboard');")
        ->toContain("->name('admin.dashboard')");
});

it('requires a package manifest', function () {
    $directory = sys_get_temp_dir().'/dcode-frontend-'.bin2hex(random_bytes(5));
    mkdir($directory, 0777, true);
    $messages = [];

    expect((new InstallDcodeStarterKitFrontend(fn () => true))->install($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('No package.json was found');
});
