<?php

declare(strict_types=1);

namespace Modules\Blog\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Blog\Events\Pinged;
use function event;
use function config_path;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'blog');
    }

    public function boot(): void
    {
        $base = __DIR__ . '/..';
        $this->loadRoutesFrom($base . '/Routes/web.php');
        if (file_exists($base . '/Routes/api.php')) {
            $this->loadRoutesFrom($base . '/Routes/api.php');
        }
        if (file_exists($base . '/Routes/console.php')) {
            $this->loadRoutesFrom($base . '/Routes/console.php');
        }
        $this->loadViewsFrom($base . '/Resources/views', 'blog');
        $this->loadMigrationsFrom($base . '/Database/Migrations');

        // Fire a simple event for tests to assert listener auto-registration in eager mode.
        event(new Pinged('boot'));
        // Explicitly register middleware and listener for testbench isolation
        $router = $this->app['router'];
        $router->aliasMiddleware('blogsample', \Modules\Blog\Http\Middleware\BlogSampleMiddleware::class);
        $this->app['events']->listen(
            \Modules\Blog\Events\Pinged::class,
            \Modules\Blog\Listeners\RecordPing::class
        );
        // Record provider in manager if available (idempotent)
        if ($this->app->bound('modules.manager')) {
            $manager = $this->app->make('modules.manager');
            if (method_exists($manager, 'markProviderRegistered')) {
                $manager->markProviderRegistered(static::class);
            }
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $base . '/Config/config.php' => config_path('blog.php'),
                $base . '/Resources/views' => resource_path('views/vendor/blog'),
            ], 'module-blog');
            $this->publishes([
                $base . '/Config/config.php' => config_path('blog.php'),
                $base . '/Resources/views' => resource_path('views/vendor/blog'),
            ], 'modules-resources');
        }
    }

    /**
     * Simple method to facilitate Module::call tests.
     */
    public function testPing(): string
    {
    // Also dispatch event path used in lazy tests
    event(new Pinged('call'));
        return 'ok';
    }
}
