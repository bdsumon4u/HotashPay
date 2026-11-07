<?php

use Illuminate\Support\Facades\File;

it('returns plugin image when a supported image type exists', function () {
    $plugin = 'test-image-plugin';
    $pluginPath = resource_path("plugins/{$plugin}");
    $imagesPath = "{$pluginPath}/assets/imgs";

    File::deleteDirectory($pluginPath);
    File::ensureDirectoryExists($imagesPath);
    File::put("{$imagesPath}/logo.jpg", base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAA=='));

    $response = $this->get(route('plugins.image', ['plugin' => $plugin]));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'image/jpeg');

    File::deleteDirectory($pluginPath);
});

it('returns not found when no supported plugin image exists', function () {
    $plugin = 'test-missing-image-plugin';
    $pluginPath = resource_path("plugins/{$plugin}");
    $imagesPath = "{$pluginPath}/assets/imgs";

    File::deleteDirectory($pluginPath);
    File::ensureDirectoryExists($imagesPath);

    $response = $this->get(route('plugins.image', ['plugin' => $plugin]));

    $response->assertNotFound();

    File::deleteDirectory($pluginPath);
});
