<?php
declare(strict_types=1);

use Modules\Tests\TestCase;

uses(TestCase::class);

it('captures boot event via listener in eager mode', function () {
    expect(\Modules\Blog\Listeners\RecordPing::$records)->toContain('boot');
});

it('registers blogsample middleware alias', function () {
    $router = app('router');
    $aliases = method_exists($router, 'getMiddleware') ? $router->getMiddleware() : [];
    expect($aliases)->toHaveKey('blogsample');
    expect($aliases['blogsample'])->toBe(\Modules\Blog\Http\Middleware\BlogSampleMiddleware::class);
});
