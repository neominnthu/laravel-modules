<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;

/**
 * Artisan command to enable a module.
 */
class EnableModuleCommand extends Command
{
    protected $signature = 'module:enable {name}';
    protected $description = 'Enable a module';

    /**
     * Execute the enable module command.
     *
     * @return int Exit code (SUCCESS).
     */
    public function handle(): int
    {
        $manager = $this->laravel->make(ModuleManager::class);
        $name = $this->argument('name');
        $result = $manager->enable($name);
        $manager->buildCache();
        $this->info("Module [$name] enabled.");
        return self::SUCCESS;
    }
}
