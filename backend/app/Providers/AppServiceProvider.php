<?php

namespace App\Providers;

use App\Models\MenuItem;
use App\Observers\MenuItemObserver;
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
        MenuItem::observe(MenuItemObserver::class);
    }
}
