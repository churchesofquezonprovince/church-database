<?php

namespace App\Providers;

use App\Observers\PersonObserver;
use App\Observers\HouseholdObserver;
use App\Models\Person;
use App\Models\Household;
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
        Person::observe(PersonObserver::class);
        Household::observe(HouseholdObserver::class);
        //
    }
}
