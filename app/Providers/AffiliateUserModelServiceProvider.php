<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Affiliate\AffiliateUser;
use App\Observers\AffiliateUserObserver;
use Illuminate\Support\Facades\Schema;

class AffiliateUserModelServiceProvider extends ServiceProvider
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
        if (Schema::connection('platform')->hasTable('affiliate_users')) AffiliateUser::observe(AffiliateUserObserver::class);
    }
}
