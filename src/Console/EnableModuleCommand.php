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
     * @param ModuleManager $manager Module manager instance.
     * @return int Exit code (SUCCESS).
     */
    public function handle(ModuleManager $manager): int
    {
        $name = $this->argument('name');
        $manager->enable($name);
        $manager->buildCache();
        $this->info("Module [$name] enabled.");
        return self::SUCCESS;
    }
}
