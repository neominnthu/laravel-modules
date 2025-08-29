<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Modules\Support\ModuleManager;

/**
 * Artisan command to create an Eloquent model inside a module (optionally with migration).
 */
class MakeModelCommand extends Command
{
    protected $signature = 'module:make:model {module} {name} {--m|migration : Create a migration file}';
    protected $description = 'Create an Eloquent model inside a module (optionally with migration).';

    /**
     * Execute the model file creation command.
     *
     * @param Filesystem $files Filesystem instance.
     * @param ModuleManager $manager Module manager instance.
     * @return int Exit code (SUCCESS/FAILURE).
     */
    public function handle(Filesystem $files, ModuleManager $manager): int
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name'));
        $modulePath = $manager->path($module);
        if (! $modulePath) {
            $this->error('Module not found.');
            return self::FAILURE;
        }
        $modelsDir = $modulePath . '/Models';
        if (! $files->isDirectory($modelsDir)) {
            $files->makeDirectory($modelsDir, 0755, true);
        }
        $target = $modelsDir . '/' . $name . '.php';
        if ($files->exists($target)) {
            $this->error('Model already exists.');
            return self::FAILURE;
        }
        $stub = file_get_contents(__DIR__ . '/../../stubs/Model.stub');
        $content = str_replace(['DummyName'], [$name], $stub);
        $files->put($target, $content);

        if ($this->option('migration')) {
            $migDir = $modulePath . '/Database/Migrations';
            if (! $files->isDirectory($migDir)) {
                $files->makeDirectory($migDir, 0755, true);
            }
            $timestamp = date('Y_m_d_His');
            $table = Str::snake(Str::pluralStudly($name));
            $migrationFile = $migDir . '/' . $timestamp . '_create_' . $table . '_table.php';
            $migStub = file_get_contents(__DIR__ . '/../../stubs/Migration.create.stub');
            $migContent = str_replace(['dummy_table'], [$table], $migStub);
            $files->put($migrationFile, $migContent);
            $this->info("Migration created: " . basename($migrationFile));
        }

        $this->info("Model [$name] created in module [$module].");
        return self::SUCCESS;
    }
}
