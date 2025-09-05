<?php

declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('rejects invalid module name in make:test', function () {
    $this->artisan('module:make:test', ['module' => '../etc/passwd', 'name' => 'ExampleTest'])
        ->expectsOutputToContain('Invalid module name: [../etc/passwd]')
        ->assertExitCode(1);
});

it('rejects invalid module name in make:seeder', function () {
    $this->artisan('module:make:seeder', ['module' => 'Blog..', 'name' => 'ExampleSeeder'])
        ->expectsOutputToContain('Invalid module name: [Blog..]')
        ->assertExitCode(1);
});

it('rejects invalid module name in make:model', function () {
    $this->artisan('module:make:model', ['module' => 'Shop/../../evil', 'name' => 'ExampleModel'])
        ->expectsOutputToContain('Invalid module name: [Shop/../../evil]')
        ->assertExitCode(1);
});

it('rejects invalid module name in make:migration', function () {
    $this->artisan('module:make:migration', ['module' => 'Blog..', 'name' => 'create_table'])
        ->expectsOutputToContain('Invalid module name: [Blog..]')
        ->assertExitCode(1);
});

it('rejects invalid module name in make:middleware', function () {
    $this->artisan('module:make:middleware', ['module' => '../etc/passwd', 'name' => 'ExampleMiddleware'])
        ->expectsOutputToContain('Invalid module name: [../etc/passwd]')
        ->assertExitCode(1);
});

it('rejects invalid module name in make:listener', function () {
    $this->artisan('module:make:listener', ['module' => 'Blog..', 'name' => 'ExampleListener'])
        ->expectsOutputToContain('Invalid module name: [Blog..]')
        ->assertExitCode(1);
});

it('rejects invalid module name in make:factory', function () {
    $this->artisan('module:make:factory', ['module' => 'Shop/../../evil', 'name' => 'ExampleFactory'])
        ->expectsOutputToContain('Invalid module name: [Shop/../../evil]')
        ->assertExitCode(1);
});

it('rejects invalid module name in make:event', function () {
    $this->artisan('module:make:event', ['module' => '../etc/passwd', 'name' => 'ExampleEvent'])
        ->expectsOutputToContain('Invalid module name: [../etc/passwd]')
        ->assertExitCode(1);
});

it('rejects invalid module name in make:controller', function () {
    $this->artisan('module:make:controller', ['module' => 'Blog..', 'name' => 'ExampleController'])
        ->expectsOutputToContain('Invalid module name: [Blog..]')
        ->assertExitCode(1);
});
