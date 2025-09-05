<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Modules\Support\ModuleManager;

/**
 * Generate a controller inside a module.
 */
class MakeControllerCommand extends Command
{
    protected $signature = 'module:make:controller {module : Module name} {name : Controller name (without suffix)}';
    protected $description = 'Create a controller within the specified module.';

    public function handle(Filesystem $files, ModuleManager $manager): int
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name')) . 'Controller';
        if (!ModuleManager::validateModuleName($module)) {
            $this->error("Invalid module name: [{$module}]");
            return self::FAILURE;
        }
        $modulePath = $manager->path($module);
        if (! $modulePath) {
            $this->error('Module not found.');
            return self::FAILURE;
        }
        $dir = $modulePath . '/Http/Controllers';
        if (! $files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0755, true);
        }
        $target = $dir . '/' . $name . '.php';
        if ($files->exists($target)) {
            $this->error('Controller already exists.');
            return self::FAILURE;
        }
        $stub = file_get_contents(__DIR__ . '/../../stubs/Controller.stub');
        $replaced = str_replace(['DummyNameController','DummyName'], [$name, $module], $stub);
        $files->put($target, $replaced);
        $this->info("Controller [$name] created in module [$module].");
        return self::SUCCESS;
    }
}
