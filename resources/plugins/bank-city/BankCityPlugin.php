<?php

namespace App\Plugins\BankCity;

use App\Payment\PaymentManager;
use App\Plugins\Plugin;

class BankCityPlugin extends Plugin
{
    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->bound(PaymentManager::class)) {
            $manager = $this->app->make(PaymentManager::class);
            $manager->extend('bank-city', fn ($app) => new BankCityDriver($this));
        }
    }

    public function getName(): string
    {
        return 'City Bank';
    }

    public function postActivation(): void
    {
        // Actions to perform after the plugin is activated
    }
}
