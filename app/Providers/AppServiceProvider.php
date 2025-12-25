<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
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


    public function boot(): void
    {
        // 🛑 Stop si on est en CLI (composer, artisan, docker build)
        if (app()->runningInConsole()) {
            return;
        }
    
        // 🛑 Stop si la table n'existe pas encore
        if (!Schema::hasTable('users')) {
            return;
        }
    
        // ✅ Là seulement tu peux interroger la DB
        // Exemple :
        // $gerant = User::where('username', 'gerant')->first();
    }


    /**
     * Bootstrap any application services.
    
    public function boot(): void
    {
        //
    } */
}




