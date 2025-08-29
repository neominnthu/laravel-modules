<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;

class ListModulesCommand extends Command
{
    protected $signature = 'module:list {--verbose : Show dependencies when enabled}';
    protected $description = 'List modules and status';

    public function handle(ModuleManager $manager): int
    {
        $rows = [];
        $headers = ['Module','Status','Version'];
        $showVerbose = (bool) $this->option('verbose');
        if ($showVerbose) {
            $headers[] = 'Dependencies';
        }
        $all = $manager->discover();
        foreach ($all as $name => $path) {
            $status = $manager->enabled($name);
            $version = $manager->version($name) ?? 'n/a';
            $row = [$name, $status ? 'ENABLED' : 'DISABLED', $version];
            if ($showVerbose) {
                $deps = [];
                try { $deps = (new \Modules\Support\ModuleManifest($path))->dependencies(); } catch (\Throwable) {}
                $row[] = implode(',', $deps) ?: '-';
            }
            $rows[] = $row;
        }
        $this->table($headers, $rows);
        return self::SUCCESS;
    }
}
