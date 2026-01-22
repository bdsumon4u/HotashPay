<?php

namespace App\Plugins;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginLoader
{
    public const NAMESPACE_PREFIX = 'App\\Plugins\\';

    private static bool $registered = false;

    public static function getPluginsPath(): string
    {
        return resource_path('plugins');
    }

    public static function getPluginClass(string $pluginName): string
    {
        $studlyName = Str::studly($pluginName);

        return self::NAMESPACE_PREFIX."{$studlyName}\\{$studlyName}Plugin";
    }

    public static function getPluginPath(string $pluginName): string
    {
        return self::getPluginsPath().'/'.basename($pluginName);
    }

    public static function findPluginFile(string $pluginName): ?string
    {
        $studlyName = Str::studly($pluginName);
        $basePath = self::getPluginsPath();

        // Check for exact case match
        $exactPath = "{$basePath}/{$studlyName}/{$studlyName}Plugin.php";
        if (File::exists($exactPath)) {
            return $exactPath;
        }

        // Check for case-insensitive match
        foreach (File::directories($basePath) as $directory) {
            if (strcasecmp(basename($directory), $pluginName) === 0) {
                $filePath = "{$directory}/{$studlyName}Plugin.php";
                if (File::exists($filePath)) {
                    return $filePath;
                }
            }
        }

        return null;
    }

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register(function ($class) {
            $len = strlen(self::NAMESPACE_PREFIX);
            if (strncmp(self::NAMESPACE_PREFIX, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $parts = explode('\\', $relativeClass);

            if (count($parts) < 2) {
                return;
            }

            $pluginName = $parts[0];
            $kebabName = Str::kebab($pluginName);
            $classFile = implode('/', array_slice($parts, 1)).'.php';
            $baseDir = self::getPluginsPath();

            $file = $baseDir.'/'.$kebabName.'/'.$classFile;
            if (File::exists($file)) {
                require $file;

                return;
            }

            $srcFile = $baseDir.'/'.$kebabName.'/src/'.$classFile;
            if (File::exists($srcFile)) {
                require $srcFile;
            }
        });

        self::$registered = true;
    }
}
