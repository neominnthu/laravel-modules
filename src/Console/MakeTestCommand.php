<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Modules\Support\ModuleManager;

class MakeTestCommand extends Command
{
    protected $signature = 'module:make:test {module} {name : Test file name (without .php)}';
    protected $description = 'Create a Pest test file within a module tests directory.';

    public function handle(Filesystem $files, ModuleManager $manager): int
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name'));
        $modulePath = $manager->path($module);
        if (! $modulePath) {
            $this->error('Module not found.');
            return self::FAILURE;
        }
        $dir = $modulePath . '/Tests';
        if (! $files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0755, true);
        }
        $file = $dir . '/' . $name . '.php';
        if ($files->exists($file)) {
            $this->error('Test file already exists.');
            return self::FAILURE;
        }
        $stub = file_get_contents(__DIR__ . '/../../stubs/Test.stub');
        $content = str_replace(['DummyName'], [$module], $stub);
        $files->put($file, $content);
        $this->info("Test [$name] created in module [$module].");
        return self::SUCCESS;
    }
}
