<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind('stringHelper', function()
        {
            return new \App\Helpers\StringHelper;
        });

        $this->app->bind('emailHelper', function()
        {
            return new \App\Helpers\EmailHelper;
        });

        $this->app->bind('locationHelper', function()
        {
            return new \App\Helpers\LocationHelper;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'App\Helpers\StringHelper',
            'App\Helpers\EmailHelper',
            'App\Helpers\LocationHelper',
        ];
    }
}