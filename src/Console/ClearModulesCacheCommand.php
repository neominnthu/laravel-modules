<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;

class ClearModulesCacheCommand extends Command
{
    protected $signature = 'module:cache:clear';
    protected $description = 'Clear module cache file and rebuild';

    public function handle(): int
    {
        $manager = $this->laravel->make(ModuleManager::class);
        $manager->forgetCache();
        $manager->buildCache();
        $this->info('Module cache rebuilt.');
        return self::SUCCESS;
    }
}
