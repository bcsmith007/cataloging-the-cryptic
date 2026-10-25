<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegistrationResponse;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Filament\Auth\Http\Responses\Contracts\RegistrationResponse::class,
            RegistrationResponse::class
        );

        $this->app->singleton(
            \Filament\Auth\Http\Responses\Contracts\LoginResponse::class,
            LoginResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());

        // Carbon macro to convert any date instance to the active user timezone
        Carbon::macro('inUserTimezone', function (?string $timezone = null) {
            /** @var Carbon $this */
            return TimezoneService::convert($this, $timezone);
        });

        // Blade directive for formatting dates in the active user timezone
        Blade::directive('userDate', function ($expression) {
            return "<?php echo \App\Services\TimezoneService::format({$expression}); ?>";
        });

        // Blade directive for formatting datetimes in the active user timezone
        Blade::directive('userDateTime', function ($expression) {
            return "<?php echo \App\Services\TimezoneService::formatDateTime({$expression}); ?>";
        });
    }
}
