<?php

declare(strict_types=1);

use Modules\Tests\TestCase;
use Modules\Support\ModuleManifest;

uses(TestCase::class);

it('verifies all manifests with valid checksums', function () {
    // Re-write Blog manifest with checksum
    $blogPath = base_path('Modules/Blog/module.json');
    $data = json_decode(file_get_contents($blogPath), true);
    ModuleManifest::writeWithChecksum($blogPath, $data);
    // Re-write Shop manifest with checksum
    $shopPath = base_path('Modules/Shop/module.json');
    $data2 = json_decode(file_get_contents($shopPath), true);
    ModuleManifest::writeWithChecksum($shopPath, $data2);
    $this->artisan('module:verify-manifests')
        ->expectsOutputToContain('Blog: checksum OK')
        ->expectsOutputToContain('Shop: checksum OK')
        ->assertExitCode(0);
});
