<?php

declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('shows version constraint failures in doctor output', function () {
    // Set Shop to require Blog >=2.0.0 (but Blog is 1.2.0)
    $shopPath = base_path('Modules/Shop/module.json');
    $orig = file_get_contents($shopPath);
    $data = json_decode($orig, true);
    $data['dependency_versions'] = ['Blog' => '>=2.0.0'];
    file_put_contents($shopPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $this->artisan('module:cache')->assertExitCode(0);
    $this->artisan('module:doctor')
        ->expectsOutputToContain('Dependency Paths')
        ->expectsOutputToContain('[version] Shop → Blog (constraint: >=2.0.0, found: 1.2.0)')
        ->expectsOutputToContain('Shop: dependency Blog version constraint not satisfied (>=2.0.0, found 1.2.0)')
        ->assertExitCode(1);
    // Restore
    file_put_contents($shopPath, $orig);
});
