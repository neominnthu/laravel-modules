<?php

declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Modules\Support\ModuleManager;

/**
 * Artisan command to build and write the compiled module manifest cache file.
 */
class CacheModulesCommand extends Command
{
    protected $signature = 'module:cache';
    protected $description = 'Build and write the compiled module manifest cache file';

    /**
     * Execute the cache build command.
     *
     * @return int Exit code (0 = success).
     */
    public function handle(): int
    {
        $manager = $this->laravel->make(\Modules\Support\ModuleManager::class);
        $io = new SymfonyStyle($this->input, $this->output);
        $data = $manager->buildCache();
        $count = count($data);
        $io->success("Module cache built for {$count} enabled module(s).");
        $io->writeln('Cache file: ' . $manager::CACHE_FILE);
        return 0;
    }
}
