<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Modules\Support\ModuleManager;

/**
 * Artisan command to create a database seeder inside a module.
 */
class MakeSeederCommand extends Command
{
    protected $signature = 'module:make:seeder {module} {name}';
    protected $description = 'Create a database seeder inside a module.';

    /**
     * Execute the seeder file creation command.
     *
     * @param Filesystem $files Filesystem instance.
     * @param ModuleManager $manager Module manager instance.
     * @return int Exit code (SUCCESS/FAILURE).
     */
    public function handle(Filesystem $files, ModuleManager $manager): int
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name'));
        if (!ModuleManager::validateModuleName($module)) {
            $this->error("Invalid module name: [{$module}]");
            return self::FAILURE;
        }
        $modulePath = $manager->path($module);
        if (! $modulePath) {
            $this->error('Module not found.');
            return self::FAILURE;
        }
        $dir = $modulePath . '/Database/Seeders';
        if (! $files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0755, true);
        }
        $class = $name . 'Seeder';
        $file = $dir . '/' . $class . '.php';
        if ($files->exists($file)) {
            $this->error('Seeder already exists.');
            return self::FAILURE;
        }
        $stub = file_get_contents(__DIR__ . '/../../stubs/Seeder.stub');
        $content = str_replace(['DummyName','DummyClass'], [$module, $name], $stub);
        $files->put($file, $content);
        $this->info("Seeder [$class] created in module [$module].");
        return self::SUCCESS;
    }
}
