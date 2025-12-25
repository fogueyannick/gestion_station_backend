<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;


// app/Providers/AppServiceProvider.php
use Database\Seeders\InitialDataSeeder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot()
    {
        if (\App::environment('production')) {
            $seeder = new InitialDataSeeder();
            $seeder->run();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}




