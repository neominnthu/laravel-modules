<?php

declare(strict_types=1);

namespace Modules\Shop\Providers;

use Illuminate\Support\ServiceProvider;

class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'shop');
    }

    public function boot(): void
    {
        // For tests only
    }

    /**
     * Simple method to facilitate Module::call tests for the Shop module.
     */
    public function testPing(): string
    {
        return 'shop-ok';
    }
}
