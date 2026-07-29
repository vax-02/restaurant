<?php

namespace App\Providers;

use App\Models\Buy;
use App\Observers\BuyObserver;
use Illuminate\Support\ServiceProvider;

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
        Buy::observe(BuyObserver::class);
    }
}
