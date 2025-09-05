<?php
declare(strict_types=1);

namespace Modules\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Modules\ModulesServiceProvider;

abstract class TestCase extends BaseTestCase
{
// removed extra brace
    protected function logManifestContents(): void
    {
        $base = $this->app->basePath();
        $destModules = $base . DIRECTORY_SEPARATOR . 'Modules';
        foreach (["Blog", "Shop"] as $mod) {
            $manifestPath = $destModules . DIRECTORY_SEPARATOR . $mod . DIRECTORY_SEPARATOR . 'module.json';
            if (file_exists($manifestPath)) {
                $contents = file_get_contents($manifestPath);
                fwrite(STDERR, "[DEBUG] $mod manifest: $contents\n");
            } else {
                fwrite(STDERR, "[DEBUG] $mod manifest missing: $manifestPath\n");
            }
        }
    }

    protected function getPackageProviders($app)
    {
        return [ModulesServiceProvider::class];
    }


    protected function setUp(): void
    {
        parent::setUp();
        $this->restoreModuleManifests();
        $this->logManifestContents();
    }

    protected function tearDown(): void
    {
        $this->restoreModuleManifests();
        parent::tearDown();
    }


    protected function restoreModuleManifests(): void
    {
        if (!isset($this->app)) return;
        $paths = [];
        // Workspace Modules path
        $base = $this->app->basePath();
        $paths[] = $base . DIRECTORY_SEPARATOR . 'Modules';
        // Testbench sandbox Modules path
        $vendorTestbench = $base . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'orchestra' . DIRECTORY_SEPARATOR . 'testbench-core' . DIRECTORY_SEPARATOR . 'laravel' . DIRECTORY_SEPARATOR . 'Modules';
        $paths[] = $vendorTestbench;
        foreach ($paths as $destModules) {
            foreach ([
                'Blog' => '{"name":"Blog","version":"1.2.0","provider":"Modules\\Blog\\Providers\\BlogServiceProvider"}',
                'Shop' => '{"name":"Shop","version":"1.1.0","provider":"Modules\\Shop\\Providers\\ShopServiceProvider","dependencies":["Blog"],"dependency_versions":{"Blog":">=1.1.0"}}'
            ] as $mod => $manifest) {
                $modDir = $destModules . DIRECTORY_SEPARATOR . $mod;
                $manifestPath = $modDir . DIRECTORY_SEPARATOR . 'module.json';
                if (!is_dir($modDir)) {
                    fwrite(STDERR, "[RESTORE] Directory missing: $modDir\n");
                    continue;
                }
                // Write with Unix line endings and no BOM
                $cleanManifest = str_replace(["\r\n", "\r"], "\n", $manifest);
                $result = @file_put_contents($manifestPath, $cleanManifest);
                if ($result === false) {
                    fwrite(STDERR, "[RESTORE] Failed to write manifest: $manifestPath\n");
                } else {
                    fwrite(STDERR, "[RESTORE] Manifest written: $manifestPath\n");
                }
            }
        }
    }

    protected function defineConsoleKernel($app)
    {
        $commands = [
            new \Modules\Console\CacheModulesCommand(),
            new \Modules\Console\EnableModuleCommand(),
            new \Modules\Console\DisableModuleCommand(),
            new \Modules\Console\ListModulesCommand(),
            new \Modules\Console\ValidateModulesCommand(),
            new \Modules\Console\SyncManifestCommand(),
            new \Modules\Console\MakeTestCommand(),
            new \Modules\Console\MakeSeederCommand(),
            new \Modules\Console\MakeModuleCommand(),
            new \Modules\Console\MakeModelCommand(),
            new \Modules\Console\MakeMigrationCommand(),
            new \Modules\Console\MakeMiddlewareCommand(),
            new \Modules\Console\MakeListenerCommand(),
            new \Modules\Console\MakeFactoryCommand(),
            new \Modules\Console\MakeEventCommand(),
            new \Modules\Console\MakeControllerCommand(),
            new \Modules\Console\ListModuleTestsCommand(),
            new \Modules\Console\GraphModulesCommand(),
            new \Modules\Console\CoverageModulesCommand(),
            new \Modules\Console\ClearModulesCacheCommand(),
            new \Modules\Console\CacheStatusCommand(),
        ];
        foreach ($commands as $command) {

            $app->make('Illuminate\Contracts\Console\Kernel')->registerCommand($command);
        }
    }

    protected function getPackageAliases($app)
    {
        return [
            'Module' => \Modules\Facades\Module::class,
        ];
    }


    protected function recursiveCopy($src, $dest)
    {
        $dir = opendir($src);
        @mkdir($dest, 0755, true);
        while (false !== ($file = readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
                if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                    $this->recursiveCopy($src . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file);
                } else {
                    copy($src . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
        closedir($dir);
    }
}
