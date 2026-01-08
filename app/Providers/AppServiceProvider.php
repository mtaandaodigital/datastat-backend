<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
        Model::unguard();
        Schema::defaultStringLength(191);
        
        // Register custom authentication provider for MD5 passwords
        \Illuminate\Support\Facades\Auth::provider('custom_eloquent', function ($app, array $config) {
            return new \App\Auth\CustomUserProvider($app['hash'], $config['model']);
        });
    }
}