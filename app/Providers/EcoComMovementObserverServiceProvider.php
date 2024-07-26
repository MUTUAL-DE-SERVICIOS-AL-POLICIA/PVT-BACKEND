<?php

namespace App\Providers;

use App\Models\EconomicComplement\EcoComMovement;
use App\Observers\EcoComMovementObserver;
use Illuminate\Support\ServiceProvider;

class EcoComMovementObserverServiceProvider extends ServiceProvider
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
        EcoComMovement::observe(EcoComMovementObserver::class);
    }
}
