<?php

namespace App\Plugins;

use App\Plugins\Facades\PluginManager;
use App\Plugins\PluginManager as PluginManagerService;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManagerService::class, function ($app) {
            return new PluginManagerService($app);
        });

        require_once __DIR__.'/functions.php';
    }

    public function boot(): void
    {
        PluginManager::loadPlugins();
    }
}
