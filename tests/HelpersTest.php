<?php
declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('module_enabled helper reflects registry', function () {
    expect(module_enabled('Blog'))->toBeTrue();
});

it('module_path helper returns path', function () {
    $path = module_path('Blog');
    expect($path)->not->toBeNull();
    expect(is_dir($path))->toBeTrue();
});

it('module_call helper returns provider method output', function () {
    expect(module_call('Blog@testPing'))->toBe('ok');
});

it('module_version helper returns version', function () {
    expect(module_version('Blog'))->toBe('1.1.0');
});

it('module_manifest helper returns array data', function () {
    $manifest = module_manifest('Blog');
    expect($manifest)->toBeArray()->and($manifest['name'])->toBe('Blog');
});

it('modules_manager helper returns manager instance', function () {
    $mgr = modules_manager();
    expect($mgr)->not->toBeNull();
});

it('module_enabled false for unknown module', function () {
    expect(module_enabled('Nope123'))->toBeFalse();
});

it('module_path null for unknown module', function () {
    expect(module_path('Nope123'))->toBeNull();
});

it('module_call returns null for disabled or unknown', function () {
    expect(module_call('Nope123@testPing'))->toBeNull();
});

it('modules_enabled returns at least Blog', function () {
    $enabled = modules_enabled();
    expect($enabled)->toBeArray()->and($enabled)->toContain('Blog');
});
