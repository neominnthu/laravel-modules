<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;

class DisableModuleCommand extends Command
{
    protected $signature = 'module:disable {name}';
    protected $description = 'Disable a module';

    public function handle(ModuleManager $manager): int
    {
        $name = $this->argument('name');
        $manager->disable($name);
        $manager->buildCache();
        $this->info("Module [$name] disabled.");
        return self::SUCCESS;
    }
}
