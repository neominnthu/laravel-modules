<?php
declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('rebuilds cache with --fix when missing', function () {
    // Ensure cache file removed
    $cacheFile = base_path(\Modules\Support\ModuleManager::CACHE_FILE);
    if (file_exists($cacheFile)) unlink($cacheFile);
    $this->artisan('module:doctor', ['--fix' => true])
        ->expectsOutputToContain('Fix Actions')
        ->assertExitCode(0);
    expect(file_exists($cacheFile))->toBeTrue();
});
