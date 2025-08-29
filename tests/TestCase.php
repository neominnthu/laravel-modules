<?php
declare(strict_types=1);

namespace Modules\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Modules\ModulesServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [ModulesServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // Ensure a temporary modules.json exists for tests.
        $path = base_path('modules.json');
        if (! file_exists($path)) {
            file_put_contents($path, json_encode(['Blog' => true], JSON_PRETTY_PRINT));
        }
    }
}
