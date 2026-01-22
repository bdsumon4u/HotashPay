<?php

namespace App\Http\Controllers;

use App\Plugins\Facades\PluginManager;
use App\Plugins\Plugin;
use App\Plugins\PluginLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PluginImageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $plugin)
    {
        $path = PluginLoader::getPluginPath($plugin).'/assets/imgs/logo.png';

        if (! File::exists($path)) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        $response = response($file);
        $response->header('Content-Type', $type);

        return $response;
    }
}
