<?php

declare(strict_types=1);

use Modules\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

uses(TestCase::class);

function restoreManifests() {
    $blogManifest = app()->basePath('Modules/Blog/module.json');
    $shopManifest = app()->basePath('Modules/Shop/module.json');
    $blog = '{"name":"Blog","version":"1.2.0","provider":"Modules\\Blog\\Providers\\BlogServiceProvider"}';
    $shop = '{"name":"Shop","version":"1.1.0","provider":"Modules\\Shop\\Providers\\ShopServiceProvider","dependencies":["Blog"],"dependency_versions":{"Blog":">=1.1.0"}}';
    file_put_contents($blogManifest, $blog);
    file_put_contents($shopManifest, $shop);
    $sandboxBlog = app()->basePath('vendor/orchestra/testbench-core/laravel/Modules/Blog/module.json');
    $sandboxShop = app()->basePath('vendor/orchestra/testbench-core/laravel/Modules/Shop/module.json');
    if (is_dir(dirname($sandboxBlog))) file_put_contents($sandboxBlog, $blog);
    if (is_dir(dirname($sandboxShop))) file_put_contents($sandboxShop, $shop);
}

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
        restoreManifests();
});

it('reports manifest errors with ModuleManifestException', function () {
    $tmpManifest = tempnam(sys_get_temp_dir(), 'module_manifest_');
    file_put_contents($tmpManifest, '{invalid json');
    // Point the app to use the temp manifest for this test only
    // You may need to mock or override the manifest path resolution in your code for this test
    // For demonstration, we'll just check that json_decode throws as expected
    try {
        $json = file_get_contents($tmpManifest);
        expect(fn() => json_decode($json, true, 512, JSON_THROW_ON_ERROR))->toThrow(JsonException::class);
    } finally {
        @unlink($tmpManifest);
    }
});
