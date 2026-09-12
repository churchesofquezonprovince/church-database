<?php

namespace App\Providers;

use App\Models\Schedule;
use App\Observers\ScheduleObserver;
use App\Observers\PersonObserver;
use App\Observers\HouseholdObserver;
use App\Models\Person;
use App\Models\Household;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
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
        Schedule::observe(ScheduleObserver::class);
        Person::observe(PersonObserver::class);
        Household::observe(HouseholdObserver::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn (): string =>
                view(
                    'filament.components.back-to-top'
                )->render(),
        );
    }
}
