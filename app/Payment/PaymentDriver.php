<?php

namespace App\Payment;

use App\Plugins\Facades\PluginManager;
use App\Plugins\Plugin;
use App\Plugins\PluginLoader;

abstract class PaymentDriver
{
    public function __construct(
        private Plugin $plugin,
    ) {
        //
    }

    public function getType(): string
    {
        return 'mobile';
    }

    public function getInstructionView(): string
    {
        return $this->plugin->getFolder() . '::instruction';
    }

    public function isEnabled(): bool
    {
        return PluginManager::isPluginActive($this->plugin->getFolder());
    }

    public function getMetadata(): array
    {
        return [
            ...$this->plugin->toArray(),
            'type' => $this->getType(),
            'enabled' => $this->isEnabled(),
            'instruction_view' => $this->getInstructionView(),
        ];
    }
}
