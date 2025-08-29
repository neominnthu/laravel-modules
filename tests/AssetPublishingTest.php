<?php
declare(strict_types=1);

namespace Modules\Tests;

use Illuminate\Support\ServiceProvider;
use Modules\Blog\Providers\BlogServiceProvider;
use Modules\Tests\TestCase;

class AssetPublishingTest extends TestCase
{
    public function test_module_asset_publishing_tags_are_registered()
    {
    $provider = new BlogServiceProvider($this->app);
    $provider->boot();
    $tags = ServiceProvider::$publishGroups ?? ServiceProvider::$publishes;
    self::assertArrayHasKey('module-blog', $tags, 'module-blog publish tag should be registered');
    self::assertArrayHasKey('modules-resources', $tags, 'modules-resources publish tag should be registered');
    }
}
