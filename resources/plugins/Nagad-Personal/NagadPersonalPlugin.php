<?php

namespace App\Plugins\NagadPersonal;

use App\Plugins\Plugin;

class NagadPersonalPlugin extends Plugin
{
    public function getName(): string
    {
        return 'Nagad Personal Payment Gateway';
    }

    public function register(): void
    {
        // Register any services, routes, or bindings specific to this plugin
    }

    public function boot(): void
    {
        // Boot any services or perform actions when the plugin is loaded
    }

    public function postActivation(): void
    {
        // Actions to perform after the plugin is activated
    }
}
