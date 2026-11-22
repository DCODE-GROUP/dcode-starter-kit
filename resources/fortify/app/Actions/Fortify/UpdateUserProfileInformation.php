<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function update($user, array $input): void
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ])->validateWithBag('updateProfileInformation');

        $data = [
            'first_name' => $input['first_name'],
            'middle_name' => $input['middle_name'] ?? null,
            'last_name' => $input['last_name'],
            'email' => $input['email'],
        ];

        if (($input['photo'] ?? null) instanceof UploadedFile) {
            $path = $input['photo']->store('profile-photos', 'public');

            if ($path !== false) {
                $data['profile_photo_path'] = $path;
            }
        }

        if ($input['email'] !== $user->email && $user instanceof MustVerifyEmail) {
            $data['email_verified_at'] = null;

            /** @var Model&MustVerifyEmail $user */
            $user->forceFill($data)->save();
            $user->sendEmailVerificationNotification();

            return;
        }

        $user->forceFill($data)->save();
    }
}
