<?php

namespace App\Providers;

use App\Contracts\CustomDomainVerifier;
use App\Support\NetworkCustomDomainVerifier;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CustomDomainVerifier::class, NetworkCustomDomainVerifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
