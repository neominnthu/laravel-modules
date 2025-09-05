<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Modules\Support\ModuleManifest;

/**
 * Artisan command to show the module dependency graph (table, JSON, or DOT format).
 */
class GraphModulesCommand extends Command
{
    protected $signature = 'module:graph {--json : Output JSON instead of table} {--dot : Output Graphviz DOT format}';
    protected $description = 'Show module dependency graph';

    /**
     * Execute the graph command to display module dependencies.
     *
     * @return int Exit code (SUCCESS).
     */
    public function handle(): int
    {
        $manager = $this->laravel->make(ModuleManager::class);
        $modules = $manager->discover();
        $rows = [];
        $graph = [];
        foreach ($modules as $name => $path) {
            try {
                $manifest = new ModuleManifest($path);
                $deps = $manifest->dependencies();
            } catch (\Throwable $e) {
                $deps = [];
            }
            $missing = array_values(array_filter($deps, fn($d) => !isset($modules[$d])));
            $disabled = array_values(array_filter($deps, fn($d) => isset($modules[$d]) && !$manager->enabled($d)));
            $status = $manager->enabled($name) ? 'ENABLED' : 'DISABLED';
            $rows[] = [
                $name,
                $status,
                implode(',', $deps) ?: '-',
                implode(',', $missing) ?: '-',
                implode(',', $disabled) ?: '-',
            ];
            $graph[$name] = [
                'enabled' => $manager->enabled($name),
                'dependencies' => $deps,
                'missing' => $missing,
                'disabled' => $disabled,
            ];
        }
        if ($this->option('json')) {
            $this->line(json_encode($graph, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }
        if ($this->option('dot')) {
            $this->line('digraph Modules {');
            foreach ($graph as $mod => $info) {
                $color = $info['enabled'] ? 'green' : 'gray';
                $this->line("  \"$mod\" [color={$color}];");
                foreach ($info['dependencies'] as $dep) {
                    $this->line("  \"$mod\" -> \"$dep\";");
                }
            }
            $this->line('}');
            return self::SUCCESS;
        }
        $this->table(['Module','Status','Dependencies','Missing','Disabled'], $rows);
        return self::SUCCESS;
    }
}
