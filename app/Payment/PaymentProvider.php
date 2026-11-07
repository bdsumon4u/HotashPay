<?php

namespace App\Payment;

use App\Plugins\Facades\PluginManager;
use App\Plugins\Plugin;
use JsonSerializable;
use Stringable;

abstract class PaymentProvider implements JsonSerializable, Stringable
{
    public function __construct(
        protected Plugin $plugin,
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

    /**
     * @return array<string>
     */
    abstract public function getAliases(): array;

    /**
     * @return array<string>
     */
    abstract public function getMessageFormats(): array;

    /**
     * Custom receipt prompt guidance for Gemini Vision, if applicable.
     */
    public function getReceiptPromptNotes(): ?string
    {
        return null;
    }

    /**
     * Check if a given identifier or text matches this provider.
     */
    public function matches(string $query): bool
    {
        $clean = mb_strtolower(trim($query));
        if ($clean === '' || $clean === 'other') {
            return false;
        }

        if (mb_strtolower($this->getId()) === $clean || mb_strtolower($this->getName()) === $clean) {
            return true;
        }

        foreach ($this->getAliases() as $alias) {
            $aliasClean = mb_strtolower(trim($alias));
            if ($aliasClean === $clean || str_contains($clean, $aliasClean) || str_contains($aliasClean, $clean)) {
                return true;
            }
        }

        return false;
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

    public function jsonSerialize(): mixed
    {
        return $this->getId();
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}
