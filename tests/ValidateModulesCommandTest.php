<?php

declare(strict_types=1);

use Modules\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

uses(TestCase::class);

it('detects dependency cycles in module:validate', function () {
    // Simulate cycle: Blog depends on Shop, Shop depends on Blog
    $blogManifest = app()->basePath('Modules/Blog/module.json');
    $shopManifest = app()->basePath('Modules/Shop/module.json');
    $blog = json_decode(file_get_contents($blogManifest), true);
    $shop = json_decode(file_get_contents($shopManifest), true);
    $blog['dependencies'] = ['Shop'];
    $shop['dependencies'] = ['Blog'];
    file_put_contents($blogManifest, json_encode($blog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    file_put_contents($shopManifest, json_encode($shop, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    // Enable both modules
    config(['modules.strict_dependencies' => true]);
    \Modules\Facades\Module::enable('Blog');
    \Modules\Facades\Module::enable('Shop');
    $result = Artisan::call('module:validate');
    $output = Artisan::output();
    expect($result)->toBe(1);
    expect($output)->toContain('dependency cycle');
    // Cleanup: remove cycle
    $blog['dependencies'] = [];
    $shop['dependencies'] = [];
    file_put_contents($blogManifest, json_encode($blog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    file_put_contents($shopManifest, json_encode($shop, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
});

it('reports manifest errors with ModuleManifestException', function () {
    $blogManifest = app()->basePath('Modules/Blog/module.json');
    $sandboxManifest = app()->basePath('vendor/orchestra/testbench-core/laravel/Modules/Blog/module.json');
    $backup = file_get_contents($blogManifest);
    $sandboxBackup = file_exists($sandboxManifest) ? file_get_contents($sandboxManifest) : null;
    try {
        file_put_contents($blogManifest, '{invalid json');
        if ($sandboxBackup !== null) {
            file_put_contents($sandboxManifest, '{invalid json');
        }
        $result = Artisan::call('module:validate');
        $output = Artisan::output();
        expect($result)->toBe(1);
        expect($output)->toContain('invalid manifest');
    } finally {
        file_put_contents($blogManifest, $backup);
        if ($sandboxBackup !== null) {
            file_put_contents($sandboxManifest, $sandboxBackup);
        }
    }
});
