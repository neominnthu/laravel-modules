<?php
declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('shows sync plan in dry run', function () {
    $this->artisan('module:sync', ['--dry-run' => true])
        ->expectsOutputToContain('Module Sync Plan')
        ->expectsOutputToContain('Dry run: yes')
        ->assertExitCode(0);
});

it('outputs json plan', function () {
    $this->artisan('module:sync', ['--json' => true, '--dry-run' => true])
        ->assertExitCode(0);
});
