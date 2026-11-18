<?php

namespace App\Http\Requests\Admin\Role;

use App\Support\DcodeStarterKitPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(DcodeStarterKitPermissions::ROLES_CREATE) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('roles', 'name')],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(DcodeStarterKitPermissions::all())],
        ];
    }
}
