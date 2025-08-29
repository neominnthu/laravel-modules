<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Modules\Support\ModuleManager;

class MakeMiddlewareCommand extends Command
{
    protected $signature = 'module:make:middleware {module} {name}';
    protected $description = 'Create a middleware class inside a module.';

    public function handle(Filesystem $files, ModuleManager $manager): int
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name'));
        if (! Str::endsWith($name, 'Middleware')) {
            $name .= 'Middleware';
        }
        $path = $manager->path($module);
        if (! $path) {
            $this->error('Module not found.');
            return self::FAILURE;
        }
        $dir = $path . '/Http/Middleware';
        if (! $files->isDirectory($dir)) {
            $files->makeDirectory($dir, 0755, true);
        }
        $file = $dir . '/' . $name . '.php';
        if ($files->exists($file)) {
            $this->error('Middleware already exists.');
            return self::FAILURE;
        }
        $stub = file_get_contents(__DIR__ . '/../../stubs/Middleware.stub');
        $content = str_replace(['DummyMiddleware','DummyName'], [$name, $module], $stub);
        $files->put($file, $content);
        $this->info("Middleware [$name] created.");
        return self::SUCCESS;
    }
}
