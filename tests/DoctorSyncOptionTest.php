<?php

declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('can run doctor with sync (no changes expected)', function () {
    $this->artisan('module:cache')->assertExitCode(0);
    $this->artisan('module:doctor', ['--sync' => true])
        ->assertExitCode(0);
});

