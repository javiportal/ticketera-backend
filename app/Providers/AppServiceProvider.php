<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('manage-users', fn (User $user) => $user->hasPermission('manage-users'));
        Gate::define('manage-events', fn (User $user) => $user->hasPermission('manage-events'));
        Gate::define('validate-tickets', fn (User $user) => $user->hasPermission('validate-tickets'));
        Gate::define('view-reports', fn (User $user) => $user->hasPermission('view-reports'));
    }
}