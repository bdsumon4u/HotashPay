<?php

use App\Plugins\Facades\PluginManager;

if (! function_exists('is_plugin_active')) {
    function is_plugin_active(string $pluginFolder): bool
    {
        return PluginManager::isPluginActive($pluginFolder);
    }
}