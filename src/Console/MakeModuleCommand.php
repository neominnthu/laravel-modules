<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Modules\Support\ModuleManager;

/**
 * Artisan command to scaffold a new module with standard structure.
 */
class MakeModuleCommand extends Command
{
    protected $signature = 'module:make {name : The module name} {--api : Include api route file}';
    protected $description = 'Scaffold a new module with standard structure';

    /**
     * Execute the module scaffolding command.
     *
     * @param Filesystem $files Filesystem instance.
     * @return int Exit code (SUCCESS/FAILURE).
     */
    public function handle(Filesystem $files): int
    {
        $manager = $this->laravel->make(ModuleManager::class);
        $name = Str::studly($this->argument('name'));
        $base = $manager->modulesRoot() . DIRECTORY_SEPARATOR . $name;
        if ($files->exists($base)) {
            $this->error('Module already exists.');
            return self::FAILURE;
        }
        // directories
        $dirs = [
            'Config','Database/Migrations','Database/Seeders','Database/Factories','Http/Controllers','Http/Middleware',
            'Models','Providers','Resources/views','Routes'
        ];
        foreach ($dirs as $dir) {
            $files->makeDirectory($base . '/' . $dir, 0755, true, true);
        }

        // module.json
        $moduleJson = [
            'name' => $name,
            'version' => '1.0.0',
            'provider' => "Modules\\\\{$name}\\\\Providers\\\\{$name}ServiceProvider",
        ];
        $files->put($base . '/module.json', json_encode($moduleJson, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        // provider
        $providerStub = file_get_contents(__DIR__ . '/../../stubs/ServiceProvider.stub');
        $providerCode = str_replace(['DummyName','dummyname'], [$name, Str::snake($name)], $providerStub);
        $files->put("$base/Providers/{$name}ServiceProvider.php", $providerCode);

        // routes
        $webStub = file_get_contents(__DIR__ . '/../../stubs/Routes/web.stub');
        $files->put("$base/Routes/web.php", $webStub);
        if ($this->option('api')) {
            $apiStub = file_get_contents(__DIR__ . '/../../stubs/Routes/api.stub');
            $files->put("$base/Routes/api.php", $apiStub);
        }
        $consoleStub = file_get_contents(__DIR__ . '/../../stubs/Routes/console.stub');
        $files->put("$base/Routes/console.php", $consoleStub);

        // config
        $configStub = file_get_contents(__DIR__ . '/../../stubs/Config/config.stub');
        $files->put("$base/Config/config.php", $configStub);

        // view
        $viewStub = file_get_contents(__DIR__ . '/../../stubs/view.stub');
        $files->put("$base/Resources/views/index.blade.php", $viewStub);

        $this->info("Module [$name] created.");
        return self::SUCCESS;
    }
}
