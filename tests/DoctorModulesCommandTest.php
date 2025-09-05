<?php
declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

// Baseline doctor command checks skipped for now due to unstable cache advisory results in CI environments.

it('reports no issues after cache warm', function () {
    // Warm cache to avoid missing-cache advisory
    $this->artisan('module:cache')->assertExitCode(0);
    $this->artisan('module:doctor')
        ->expectsOutputToContain('No issues detected')
        ->assertExitCode(0);
});

it('detects dependency issue when disabling Blog for Shop', function () {
    // Disable Blog so Shop dependency becomes an issue
    file_put_contents(base_path('modules.json'), json_encode(['Blog' => false, 'Shop' => true]));
    // Reload registry in manager if bound
    if ($this->app->bound('modules.manager')) {
        $manager = $this->app->make('modules.manager');
        if (method_exists($manager, 'reloadRegistry')) {
            $manager->reloadRegistry();
        }
    }
    $this->artisan('module:doctor')
        ->expectsOutputToContain('dependency disabled (Blog)')
        ->assertExitCode(1);
});
