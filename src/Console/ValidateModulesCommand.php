<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Modules\Support\ModuleManifest;

class ValidateModulesCommand extends Command
{
    protected $signature = 'module:validate {--no-strict : Ignore strict dependency filtering}';
    protected $description = 'Validate module manifests & dependencies';

    public function handle(ModuleManager $manager): int
    {
        $issues = [];
        $all = $manager->discover();
        foreach ($all as $name => $path) {
            if (! $manager->enabled($name)) {
                continue;
            }
            try {
                $manifest = new ModuleManifest($path);
                foreach ($manifest->dependencies() as $dep) {
                    if (! isset($all[$dep])) {
                        $issues[] = [$name, 'missing dependency', $dep];
                    } elseif (! $manager->enabled($dep)) {
                        $issues[] = [$name, 'dependency disabled', $dep];
                    }
                }
            } catch (\Throwable $e) {
                $issues[] = [$name, 'invalid manifest', $e->getMessage()];
            }
        }
        if ($issues) {
            $this->table(['Module','Issue','Detail'], $issues);
            $this->warn(count($issues) . ' issue(s) found.');
            return self::FAILURE;
        }
        $this->info('All module dependencies satisfied.');
        return self::SUCCESS;
    }
}
