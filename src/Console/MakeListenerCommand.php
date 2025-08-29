<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Modules\Support\ModuleManager;

class MakeListenerCommand extends Command
{
    protected $signature = 'module:make:listener {module} {name} {--event= : Event class name to handle}';
    protected $description = 'Create a listener class inside a module.';

    public function handle(Filesystem $files, ModuleManager $manager): int
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name'));
        $path = $manager->path($module);
        if (! $path) {
            $this->error('Module not found.');
            return self::FAILURE;
        }
        $dir = $path . '/Listeners';
        if (! $files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0755, true);
        }
        $file = $dir . '/' . $name . '.php';
        if ($files->exists($file)) {
            $this->error('Listener already exists.');
            return self::FAILURE;
        }
        $stub = file_get_contents(__DIR__ . '/../../stubs/Listener.stub');
        $content = str_replace(['DummyListener','DummyName'], [$name, $module], $stub);
        if ($event = $this->option('event')) {
            $content = str_replace('DummyEvent', Str::studly($event), $content);
        }
        $files->put($file, $content);
        $this->info("Listener [$name] created.");
        return self::SUCCESS;
    }
}
