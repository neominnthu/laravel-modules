<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Modules\Support\ModuleManager;

class MakeFactoryCommand extends Command
{
    protected $signature = 'module:make:factory {module} {name} {--model= : Model class (without namespace) to bind}';
    protected $description = 'Create a model factory inside a module.';

    public function handle(Filesystem $files, ModuleManager $manager): int
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name'));
        $modulePath = $manager->path($module);
        if (! $modulePath) {
            $this->error('Module not found.');
            return self::FAILURE;
        }
        $dir = $modulePath . '/Database/Factories';
        if (! $files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0755, true);
        }
        $class = $name . 'Factory';
        $file = $dir . '/' . $class . '.php';
        if ($files->exists($file)) {
            $this->error('Factory already exists.');
            return self::FAILURE;
        }
        $stub = file_get_contents(__DIR__ . '/../../stubs/Factory.stub');
        $model = $this->option('model') ? Str::studly((string)$this->option('model')) : $name;
        $modelFqn = "Modules\\{$module}\\Models\\{$model}";
        $content = str_replace(['DummyName','DummyClass','DummyModel'], [$module, $name, $modelFqn], $stub);
        $files->put($file, $content);
        $this->info("Factory [$class] created in module [$module] targeting model [$modelFqn].");
        return self::SUCCESS;
    }
}
