<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ServeCommand::class,
            \App\Console\Commands\ServeCommand::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applyOrganisationTimezone();

        // Administrators implicitly pass every authorization check.
        Gate::before(function (User $user) {
            return $user->isAdmin() ? true : null;
        });

        Gate::define('manage-settings', fn (User $user) => $user->isAdmin());
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
        Gate::define('manage-catalog', fn (User $user) => $user->hasRole(Role::Admin, Role::ResourceManager));
        Gate::define('manage-resources', fn (User $user) => $user->hasRole(Role::Admin, Role::ResourceManager));
        Gate::define('view-reports', fn (User $user) => $user->hasRole(Role::Admin, Role::ResourceManager));
        Gate::define('approve-bookings', fn (User $user) => $user->hasRole(Role::Admin, Role::ResourceManager));
    }

    /**
     * Apply the organisation's configured timezone to the whole application.
     * Guarded so it is a no-op before the settings table exists (install,
     * migrations, early console boot).
     */
    protected function applyOrganisationTimezone(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $timezone = Setting::get('org_timezone');

            if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (\Throwable) {
            // Settings are not available yet; fall back to the environment config.
        }
    }
}
