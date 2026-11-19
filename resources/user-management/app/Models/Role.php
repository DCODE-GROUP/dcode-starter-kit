<?php

namespace App\Models;

use Dcodegroup\ActivityLog\Support\Traits\LastModifiedBy;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use LastModifiedBy;
}
