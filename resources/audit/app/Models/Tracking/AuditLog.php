<?php

namespace App\Models\Tracking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'user_ip',
        'requested_url',
        'referrer_url',
        'requested_method',
        'requested_action',
        'action_routing',
    ];
}
