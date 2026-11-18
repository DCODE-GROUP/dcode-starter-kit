<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController
{
    public function edit(): Response
    {
        return Inertia::render('Account/Password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', PasswordRule::defaults()]]);
        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('status', __('dcode-starter-kit::messages.account.password_saved'));
    }
}
