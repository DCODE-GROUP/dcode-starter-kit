<?php

namespace App\Providers;

use App\Policies\MediaPolicy;
use Dcodegroup\LaravelAttachments\Models\Media;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AttachmentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Media::class, MediaPolicy::class);
    }
}
