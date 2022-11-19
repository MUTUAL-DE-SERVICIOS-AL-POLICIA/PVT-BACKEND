<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Affiliate\Spouse;
use App\Observers\SpouseObserver;
use Illuminate\Support\Facades\Schema;

class SpouseModelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (Schema::connection('platform')->hasTable('spouses')) Spouse::observe(SpouseObserver::class);
    }
}
