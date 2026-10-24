<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Traits\HasRoles;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerSuperAdminGate();
    }

    /**
     * Grant the seeded Super Admin role every ability without bypassing
     * unrelated security boundaries. Returning null for everyone else lets
     * policies and permissions resolve the check as normal (ADR-002).
     */
    protected function registerSuperAdminGate(): void
    {
        Gate::before(static function ($user) {
            /** @var User|HasRoles $user */
            if (method_exists($user, 'hasRole') && $user->hasRole('super admin')) {
                return true;
            }

            /** Unrelated systems (e.g. package-internal checks) keep their own rules. */
            return null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
