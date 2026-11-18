<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class NewPasswordController
{
    public function create(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::defaults()]]);
        $status = Password::reset($data, function (Authenticatable $user) use ($request): void {
            if (! $user instanceof Model) {
                throw new RuntimeException('The configured password broker user must be an Eloquent model.');
            }
            $user->forceFill(['password' => Hash::make($request->password), 'remember_token' => Str::random(60)])->save();
        });

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return to_route('login')->with('status', __($status));
    }
}
