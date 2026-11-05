<?php

namespace App\Http\Controllers;

use App\Plugins\PluginLoader;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class PluginImageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $plugin): Response
    {
        $extensions = ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'];
        $path = null;

        foreach ($extensions as $extension) {
            $candidatePath = PluginLoader::getPluginPath($plugin)."/assets/imgs/logo.{$extension}";

            if (File::exists($candidatePath)) {
                $path = $candidatePath;

                break;
            }
        }

        if ($path === null) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path) ?: 'application/octet-stream';

        $response = response($file);
        $response->header('Content-Type', $type);

        return $response;
    }
}
