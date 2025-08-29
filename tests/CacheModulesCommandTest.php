<?php

declare(strict_types=1);

use Modules\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use function Pest\uses;
use function Pest\it;
use function Pest\expect;
use function base_path;

uses(TestCase::class);

it('builds module cache file with module:cache command', function () {
    $cacheFile = base_path('bootstrap/cache/modules.php');
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
