<?php

namespace App\Providers;

use App\Services\Freight\FreightApiSimulator;
use App\Services\Freight\FreightQuoter;
use App\Services\Freight\HttpFreightQuoter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FreightQuoter::class, HttpFreightQuoter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->simulateFreightApi();
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

    protected function simulateFreightApi(): void
    {
        if (! config('services.freight.simulate') || app()->isProduction()) {
            return;
        }

        Http::fake([
            rtrim(config('services.freight.url'), '/').'/*' => fn (Request $request) => (new FreightApiSimulator)($request),
        ]);
    }
}
