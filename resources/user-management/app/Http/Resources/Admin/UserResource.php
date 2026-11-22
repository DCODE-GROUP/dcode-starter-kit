<?php

namespace App\Http\Resources\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property User $resource */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var EloquentCollection<int, Role> $roles */
        $roles = $this->resource->roles;

        return [
            'id' => $this->resource->id,
            'full_name' => collect([
                $this->resource->first_name,
                $this->resource->middle_name,
                $this->resource->last_name,
            ])->filter()->implode(' ') ?: $this->resource->email,
            'email' => $this->resource->email,
            'active' => (bool) $this->resource->active,
            'roles' => $roles->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
            ])->values(),
            'updated_at' => $this->resource->updated_at?->format('d/m/Y, H:i:s'),
        ];
    }
}
