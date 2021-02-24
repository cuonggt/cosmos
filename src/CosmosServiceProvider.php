<?php

namespace Sinclair\Cosmos;

use Illuminate\Support\ServiceProvider;

class CosmosServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cosmos.php' => config_path('cosmos.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cosmos.php', 'cosmos');

        $this->app->bind('cosmos', Cosmos::class);

        $this->app->bind('document', Models\Document::class);
    }
}
