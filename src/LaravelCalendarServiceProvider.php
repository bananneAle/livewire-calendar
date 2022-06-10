<?php

namespace Bananneale\LaravelCalendar;

use Illuminate\Support\ServiceProvider;
use Bananneale\LaravelCalendar\Http\Livewire\Calendar;
use Livewire\Livewire;

class LaravelCalendarServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Livewire::component('calendar', Calendar::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-calendar');
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-calendar'),
        ]);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}