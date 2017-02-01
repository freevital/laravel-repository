<?php

namespace Freevital\Repository\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $config_path = __DIR__ . '/../../config/repository.php';

        // Publish config
        $this->publishes([$config_path => config_path('repository.php')]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $config_path = __DIR__ . '/../../config/repository.php';

        // Merge config
        $this->mergeConfigFrom($config_path, 'repository');
    }
}
