<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Modules\Support\ModuleManifest;
use Modules\Support\ModuleManifestException;
use Symfony\Component\Console\Style\SymfonyStyle;

class ValidateModulesCommand extends Command
{
    protected $signature = 'module:validate {--no-strict : Ignore strict dependency filtering}';
    protected $description = 'Validate module manifests & dependencies';

    public function handle(ModuleManager $manager): int
    {
        $io = new SymfonyStyle($this->getInput(), $this->getOutput());
        $issues = [];
        $all = $manager->discover();
        $graph = [];
        foreach ($all as $name => $path) {
            if (! $manager->enabled($name)) {
                continue;
            }
            try {
                $manifest = new ModuleManifest($path);
                $deps = $manifest->dependencies();
                $graph[$name] = $deps;
                foreach ($deps as $dep) {
                    if (! isset($all[$dep])) {
                        $issues[] = [$name, 'missing dependency', $dep];
                    } elseif (! $manager->enabled($dep)) {
                        $issues[] = [$name, 'dependency disabled', $dep];
                    }
                }
            } catch (ModuleManifestException $e) {
                $issues[] = [$name, 'invalid manifest', $e->getMessage()];
            }
        }
        // Cycle detection
        $visited = [];
        $stack = [];
        $cycles = [];
        $detect = function($node) use (&$detect, &$graph, &$visited, &$stack, &$cycles) {
            if (isset($stack[$node])) {
                // Found a cycle
                $cycle = array_keys($stack);
                $cycle[] = $node;
                $cycles[] = $cycle;
                return;
            }
            if (isset($visited[$node])) return;
            $visited[$node] = true;
            $stack[$node] = true;
            foreach ($graph[$node] ?? [] as $dep) {
                $detect($dep);
            }
            unset($stack[$node]);
        };
        foreach (array_keys($graph) as $mod) {
            $detect($mod);
        }
        foreach ($cycles as $cycle) {
            $issues[] = [implode(' -> ', $cycle), 'dependency cycle', 'Cycle detected'];
        }
        if ($issues) {
            $io->table(['Module','Issue','Detail'], $issues);
            $io->warning(count($issues) . ' issue(s) found.');
            return 1;
        }
        $io->success('All module dependencies satisfied.');
        return 0;
    }
}
