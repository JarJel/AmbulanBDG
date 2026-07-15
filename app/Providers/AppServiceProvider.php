<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureDefaults();

        \Illuminate\Support\Facades\RateLimiter::for('guest_sos', function (\Illuminate\Http\Request $request) {
            $ip = $request->ip();
            $phone = $request->input('guest_phone');
            $key = $phone ? "guest_sos:{$ip}:{$phone}" : "guest_sos:{$ip}";
            
            $limit = app()->environment('local', 'testing') ? 60 : 3;
            $decayMinutes = app()->environment('local', 'testing') ? 1 : 10;
            
            return \Illuminate\Cache\RateLimiting\Limit::perMinutes($decayMinutes, $limit)->by($key);
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
