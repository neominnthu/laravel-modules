<?php
declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('shows status summary in table form', function () {
    $this->artisan('module:status')
        ->expectsOutputToContain('Module Status')
        ->expectsOutputToContain('Enabled:') // part of summary line
        ->assertExitCode(0);
});

it('outputs JSON summary', function () {
    $pending = $this->artisan('module:status', ['--json' => true])
        ->assertExitCode(0);
});
