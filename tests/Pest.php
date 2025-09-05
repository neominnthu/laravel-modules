<?php

/**
 * Pest configuration for the package test suite.
 */

use Pest\Laravel\commands; // Keeps IDE helper reference

beforeEach(function () {
    // Ensure baseline: Blog enabled, Shop disabled
    if (file_exists(base_path('modules.json'))) {
        $state = json_decode(file_get_contents(base_path('modules.json')), true) ?: [];
        $changed = false;
        if (($state['Blog'] ?? null) !== true) { $state['Blog'] = true; $changed = true; }
        if (($state['Shop'] ?? null) !== false) { $state['Shop'] = false; $changed = true; }
        if ($changed) {
            file_put_contents(base_path('modules.json'), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    // Restore module manifests before each test
    $paths = [];
    $base = base_path();
    $paths[] = $base . DIRECTORY_SEPARATOR . 'Modules';
    $vendorTestbench = $base . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'orchestra' . DIRECTORY_SEPARATOR . 'testbench-core' . DIRECTORY_SEPARATOR . 'laravel' . DIRECTORY_SEPARATOR . 'Modules';
    $paths[] = $vendorTestbench;
    foreach ($paths as $destModules) {
        foreach ([
            'Blog' => '{"name":"Blog","version":"1.2.0","provider":"Modules\\Blog\\Providers\\BlogServiceProvider"}',
            'Shop' => '{"name":"Shop","version":"1.1.0","provider":"Modules\\Shop\\Providers\\ShopServiceProvider","dependencies":["Blog"],"dependency_versions":{"Blog":">=1.1.0"}}'
        ] as $mod => $manifest) {
            $modDir = $destModules . DIRECTORY_SEPARATOR . $mod;
            $manifestPath = $modDir . DIRECTORY_SEPARATOR . 'module.json';
            if (is_dir($modDir)) {
                $cleanManifest = str_replace(["\r\n", "\r"], "\n", $manifest);
                file_put_contents($manifestPath, $cleanManifest);
            }
        }
    }
});

afterEach(function () {
    // Restore module manifests after each test
    $paths = [];
    $base = base_path();
    $paths[] = $base . DIRECTORY_SEPARATOR . 'Modules';
    $vendorTestbench = $base . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'orchestra' . DIRECTORY_SEPARATOR . 'testbench-core' . DIRECTORY_SEPARATOR . 'laravel' . DIRECTORY_SEPARATOR . 'Modules';
    $paths[] = $vendorTestbench;
    foreach ($paths as $destModules) {
        foreach ([
            'Blog' => '{"name":"Blog","version":"1.2.0","provider":"Modules\\Blog\\Providers\\BlogServiceProvider"}',
            'Shop' => '{"name":"Shop","version":"1.1.0","provider":"Modules\\Shop\\Providers\\ShopServiceProvider","dependencies":["Blog"],"dependency_versions":{"Blog":">=1.1.0"}}'
        ] as $mod => $manifest) {
            $modDir = $destModules . DIRECTORY_SEPARATOR . $mod;
            $manifestPath = $modDir . DIRECTORY_SEPARATOR . 'module.json';
            if (is_dir($modDir)) {
                $cleanManifest = str_replace(["\r\n", "\r"], "\n", $manifest);
                file_put_contents($manifestPath, $cleanManifest);
            }
        }
    }
});
