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
            if ($instance instanceof PaymentProvider) {
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
            ->map(fn (PaymentProvider $driver) => $driver->getMetadata())
            ->filter(fn ($meta) => $meta['enabled'] ?? false)
            ->when($type, fn ($collection) => $collection->filter(fn ($meta) => ($meta['type'] ?? null) === $type))
            ->values()
            ->toArray();
    }

    /**
     * Find a payment provider by its ID, name, or any registered alias.
     */
    public function findProvider(PaymentProvider|string|null $identifier): ?PaymentProvider
    {
        if ($identifier === null) {
            return null;
        }

        if ($identifier instanceof PaymentProvider) {
            return $identifier;
        }

        $clean = trim($identifier);
        if ($clean === '' || strcasecmp($clean, 'other') === 0) {
            return null;
        }

        $drivers = $this->getDrivers();

        // 1. Direct ID / folder match
        if (isset($drivers[$clean]) && $drivers[$clean] instanceof PaymentProvider) {
            return $drivers[$clean];
        }

        $lower = mb_strtolower($clean);

        // 2. Exact match against ID, name, or alias
        foreach ($drivers as $driver) {
            if (! $driver instanceof PaymentProvider) {
                continue;
            }

            if (mb_strtolower($driver->getId()) === $lower || mb_strtolower($driver->getName()) === $lower) {
                return $driver;
            }

            foreach ($driver->getAliases() as $alias) {
                if (mb_strtolower(trim($alias)) === $lower) {
                    return $driver;
                }
            }
        }

        // 3. Substring match against driver's aliases or names
        foreach ($drivers as $driver) {
            if ($driver instanceof PaymentProvider && $driver->matches($clean)) {
                return $driver;
            }
        }

        return null;
    }

    /**
     * Resolve a provider from arbitrary text strings (e.g. from receipt notes, beneficiary bank, etc.).
     * Optionally prioritizes a specific type (e.g. 'ibanking' for bank transfers).
     */
    public function resolveProviderFromText(?string $preferredType = null, ?string ...$texts): ?PaymentProvider
    {
        $combined = mb_strtolower(trim(implode(' ', array_filter($texts))));
        if ($combined === '') {
            return null;
        }

        $drivers = collect($this->getDrivers())->filter(fn ($d) => $d instanceof PaymentProvider);

        // If preferred type is specified (e.g. 'ibanking'), check those drivers first
        if ($preferredType !== null) {
            $preferredDrivers = $drivers->filter(fn (PaymentProvider $d) => $d->getType() === $preferredType);
            foreach ($preferredDrivers as $driver) {
                if ($this->textMatchesDriver($combined, $driver)) {
                    return $driver;
                }
            }
        }

        // Check all drivers
        foreach ($drivers as $driver) {
            if ($this->textMatchesDriver($combined, $driver)) {
                return $driver;
            }
        }

        return null;
    }

    private function textMatchesDriver(string $text, PaymentProvider $driver): bool
    {
        $id = mb_strtolower($driver->getId());
        $name = mb_strtolower($driver->getName());

        if (str_contains($text, $id) || str_contains($text, $name)) {
            return true;
        }

        foreach ($driver->getAliases() as $alias) {
            $aliasClean = mb_strtolower(trim($alias));
            if ($aliasClean !== '' && str_contains($text, $aliasClean)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the canonical payment driver key (stored in database transactions.provider).
     */
    public function getDriverKey(PaymentProvider|string|null $provider): ?string
    {
        if (! $provider) {
            return null;
        }

        $resolved = $this->findProvider($provider);

        return $resolved instanceof PaymentProvider ? $resolved->getId() : (string) $provider;
    }

    public function getDefaultDriver()
    {
        return null;
    }
}
