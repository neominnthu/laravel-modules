<?php
declare(strict_types=1);

use Modules\Tests\TestCase;
use Modules\Facades\Module;

uses(TestCase::class);

it('auto-registers listener & middleware in lazy mode on first call', function () {
    config(['modules.lazy' => true]);
    \Modules\Blog\Listeners\RecordPing::$records = [];
    expect(\Modules\Blog\Listeners\RecordPing::$records)->toBeEmpty();
    expect(Module::call('Blog@testPing'))->toBe('ok');
    expect(\Modules\Blog\Listeners\RecordPing::$records)->toContain('call');
        // Middleware alias should now exist
        $router = app('router');
        $aliases = method_exists($router, 'getMiddleware') ? $router->getMiddleware() : [];
        expect($aliases)->toHaveKey('blogsample');
});
