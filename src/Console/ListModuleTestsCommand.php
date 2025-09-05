<?php

declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListModuleTestsCommand extends Command
{
    protected $signature = 'module:test:list {module : Module name}';
    protected $description = 'List all Pest test files in a module.';

    public function handle(): int
    {
        $manager = $this->laravel->make(ModuleManager::class);
        $io = new SymfonyStyle($this->input, $this->output);
        $module = $this->argument('module');
        $path = $manager->path($module);
        if (! $path) {
            $io->error('Module not found.');
            return 1;
        }
        $testDir = $path . DIRECTORY_SEPARATOR . 'Tests';
        if (! is_dir($testDir)) {
            $io->warning('No Tests directory found in module.');
            return 0;
        }
        $files = glob($testDir . '/*.php');
        if (! $files) {
            $io->info('No test files found.');
            return 0;
        }
        $io->table(['Test File'], array_map(fn($f) => [basename($f)], $files));
        return 0;
    }
}
