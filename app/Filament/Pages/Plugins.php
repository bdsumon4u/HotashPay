<?php

namespace App\Filament\Pages;

use App\Plugins\Facades\PluginManager;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Plugins extends Page
{
    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected string $view = 'filament.pages.plugins';

    protected static ?int $navigationSort = 9;

    public array $plugins = [];

    public function mount(): void
    {
        $this->refreshPlugins();
    }

    private function refreshPlugins(): void
    {
        $this->plugins = array_map(fn ($plugin) => $plugin->toArray(), PluginManager::getPluginsFromFolder());
    }

    public function activate(string $pluginFolder): void
    {
        PluginManager::activate($pluginFolder);

        Notification::make()
            ->title('Successfully activated plugin')
            ->success()
            ->send();

        // $this->refreshPlugins();
    }

    public function deactivate(string $pluginFolder): void
    {
        PluginManager::deactivate($pluginFolder);

        Notification::make()
            ->title('Successfully deactivated plugin')
            ->success()
            ->send();

        // $this->refreshPlugins();
    }

    public function deletePlugin(string $pluginFolder): void
    {
        PluginManager::deletePlugin($pluginFolder);

        Notification::make()
            ->title('Successfully deleted plugin')
            ->success()
            ->send();

        // $this->refreshPlugins();
    }
}
