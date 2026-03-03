<?php

namespace App\Plugins\Facades;

use App\Plugins\Plugin;
use App\Plugins\PluginManager as PluginManagerService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void loadPlugins()
 * @method static array getPluginsFromFolder()
 * @method static array getInstalledPlugins()
 * @method static void activate(string $pluginFolder)
 * @method static void deactivate(string $pluginFolder)
 * @method static void deletePlugin(string $pluginFolder)
 * @method static ?Plugin loadPluginInstance(string $folder)
 * @method static bool isPluginActive(string $folder)
 *
 * @see \App\Plugins\PluginManager
 */
class PluginManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PluginManagerService::class;
    }
}
