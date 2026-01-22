<?php

namespace App\Plugins;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PluginManager
{
    private const CACHE_KEY = 'hotash_installed_plugins';

    private const CACHE_TTL = 3600;

    private array $plugins = [];

    public function __construct(
        protected readonly Application $app
    ) {
        PluginLoader::register();
    }

    public function loadPlugins(): void
    {
        $installedPlugins = $this->getInstalledPlugins();

        if (empty($installedPlugins)) {
            return;
        }

        Log::info('Loading plugins: '.json_encode($installedPlugins));

        foreach ($installedPlugins as $pluginName) {
            $this->loadPlugin($pluginName);
        }
    }

    public function getPluginsFromFolder(): array
    {
        File::ensureDirectoryExists(PluginLoader::getPluginsPath());

        $plugins = [];

        foreach (File::directories(PluginLoader::getPluginsPath()) as $directory) {
            $folder = basename($directory);
            $plugin = $this->loadPluginInstance($folder);

            if (! $plugin) {
                continue;
            }

            $plugins[$folder] = $plugin;
        }

        return $plugins;
    }

    public function getInstalledPlugins(): array
    {
        if ($this->app->bound('cache')) {
            try {
                return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->readInstalledPluginsFile());
            } catch (Exception) {
                // Fallback to direct file access if cache fails
            }
        }

        return $this->readInstalledPluginsFile();
    }

    public function activate(string $pluginFolder): void
    {
        if ($this->isPluginActive($pluginFolder)) {
            return;
        }

        $plugin = $this->loadPluginInstance($pluginFolder);
        if (! $plugin) {
            Log::warning("Cannot activate plugin: {$pluginFolder} - plugin not found");

            return;
        }

        $this->addToInstalledPlugins($pluginFolder);
        $this->runPostActivationCommands($plugin);
    }

    public function deactivate(string $pluginFolder): void
    {
        if (! $this->isPluginActive($pluginFolder)) {
            return;
        }

        $plugin = $this->loadPluginInstance($pluginFolder);
        if ($plugin) {
            $this->runPostDeactivationCommands($plugin);
        }

        $this->removeFromInstalledPlugins($pluginFolder);
    }

    public function deletePlugin(string $pluginFolder): void
    {
        $this->deactivate($pluginFolder);

        $pluginPath = PluginLoader::getPluginPath($pluginFolder);
        if (File::isDirectory($pluginPath)) {
            File::deleteDirectory($pluginPath);
        }
    }

    private function loadPlugin(string $pluginName): void
    {
        $plugin = $this->loadPluginInstance($pluginName);

        if (! $plugin) {
            Log::warning("Plugin not found: {$pluginName}");

            return;
        }

        $this->plugins[$pluginName] = $plugin;
        $this->app->register($plugin);
        Log::info("Loaded plugin: {$plugin->getName()}");
    }

    private function runPostActivationCommands(Plugin $plugin): void
    {
        $plugin->postActivation();

        foreach ($plugin->getPostActivationCommands() as $command) {
            is_string($command) ? Artisan::call($command) : $command();
        }

        $this->runPluginMigrations($plugin);
    }

    private function runPostDeactivationCommands(Plugin $plugin): void
    {
        $plugin->postDeactivation();

        foreach ($plugin->getPostDeactivationCommands() as $command) {
            is_string($command) ? Artisan::call($command) : $command();
        }

        // Note: Plugin migrations are not rolled back automatically
    }

    private function runPluginMigrations(Plugin $plugin): void
    {
        $pluginFolder = $plugin->getFolder();
        $migrationPath = PluginLoader::getPluginPath($pluginFolder).'/database/migrations';

        if (! File::isDirectory($migrationPath)) {
            return;
        }

        Artisan::call('migrate', [
            '--path' => str_replace(base_path('/'), '', $migrationPath),
            '--force' => true,
        ]);
    }

    public function loadPluginInstance(string $folder): ?Plugin
    {
        $pluginPath = PluginLoader::findPluginFile($folder);

        if (! $pluginPath || ! File::exists($pluginPath)) {
            return null;
        }

        require_once $pluginPath;

        $pluginClass = PluginLoader::getPluginClass($folder);

        if (! class_exists($pluginClass)) {
            return null;
        }

        try {
            return new $pluginClass($folder);
        } catch (Exception $e) {
            Log::error("Failed to instantiate plugin: {$pluginClass}", ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function isPluginActive(string $folder): bool
    {
        return in_array($folder, $this->getInstalledPlugins(), true);
    }

    private function readInstalledPluginsFile(): array
    {
        $path = PluginLoader::getPluginPath('installed.json');

        if (! File::exists($path)) {
            return [];
        }

        return File::json($path) ?? [];
    }

    private function updateInstalledPlugins(array $plugins): void
    {
        $path = PluginLoader::getPluginPath('installed.json');
        File::put($path, json_encode($plugins, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($this->app->bound('cache')) {
            Cache::put(self::CACHE_KEY, $plugins, self::CACHE_TTL);
        }
    }

    private function addToInstalledPlugins(string $pluginFolder): void
    {
        $installed = $this->getInstalledPlugins();
        $installed[] = $pluginFolder;
        $this->updateInstalledPlugins(array_unique($installed));
    }

    private function removeFromInstalledPlugins(string $pluginFolder): void
    {
        $installed = array_values(array_diff($this->getInstalledPlugins(), [$pluginFolder]));
        $this->updateInstalledPlugins($installed);
    }
}
