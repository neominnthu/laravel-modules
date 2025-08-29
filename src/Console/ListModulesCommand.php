<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Artisan command to list modules and their status, version, and dependencies.
 */
class ListModulesCommand extends Command
{
    protected $signature = 'module:list {--verbose : Show dependencies when enabled} {--json : Output as JSON}';
    protected $description = 'List modules and status';

    /**
     * Execute the module listing command.
     *
     * @param ModuleManager $manager Module manager instance.
     * @return int Exit code (0 = success).
     */
    public function handle(ModuleManager $manager): int
    {
        $io = new SymfonyStyle($this->input, $this->output);
        $showVerbose = (bool) $this->option('verbose');
        $asJson = (bool) $this->option('json');
        $all = $manager->discover();
        $modules = [];
        foreach ($all as $name => $path) {
            $status = $manager->enabled($name);
            $version = $manager->version($name) ?? 'n/a';
            $deps = [];
            try { $deps = (new \Modules\Support\ModuleManifest($path))->dependencies(); } catch (\Throwable) {}
            $modules[] = [
                'name' => $name,
                'status' => $status ? 'ENABLED' : 'DISABLED',
                'version' => $version,
                'dependencies' => $deps,
            ];
        }
        if ($asJson) {
            $io->writeln(json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }
        $headers = ['Module','Status','Version'];
        if ($showVerbose) {
            $headers[] = 'Dependencies';
        }
        $rows = [];
        foreach ($modules as $mod) {
            $row = [$mod['name'], $mod['status'], $mod['version']];
            if ($showVerbose) {
                $row[] = implode(',', $mod['dependencies']) ?: '-';
            }
            $rows[] = $row;
        }
        $io->table($headers, $rows);
        return 0;
    }
}
