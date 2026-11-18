<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupAttachments;

function attachmentsSetupFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-attachment-setup-'.bin2hex(random_bytes(5));
    mkdir($directory.'/app/Models', 0777, true);
    mkdir($directory.'/bootstrap', 0777, true);
    mkdir($directory.'/config', 0777, true);
    mkdir($directory.'/routes', 0777, true);
    file_put_contents($directory.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    file_put_contents($directory.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
    file_put_contents($directory.'/app/Models/User.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
}
PHP);

    return $directory;
}

it('publishes attachment config, registers routes and integrates User', function () {
    $directory = attachmentsSetupFixture();
    $runner = function (array $arguments, string $workingDirectory, callable $output): bool {
        if ($arguments[3] === '--tag=laravel-attachments-config') {
            file_put_contents($workingDirectory.'/config/attachments.php', '<?php return [];');
        }

        if ($arguments[3] === '--tag=medialibrary-config') {
            file_put_contents($workingDirectory.'/config/media-library.php', <<<'PHP'
<?php

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

return [
    'media_model' => Media::class,
    'path_generator' => DefaultPathGenerator::class,
];
PHP);
        }

        return true;
    };

    expect((new SetupAttachments($runner))->setup($directory, fn () => null))->toBeTrue()
        ->and(is_file($directory.'/app/Providers/AttachmentServiceProvider.php'))->toBeTrue()
        ->and(is_file($directory.'/app/Policies/MediaPolicy.php'))->toBeTrue()
        ->and(is_file($directory.'/routes/laravel_attachments.php'))->toBeTrue()
        ->and(file_get_contents($directory.'/bootstrap/providers.php'))->toContain('AttachmentServiceProvider::class')
        ->and(file_get_contents($directory.'/routes/web.php'))->toContain('laravel_attachments.php')
        ->and(file_get_contents($directory.'/config/media-library.php'))
        ->toContain('Dcodegroup\\LaravelAttachments\\Models\\Media;')
        ->toContain('MediaPathGenerator::class')
        ->and(file_get_contents($directory.'/app/Models/User.php'))
        ->toContain('HasMediaUser')
        ->toContain('getMediaUserName');
});

it('refuses to replace customized media library settings', function () {
    $directory = attachmentsSetupFixture();
    file_put_contents($directory.'/config/attachments.php', '<?php return [];');
    file_put_contents($directory.'/config/media-library.php', <<<'PHP'
<?php

return [
    'media_model' => App\Models\CustomMedia::class,
    'path_generator' => App\Media\CustomPathGenerator::class,
];
PHP);

    $messages = [];

    expect((new SetupAttachments(function (): bool {
        return true;
    }))->setup($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Refusing to overwrite customised config/media-library.php');
});

it('can be rerun after attachment setup is complete', function () {
    $directory = attachmentsSetupFixture();
    $runner = function (array $arguments, string $workingDirectory, callable $output): bool {
        if ($arguments[3] === '--tag=laravel-attachments-config') {
            file_put_contents($workingDirectory.'/config/attachments.php', '<?php return [];');
        }

        if ($arguments[3] === '--tag=medialibrary-config') {
            file_put_contents($workingDirectory.'/config/media-library.php', <<<'PHP'
<?php

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

return [
    'media_model' => Media::class,
    'path_generator' => DefaultPathGenerator::class,
];
PHP);
        }

        return true;
    };
    $setup = new SetupAttachments($runner);

    expect($setup->setup($directory, fn () => null))->toBeTrue()
        ->and($setup->setup($directory, fn () => null))->toBeTrue();
});
