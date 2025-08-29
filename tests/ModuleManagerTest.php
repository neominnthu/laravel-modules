<?php
declare(strict_types=1);

use Modules\Facades\Module;
use Modules\Tests\TestCase;

uses(TestCase::class);

it('lists blog module as enabled', function () {
    expect(Module::enabled('Blog'))->toBeTrue();
});

it('can call provider method via facade', function () {
    expect(Module::call('Blog@testPing'))->toBe('ok');
});

it('skips disabled dependency module (Shop) when Blog enabled but Shop disabled', function () {
    // Shop depends on Blog; Shop disabled so call should be null
    expect(Module::enabled('Shop'))->toBeFalse();
    expect(Module::call('Shop@testPing'))->toBeNull();
});

it('can enable dependent module (Shop) once toggled in registry', function () {
    // Enable Shop via manager then rebuild cache
    Module::enable('Shop');
    expect(Module::enabled('Shop'))->toBeTrue();
    // Call should now resolve (we need to ensure cache sees it)
    // Build cache explicitly through manager
    $manager = app('modules.manager');
    $manager->buildCache();
    expect(Module::call('Shop@testPing'))->toBe('shop-ok');
});


it('includes module with unmet dependency when strict_dependencies disabled', function () {
    // Disable Shop and Blog to create missing dependency scenario for Shop
    Module::disable('Shop');
    expect(Module::enabled('Shop'))->toBeFalse();
    // Temporarily write modified config overriding strict flag (simulate)
    config(['modules.strict_dependencies' => false]);
    // Enable Shop but disable Blog dependency, then build cache
    Module::enable('Shop');
    Module::disable('Blog');
    $manager = app('modules.manager');
    $cache = $manager->buildCache();
    // Shop should appear despite Blog disabled because strict deps = false
    expect($cache)->toHaveKey('Shop');
    // Reset for other tests (re-enable Blog for isolation) and rebuild
    Module::enable('Blog');
    $manager->buildCache();
});
