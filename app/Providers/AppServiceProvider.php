<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\CepRepository;
use App\Repositories\CepFallback;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CepRepository::class, CepFallback::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
