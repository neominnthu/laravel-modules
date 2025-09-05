<?php

declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('publishes the config file', function () {
    $target = config_path('modules.php');
    if (is_file($target)) {
        unlink($target);
    }
    $this->artisan('module:publish-config')
        ->expectsOutputToContain('Config file published to:')
        ->assertExitCode(0);
    expect(is_file($target))->toBeTrue();
    // Try again without --force, should warn and not overwrite
    file_put_contents($target, '<?php // custom config ?>');
    $this->artisan('module:publish-config')
        ->expectsOutputToContain('Config file already exists:')
        ->assertExitCode(0);
    expect(file_get_contents($target))->toContain('custom config');
    // Try with --force, should overwrite
    $this->artisan('module:publish-config', ['--force' => true])
        ->expectsOutputToContain('Config file published to:')
        ->assertExitCode(0);
    expect(file_get_contents($target))->not()->toContain('custom config');
    unlink($target);
});
