<?php

namespace App\Console\Commands;

use App\Models\Tracking\AuditLog;
use Illuminate\Console\Command;

class AuditLogGarbageCollect extends Command
{
    protected $signature = 'garbage-collect:audit-logs';

    protected $description = 'Garbage collection for audit logs based on auditing config.';

    public function handle(): int
    {
        if (! config('auditing.flush_old_audits')) {
            return self::SUCCESS;
        }

        $expiry = config('auditing.audit_expiry', '-6 months');

        AuditLog::query()
            ->where('created_at', '<=', date('Y-m-d H:i:s', strtotime((string) $expiry)))
            ->get()
            ->each(function (AuditLog $auditLog): void {
                config('auditing.force_delete') ? $auditLog->forceDelete() : $auditLog->delete();
            });

        return self::SUCCESS;
    }
}
