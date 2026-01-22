<?php

namespace App\Payment;

use Closure;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Manager;

#[Singleton]
class PaymentManager extends Manager
{
    public function extend($driver, Closure $callback)
    {
        parent::extend($driver, $callback);

        $this->driver($driver); // Eagerly instantiate the driver

        return $this;
    }

    public function getDriverMetadata(string $driver): array
    {
        try {
            $instance = $this->driver($driver);
            if ($instance instanceof PaymentDriver) {
                return $instance->getMetadata();
            }
        } catch (\Exception $e) {
            // Driver doesn't exist or failed to instantiate
        }

        return [];
    }

    public function getEnabledDrivers(?string $type = null): array
    {
        return collect($this->getDrivers())
            ->map(fn (PaymentDriver $driver) => $driver->getMetadata())
            ->filter(fn ($meta) => $meta['enabled'] ?? false)
            ->when($type, fn ($collection) => $collection->filter(fn ($meta) => ($meta['type'] ?? null) === $type))
            ->values()
            ->toArray();
    }

    public function getDefaultDriver()
    {
        return null;
    }
}
