<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupAuditLogging;

function auditFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-audit-'.bin2hex(random_bytes(5));
    mkdir($directory.'/routes', 0777, true);
    file_put_contents($directory.'/routes/console.php', "<?php\n");

    return $directory;
}

it('installs host audit logging and schedules retention cleanup', function () {
    $directory = auditFixture();

    expect((new SetupAuditLogging)->setup($directory, fn () => null))->toBeTrue()
        ->and(is_file($directory.'/config/auditing.php'))->toBeTrue()
        ->and(is_file($directory.'/app/Models/Tracking/AuditLog.php'))->toBeTrue()
        ->and(is_file($directory.'/app/Http/Middleware/AdminAuditLog.php'))->toBeTrue()
        ->and(is_file($directory.'/app/Console/Commands/AuditLogGarbageCollect.php'))->toBeTrue()
        ->and(is_file($directory.'/database/migrations/0001_01_01_000007_create_audit_logs_table.php'))->toBeTrue();

    $routes = (string) file_get_contents($directory.'/routes/console.php');
    expect($routes)->toContain('AuditLogGarbageCollect::class')
        ->toContain('Schedule::command')
        ->toContain('// Dcode Starter Kit audit log schedule');
});

it('does not duplicate the audit logging scaffold or schedule on rerun', function () {
    $directory = auditFixture();
    $action = new SetupAuditLogging;

    expect($action->setup($directory, fn () => null))->toBeTrue()
        ->and($action->setup($directory, fn () => null))->toBeTrue();

    $routes = (string) file_get_contents($directory.'/routes/console.php');
    expect(substr_count($routes, '// Dcode Starter Kit audit log schedule'))->toBe(1)
        ->and(substr_count($routes, 'Schedule::command'))->toBe(1);
});
