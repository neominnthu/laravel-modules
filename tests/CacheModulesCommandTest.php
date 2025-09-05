<?php

declare(strict_types=1);

use Modules\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

uses(TestCase::class);

it('builds module cache file with module:cache command', function () {
    $cacheFile = app()->basePath('bootstrap/cache/modules.php');
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
    $result = Artisan::call('module:cache');
    $output = Artisan::output();
    expect($result)->toBe(0);
    expect($output)->toContain('Module cache built');
    expect(file_exists($cacheFile))->toBeTrue();
    // Cleanup
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
});
