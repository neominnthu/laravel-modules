<?php
declare(strict_types=1);

namespace Modules\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Modules\ModulesServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * Determine if tests should run in quiet mode (suppress RESTORE / DEBUG noise).
     */
    protected function isQuiet(): bool
    {
        $val = strtolower((string) getenv('QUIET_MODULE_TESTS'));
        if ($val === '') {
            // Also allow defining a constant for alternative triggering
            if (defined('QUIET_MODULE_TESTS') && constant('QUIET_MODULE_TESTS') === true) {
                return true;
            }
        }
        return in_array($val, ['1','true','yes','on'], true);
    }
// removed extra brace
    protected function logManifestContents(): void
    {
        if ($this->isQuiet()) {
            return; // suppress debug output when quiet mode enabled
        }
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
    if (! $this->isQuiet()) {
            $this->logManifestContents();
        }
    }

    protected function tearDown(): void
    {
        $this->restoreModuleManifests();
        parent::tearDown();
    }


    protected function restoreModuleManifests(): void
    {
        if (!isset($this->app)) return;
        static $inProgress = false;
        if ($inProgress) return; // guard against recursive calls
        $inProgress = true;
    $quiet = $this->isQuiet();
        $base = $this->app->basePath();
        $paths = [];
        $paths[] = $base . DIRECTORY_SEPARATOR . 'Modules';
        // Only add vendor sandbox path if current base is not already inside it
        if (! $this->isQuiet() && ! str_contains($base, 'vendor' . DIRECTORY_SEPARATOR . 'orchestra' . DIRECTORY_SEPARATOR . 'testbench-core' . DIRECTORY_SEPARATOR . 'laravel')) {
            $paths[] = $base . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'orchestra' . DIRECTORY_SEPARATOR . 'testbench-core' . DIRECTORY_SEPARATOR . 'laravel' . DIRECTORY_SEPARATOR . 'Modules';
        }

        $definitions = [
            'Blog' => [
                'name' => 'Blog',
                'version' => '1.1.0',
                'provider' => 'Modules\\Blog\\Providers\\BlogServiceProvider',
            ],
            'Shop' => [
                'name' => 'Shop',
                'version' => '1.1.0',
                'provider' => 'Modules\\Shop\\Providers\\ShopServiceProvider',
                'dependencies' => ['Blog'],
                'dependency_versions' => ['Blog' => '>=1.1.0'],
            ],
        ];

        foreach (array_unique($paths) as $destModules) {
            foreach ($definitions as $mod => $data) {
                $modDir = $destModules . DIRECTORY_SEPARATOR . $mod;
                $manifestPath = $modDir . DIRECTORY_SEPARATOR . 'module.json';
                if (!is_dir($modDir)) {
                    if (! $quiet) fwrite(STDERR, "[RESTORE] Directory missing: $modDir\n");
                    continue;
                }
                $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if ($json === false) {
                    if (! $quiet) fwrite(STDERR, "[RESTORE] Failed to encode manifest for $mod\n");
                    continue;
                }
                $json = str_replace(["\r\n", "\r"], "\n", $json);
                $temp = $manifestPath . '.tmp';
                $bytes = @file_put_contents($temp, $json, LOCK_EX);
                if ($bytes === false) {
                    if (! $quiet) fwrite(STDERR, "[RESTORE] Failed to write manifest: $manifestPath\n");
                    continue;
                }
                @rename($temp, $manifestPath);
                if (! $quiet) fwrite(STDERR, "[RESTORE] Manifest written: $manifestPath ($bytes bytes)\n");
            }
        }
        // Also restore the modules enable/disable registry (modules.json) to ensure isolation across tests
        $registryPath = $base . DIRECTORY_SEPARATOR . 'modules.json';
        $registryData = json_encode(['Blog' => true, 'Shop' => false]);
        if ($registryData !== false) {
            $tmpReg = $registryPath . '.tmp';
            if (@file_put_contents($tmpReg, $registryData, LOCK_EX) !== false) {
                @rename($tmpReg, $registryPath);
                if (! $quiet) fwrite(STDERR, "[RESTORE] Registry written: $registryPath\n");
                // Refresh manager singleton registry if available
                try {
                    if (isset($this->app) && $this->app->bound('modules.manager')) {
                        $manager = $this->app->make('modules.manager');
                        if (method_exists($manager, 'reloadRegistry')) {
                            $manager->reloadRegistry();
                        }
                    }
                } catch (\Throwable $e) {
                    if (! $quiet) fwrite(STDERR, "[RESTORE] Registry reload failed: {$e->getMessage()}\n");
                }
            } else {
                    if (! $quiet) fwrite(STDERR, "[RESTORE] Failed writing registry: $registryPath\n");
            }
        }
        $inProgress = false;
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
