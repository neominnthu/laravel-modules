<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Modules\Support\ModuleManager;

class MakeEventCommand extends Command
{
    protected $signature = 'module:make:event {module} {name}';
    protected $description = 'Create an event class inside a module.';

    public function handle(Filesystem $files, ModuleManager $manager): int
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name'));
        $path = $manager->path($module);
        if (! $path) {
            $this->error('Module not found.');
            return self::FAILURE;
        }
        $dir = $path . '/Events';
        if (! $files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0755, true);
        }
        $file = $dir . '/' . $name . '.php';
        if ($files->exists($file)) {
            $this->error('Event already exists.');
            return self::FAILURE;
        }
        $stub = file_get_contents(__DIR__ . '/../../stubs/Event.stub');
        $content = str_replace(['DummyEvent','DummyName'], [$name, $module], $stub);
        $files->put($file, $content);
        $this->info("Event [$name] created.");
        return self::SUCCESS;
    }
}
