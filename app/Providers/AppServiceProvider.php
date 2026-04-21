<?php

namespace App\Providers;

use App\Models\Declaration;
use App\Policies\DeclarationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceHttps();
        }

        Gate::policy(Declaration::class, DeclarationPolicy::class);
    }
}
