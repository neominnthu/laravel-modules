<?php

declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('rejects invalid module name in version show', function () {
    $this->artisan('module:version:show', ['module' => '../etc/passwd'])
        ->expectsOutputToContain('Invalid module name: [../etc/passwd]')
        ->assertExitCode(1);
});

it('rejects invalid module name in version bump', function () {
    $this->artisan('module:version:bump', ['module' => 'Blog/../../evil', 'type' => 'patch'])
        ->expectsOutputToContain('Invalid module name: [Blog/../../evil]')
        ->assertExitCode(1);
});

it('rejects invalid module name in list tests', function () {
    $this->artisan('module:test:list', ['module' => 'Shop..'])
        ->expectsOutputToContain('Invalid module name: [Shop..]')
        ->assertExitCode(1);
});
