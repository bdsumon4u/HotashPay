<?php

namespace App\Plugins\BkashMerchant;

use App\Payment\PaymentManager;
use App\Plugins\Plugin;

class BkashMerchantPlugin extends Plugin
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
            $manager->extend('bkash-merchant', fn ($app) => new BkashMerchantDriver($this));
        }
    }

    public function getName(): string
    {
        return 'bKash Merchant';
    }

    public function postActivation(): void
    {
        // Actions to perform after the plugin is activated
    }
}
