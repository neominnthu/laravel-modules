<?php

declare(strict_types=1);

use Modules\Facades\Module;
use Modules\Tests\TestCase;

uses(TestCase::class);

it('can hot reload (unregister) a module provider at runtime', function () {
    $manager = app('modules.manager');
    // Ensure Blog is enabled and provider is registered
    expect(Module::enabled('Blog'))->toBeTrue();
    expect($manager->isProviderRegistered('Modules\\Blog\\Providers\\BlogServiceProvider'))->toBeTrue();

    // Unregister provider
    $manager->unregisterProvider('Blog');
    expect($manager->isProviderRegistered('Modules\\Blog\\Providers\\BlogServiceProvider'))->toBeFalse();

    // Re-enable to restore state
    Module::enable('Blog');
    $manager->buildCache();
    expect($manager->isProviderRegistered('Modules\\Blog\\Providers\\BlogServiceProvider'))->toBeTrue();
});
