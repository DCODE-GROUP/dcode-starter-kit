<?php

namespace App\Http\Requests\Admin\Role;

use App\Models\Role;
use App\Support\DcodeStarterKitPermissions;
use Illuminate\Validation\Rule;

class UpdateRequest extends StoreRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(DcodeStarterKitPermissions::ROLES_UPDATE) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('roles', 'name')->ignore($role instanceof Role ? $role->id : $role),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(DcodeStarterKitPermissions::all())],
        ];
    }
}
