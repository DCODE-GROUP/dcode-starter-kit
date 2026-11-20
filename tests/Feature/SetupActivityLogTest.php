<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupActivityLog;

function activityLogSetupFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-activity-'.bin2hex(random_bytes(5));
    mkdir($directory.'/app/Models', 0777, true);
    mkdir($directory.'/config', 0777, true);
    file_put_contents($directory.'/app/Models/User.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
    ];
}
PHP);

    return $directory;
}

it('publishes the activity-log config and integrates the host User model', function () {
    $directory = activityLogSetupFixture();
    $calls = [];
    $runner = function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = [$arguments, $workingDirectory];
        file_put_contents($workingDirectory.'/config/activity-log.php', '<?php return [];');

        return true;
    };

    expect((new SetupActivityLog($runner))->setup($directory, fn () => null))->toBeTrue()
        ->and($calls[0][0])->toBe(['php', 'artisan', 'vendor:publish', '--tag=activity-log-config'])
        ->and(is_file($directory.'/config/activity-log.php'))->toBeTrue()
        ->and(file_get_contents($directory.'/app/Models/User.php'))
        ->toContain('use Dcodegroup\\ActivityLog\\Contracts\\HasActivityUser;')
        ->toContain('use Dcodegroup\\ActivityLog\\Support\\Traits\\ActivityLoggable;')
        ->toContain('use Illuminate\\Database\\Eloquent\\Casts\\Attribute;')
        ->toContain('implements HasActivityUser')
        ->toContain('use ActivityLoggable;')
        ->toContain("protected \$appends = ['full_name'];")
        ->toContain('function fullName')
        ->toContain('function getActivityLogUserName')
        ->toContain('function getActivityLogEmail')
        ->toContain('function getActivityLogUser')
        ->toContain("'full_name' => \$this->getActivityLogUserName()");
});

it('is idempotent when activity-log integration already exists', function () {
    $directory = activityLogSetupFixture();
    file_put_contents($directory.'/config/activity-log.php', '<?php return [];');
    $calls = 0;
    $action = new SetupActivityLog(function () use (&$calls): bool {
        $calls++;

        return true;
    });

    expect($action->setup($directory, fn () => null))->toBeTrue()
        ->and($action->setup($directory, fn () => null))->toBeTrue()
        ->and($calls)->toBe(0);
});
