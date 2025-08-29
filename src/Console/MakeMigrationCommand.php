<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Modules\Support\ModuleManager;

/**
 * Artisan command to create a migration file inside a module.
 */
class MakeMigrationCommand extends Command
{
    protected $signature = 'module:make:migration {module} {name : Migration name (snake_case)} {--create= : Table name for create stub} {--table= : Table name for blank stub}';
    protected $description = 'Create a migration file inside a module.';

    /**
     * Execute the migration file creation command.
     *
     * @param Filesystem $files Filesystem instance.
     * @param ModuleManager $manager Module manager instance.
     * @return int Exit code (SUCCESS/FAILURE).
     */
    public function handle(Filesystem $files, ModuleManager $manager): int
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::snake($this->argument('name'));
        $modulePath = $manager->path($module);
        if (! $modulePath) {
            $this->error('Module not found.');
            return self::FAILURE;
        }

        $dir = $modulePath . '/Database/Migrations';
        if (! $files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $filename = $timestamp . '_' . $name . '.php';
        $path = $dir . '/' . $filename;
        if ($files->exists($path)) {
            $this->error('Migration already exists (same timestamp & name).');
            return self::FAILURE;
        }

        $create = $this->option('create');
        $tableOpt = $this->option('table');
        if ($create && $tableOpt) {
            $this->error('Cannot use --create and --table together.');
            return self::FAILURE;
        }

        if ($create) {
            $stub = file_get_contents(__DIR__ . '/../../stubs/Migration.create.stub');
            $content = str_replace('dummy_table', Str::snake($create), $stub);
        } elseif ($tableOpt) {
            $stub = file_get_contents(__DIR__ . '/../../stubs/Migration.blank.stub');
            $content = $stub; // user will customize (we keep generic)
        } else {
            // heuristic: name like create_users_table
            if (preg_match('/create_(.+)_table$/', $name, $m)) {
                $table = $m[1];
                $stub = file_get_contents(__DIR__ . '/../../stubs/Migration.create.stub');
                $content = str_replace('dummy_table', $table, $stub);
            } else {
                $stub = file_get_contents(__DIR__ . '/../../stubs/Migration.blank.stub');
                $content = $stub;
            }
        }

        $files->put($path, $content);
        $this->info("Migration created: {$filename} in module [{$module}].");
        return self::SUCCESS;
    }
}
