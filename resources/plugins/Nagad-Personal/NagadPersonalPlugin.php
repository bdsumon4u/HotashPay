<?php

namespace App\Plugins\NagadPersonal;

use App\Payment\PaymentManager;
use App\Plugins\Plugin;

class NagadPersonalPlugin extends Plugin
{
    public function register(): void
    {
        parent::register();
        // Register any services, routes, or bindings specific to this plugin
    }

    public function boot(): void
    {
        parent::boot();

        // Register payment driver
        if ($this->app->bound(PaymentManager::class)) {
            $manager = $this->app->make(PaymentManager::class);
            $manager->extend('nagad-personal', fn ($app) => new NagadPersonalDriver($this));
        }
    }

    public function postActivation(): void
    {
        // Actions to perform after the plugin is activated
    }
}
