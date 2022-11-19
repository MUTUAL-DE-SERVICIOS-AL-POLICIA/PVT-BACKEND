<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Affiliate\Affiliate;
use App\Observers\AffiliateObserver;
use Illuminate\Support\Facades\Schema;

class AffiliateModelServiceProvider extends ServiceProvider
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
        if (Schema::connection('platform')->hasTable('affiliates')) Affiliate::observe(AffiliateObserver::class);
    }
}
