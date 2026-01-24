<?php

namespace App\Payment;

use App\Plugins\Facades\PluginManager;
use App\Plugins\Plugin;

abstract class PaymentDriver
{
    public function __construct(
        private Plugin $plugin,
    ) {
        //
    }

    public function getId(): string
    {
        return $this->plugin->getFolder();
    }

    public function getName(): string
    {
        return $this->plugin->getName();
    }

    public function getType(): string
    {
        return 'mobile';
    }

    public function getInstructionView(): string
    {
        return $this->plugin->getFolder().'::instruction';
    }

    abstract public function getAliases(): array;

    abstract public function getMessageFormats(): array;

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
