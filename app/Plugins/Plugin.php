<?php

namespace App\Plugins;

use Illuminate\Support\ServiceProvider;

abstract class Plugin extends ServiceProvider
{
    public function __construct(
        protected readonly string $folder,
    ) {
        parent::__construct(app());
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    public function getName(): string
    {
        return str(basename($this->folder))->replace('-', ' ')->title();
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getAuthor(): string
    {
        return 'Hotash Tech';
    }

    public function getDescription(): string
    {
        return 'A very good plugin.';
    }

    public function register(): void
    {
        // Default register logic, can be overridden by specific plugins
    }

    public function boot(): void
    {
        // Default boot logic, can be overridden by specific plugins
    }

    public function postActivation(): void
    {
        // Default implementation (empty)
    }

    public function postDeactivation(): void
    {
        // Default implementation (empty)
    }

    public function getPostActivationCommands(): array
    {
        return [];
    }

    public function getPostDeactivationCommands(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'author' => $this->getAuthor(),
            'description' => $this->getDescription(),
            'folder' => $this->getFolder(),
        ];
    }
}
