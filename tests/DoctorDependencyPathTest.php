<?php

declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('shows dependency chain paths for missing or cycles', function () {
    // Simulate a missing dependency by adding a fake dep to Blog
    $blogPath = base_path('Modules/Blog/module.json');
    $orig = file_get_contents($blogPath);
    $data = json_decode($orig, true);
    $data['dependencies'] = ['Nonexistent'];
    file_put_contents($blogPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $this->artisan('module:cache')->assertExitCode(0);
    $this->artisan('module:doctor')
        ->expectsOutputToContain('Dependency Paths')
        ->expectsOutputToContain('[missing] Blog → Nonexistent')
        ->assertExitCode(1);
    // Restore
    file_put_contents($blogPath, $orig);
});
