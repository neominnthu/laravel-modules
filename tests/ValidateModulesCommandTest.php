<?php

declare(strict_types=1);

use Modules\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use function Pest\uses;
use function Pest\it;
use function Pest\expect;
use function base_path;
use function config;

uses(TestCase::class);

it('detects dependency cycles in module:validate', function () {
    // Simulate cycle: Blog depends on Shop, Shop depends on Blog
    $blogManifest = base_path('Modules/Blog/module.json');
    $shopManifest = base_path('Modules/Shop/module.json');
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
    $blogManifest = base_path('Modules/Blog/module.json');
    $backup = file_get_contents($blogManifest);
    file_put_contents($blogManifest, '{invalid json');
    $result = Artisan::call('module:validate');
    $output = Artisan::output();
    expect($result)->toBe(1);
    expect($output)->toContain('invalid manifest');
    // Restore
    file_put_contents($blogManifest, $backup);
});
