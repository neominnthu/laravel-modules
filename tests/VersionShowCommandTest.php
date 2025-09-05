<?php

declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('shows all module versions in table', function () {
    $this->artisan('module:version:show')
        ->expectsOutputToContain('Blog')
        ->expectsOutputToContain('Shop')
        ->assertExitCode(0);
});

it('shows single module version in json', function () {
    $this->artisan('module:version:show', ['module' => 'Blog', '--json' => true])
        ->assertExitCode(0);
});
