<?php

declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Artisan command to show module cache status and details.
 */
class CacheStatusCommand extends Command
{
    protected $signature = 'module:cache:status';
    protected $description = 'Show module cache status and details.';

    /**
     * Execute the cache status command.
     *
     * @return int Exit code (0 = success).
     */
    public function handle(): int
    {
        $manager = $this->laravel->make(ModuleManager::class);
        $io = new SymfonyStyle($this->input, $this->output);
        $cacheFile = base_path(ModuleManager::CACHE_FILE);
        if (!file_exists($cacheFile)) {
            $io->warning('Module cache file not found.');
            return 0;
        }
        $modules = include $cacheFile;
        $count = is_array($modules) ? count($modules) : 0;
        $size = filesize($cacheFile);
        $updated = date('Y-m-d H:i:s', filemtime($cacheFile));
        $io->section('Module Cache Status');
        $io->table(['Property','Value'], [
            ['Cache File', $cacheFile],
            ['Modules Cached', $count],
            ['Last Updated', $updated],
            ['File Size', $size . ' bytes'],
        ]);
        if ($count > 0) {
            $io->section('Cached Modules');
            $io->listing(array_keys($modules));
        }
        return 0;
    }
}
