<?php
declare(strict_types=1);

use Modules\Tests\TestCase;
use Modules\Facades\Module;

uses(TestCase::class);

it('bumps module version patch', function () {
    // Ensure baseline version
    $path = module_path('Blog') . DIRECTORY_SEPARATOR . 'module.json';
    $data = json_decode(file_get_contents($path), true);
    $original = $data['version'];

    // Run bump
    $this->artisan('module:version:bump', ['module' => 'Blog', 'type' => 'patch'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Blog version bumped:');

    $updated = json_decode(file_get_contents($path), true)['version'];
    expect($updated)->not()->toBe($original);

    // Revert to original for isolation
    $this->artisan('module:version:bump', ['module' => 'Blog', 'type' => $original])
        ->assertExitCode(0);
});

it('sets explicit version', function () {
    $target = '9.9.9';
    $path = module_path('Blog') . DIRECTORY_SEPARATOR . 'module.json';
    $this->artisan('module:version:bump', ['module' => 'Blog', 'type' => $target])
        ->assertExitCode(0);
    $updated = json_decode(file_get_contents($path), true)['version'];
    expect($updated)->toBe($target);
});
