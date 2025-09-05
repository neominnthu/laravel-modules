<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Modules\Support\ModuleManifest;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Provides an overall diagnostic report ("doctor") for the modules subsystem.
 * Surfaces common configuration or runtime mismatches.
 */
class DoctorModulesCommand extends Command
{
    protected $signature = 'module:doctor'
        . ' {--json : Output JSON diagnostics}'
        . ' {--fix : Attempt to auto-fix cache issues (rebuild cache)}'
        . ' {--sync : Synchronize registry with filesystem (add & prune)}'
        . ' {--enable-new : When syncing, enable newly discovered modules}'
        . ' {--prune-missing : When syncing, prune stale registry entries}';
    protected $description = 'Diagnose module system health & mismatches (optionally sync & fix)';

    public function handle(): int
    {
        $io = new SymfonyStyle($this->input, $this->output);
        /** @var ModuleManager $manager */
        $manager = $this->laravel->make(ModuleManager::class);

        $syncActions = [];
        $doSync = (bool) $this->option('sync');
        $enableNew = (bool) $this->option('enable-new');
        $pruneMissing = (bool) $this->option('prune-missing');

        if ($doSync) {
            $discovered = $manager->discover();
            $registry = $manager->registry();
            $toAdd = [];
            foreach (array_keys($discovered) as $m) {
                if (! array_key_exists($m, $registry)) {
                    $toAdd[] = $m;
                }
            }
            $toPrune = [];
            foreach (array_keys($registry) as $m) {
                if (! isset($discovered[$m])) {
                    $toPrune[] = $m;
                }
            }
            foreach ($toAdd as $m) {
                if ($enableNew) {
                    $manager->enable($m);
                } else {
                    $manager->addToRegistry($m, false);
                }
                $syncActions[] = 'registry_added:' . $m;
            }
            if ($pruneMissing) {
                foreach ($toPrune as $m) {
                    $manager->pruneFromRegistry($m);
                    $syncActions[] = 'registry_pruned:' . $m;
                }
            }
        }

        $diagnostics = [
            'config' => [
                'lazy' => (bool) config('modules.lazy'),
                'strict_dependencies' => (bool) config('modules.strict_dependencies'),
                'autoload_factories' => (bool) config('modules.autoload_factories'),
            ],
            'paths' => [
                'modules_root' => $manager->modulesRoot(),
                'cache_file' => base_path(ModuleManager::CACHE_FILE),
                'registry_file' => base_path(ModuleManager::REGISTRY_FILE),
            ],
            'registry' => [],
            'enabled' => $manager->enabledModules(),
            'cache_present' => file_exists(base_path(ModuleManager::CACHE_FILE)),
            'issues' => [],
            'sync' => [
                'performed' => $doSync,
                'enable_new' => $enableNew,
                'prune_missing' => $pruneMissing,
                'actions' => $syncActions,
            ],
        ];

        // Dependency chain path reporting + version constraint validation
        $depPaths = [];
        $visited = [];
        $stack = [];
        $modules = $manager->discover();
        $getDeps = function($mod) use ($modules) {
            try {
                $manifest = new ModuleManifest($modules[$mod]);
                return $manifest->dependencies();
            } catch (\Throwable) {
                return [];
            }
        };
        $getDepVersions = function($mod) use ($modules) {
            try {
                $manifest = new ModuleManifest($modules[$mod]);
                return $manifest->dependencyVersions();
            } catch (\Throwable) {
                return [];
            }
        };
        $findPaths = function($mod, $path = []) use (&$findPaths, &$depPaths, &$visited, &$stack, $getDeps, $getDepVersions, $manager) {
            if (in_array($mod, $stack)) {
                $cycle = array_merge($path, [$mod]);
                $depPaths[] = ['type' => 'cycle', 'path' => $cycle];
                return;
            }
            $stack[] = $mod;
            $depVersions = $getDepVersions($mod);
            foreach ($getDeps($mod) as $dep) {
                if (!isset($modules[$dep])) {
                    $depPaths[] = ['type' => 'missing', 'path' => array_merge($path, [$mod, $dep])];
                } elseif (! $manager->enabled($dep)) {
                    $depPaths[] = ['type' => 'disabled', 'path' => array_merge($path, [$mod, $dep])];
                } else {
                    // Version constraint check
                    if (isset($depVersions[$dep])) {
                        $depVersion = $manager->version($dep);
                        $constraint = $depVersions[$dep];
                        if (! \Modules\Support\Semver::satisfies($depVersion, $constraint)) {
                            $depPaths[] = [
                                'type' => 'version',
                                'path' => array_merge($path, [$mod, $dep]),
                                'constraint' => $constraint,
                                'found' => $depVersion,
                            ];
                        }
                    }
                    $findPaths($dep, array_merge($path, [$mod]));
                }
            }
            array_pop($stack);
        };
        foreach (array_keys($modules) as $mod) {
            $findPaths($mod);
        }

        // Capture per-module info
        foreach ($manager->discover() as $name => $path) {
            $row = [
                'name' => $name,
                'enabled' => $manager->enabled($name),
                'path' => $path,
            ];
            try {
                $manifest = new ModuleManifest($path);
                $arr = $manifest->toArray();
                $row['version'] = $arr['version'] ?? null;
                $row['provider'] = $arr['provider'] ?? null;
                $row['dependencies'] = $arr['dependencies'] ?? [];
                $row['dependency_versions'] = $arr['dependency_versions'] ?? [];
                // Validate deps present/enabled
                foreach ($row['dependencies'] as $dep) {
                    if (! isset($diagnostics['registry'][$dep]) && ! $manager->path($dep)) {
                        $diagnostics['issues'][] = "{$name}: missing dependency directory ({$dep})";
                    } elseif (! $manager->enabled($dep)) {
                        $diagnostics['issues'][] = "{$name}: dependency disabled ({$dep})";
                    }
                }
            } catch (\Throwable $e) {
                $row['error'] = $e->getMessage();
                $diagnostics['issues'][] = "{$name}: manifest error - {$e->getMessage()}";
            }
            $diagnostics['registry'][$name] = $row;
        }
        $diagnostics['dependency_paths'] = $depPaths;
        // Add version constraint failures to issues
        foreach ($depPaths as $dp) {
            if ($dp['type'] === 'version') {
                $diagnostics['issues'][] = sprintf(
                    '%s: dependency %s version constraint not satisfied (%s, found %s)',
                    $dp['path'][count($dp['path'])-2],
                    $dp['path'][count($dp['path'])-1],
                    $dp['constraint'],
                    $dp['found']
                );
            }
        }

        // Add stale registry issues (entries present in registry but directory gone)
        foreach ($manager->registry() as $regName => $_) {
            if (! isset($manager->discover()[$regName])) {
                $diagnostics['issues'][] = "$regName: registry entry stale (missing directory)";
            }
        }

        // Cross-check cache vs enabled registry only if a cache file currently exists
        if ($diagnostics['cache_present']) {
            $cached = $manager->cached();
            foreach ($diagnostics['enabled'] as $en) {
                if (! isset($cached[$en])) {
                    $diagnostics['issues'][] = "$en: enabled but missing from cache (run module:cache)";
                }
            }
            foreach (array_keys($cached) as $c) {
                if (! $manager->enabled($c)) {
                    $diagnostics['issues'][] = "$c: present in cache but disabled in registry";
                }
            }
        }

        // Auto-fix phase (run before output if requested) – rebuild cache & reconcile simple cache mismatches
        if ($this->option('fix')) {
            $performed = [];
            $needsCacheRebuild = false;
            foreach ($diagnostics['enabled'] as $en) {
                if ($diagnostics['cache_present'] && ! isset($manager->cached()[$en])) {
                    $needsCacheRebuild = true; break;
                }
            }
            // If cache file missing but we have enabled modules, rebuild
            if (! $diagnostics['cache_present'] && $diagnostics['enabled']) {
                $needsCacheRebuild = true;
            }
            if ($needsCacheRebuild) {
                $manager->buildCache();
                $performed[] = 'cache_rebuilt';
            }
            // Re-run diagnostics quickly after potential changes
            $diagnostics['cache_present'] = file_exists(base_path(ModuleManager::CACHE_FILE));
            if ($performed) {
                // Remove cache-related issues now resolved
                $diagnostics['issues'] = array_values(array_filter($diagnostics['issues'], fn($i) => ! str_contains($i, 'cache')));
            }
            $diagnostics['fix_actions'] = array_merge($syncActions, $performed);
        } elseif ($syncActions) {
            $diagnostics['fix_actions'] = $syncActions; // maintain prior key for user familiarity
        }

        if ($this->option('json')) {
            $this->line(json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $hasConstraintIssue = false;
            foreach ($diagnostics['dependency_paths'] as $dp) {
                if ($dp['type'] === 'version') {
                    $hasConstraintIssue = true; break;
                }
            }
            return empty($diagnostics['issues']) && ! $hasConstraintIssue ? 0 : 1;
        }

        $io->title('Module System Doctor');
        $io->section('Configuration');
        foreach ($diagnostics['config'] as $k => $v) {
            $io->writeln(sprintf('<info>%-22s</info> %s', $k, var_export($v, true)));
        }
        $io->section('Modules');
        $table = new Table($this->output);
        $table->setHeaders(['Name','Enabled','Version','Provider','Deps']);
        foreach ($diagnostics['registry'] as $info) {
            $table->addRow([
                $info['name'],
                $info['enabled'] ? 'yes' : 'no',
                $info['version'] ?? '-',
                $info['provider'] ?? '-',
                $info['dependencies'] ? implode(',', $info['dependencies']) : '-',
            ]);
        }
        $table->render();
        if (! empty($diagnostics['fix_actions'] ?? [])) {
            $io->section('Fix Actions');
            foreach ($diagnostics['fix_actions'] as $act) {
                $io->writeln(" - $act");
            }
        }
        $hasConstraintIssue = false;
        foreach ($diagnostics['dependency_paths'] as $dp) {
            if ($dp['type'] === 'version') {
                $hasConstraintIssue = true; break;
            }
        }
        if ($diagnostics['issues']) {
            $io->section('Issues');
            foreach ($diagnostics['issues'] as $issue) {
                $io->writeln(" - $issue");
            }
            if (!empty($diagnostics['dependency_paths'])) {
                $io->section('Dependency Paths');
                foreach ($diagnostics['dependency_paths'] as $dp) {
                    $type = $dp['type'];
                    $path = implode(' → ', $dp['path']);
                    if ($type === 'version') {
                        $io->writeln(" [version] $path (constraint: {$dp['constraint']}, found: {$dp['found']})");
                    } else {
                        $io->writeln(" [$type] $path");
                    }
                }
            }
            $io->warning(count($diagnostics['issues']) . ' issue(s) detected');
        } else {
            $io->success('No issues detected');
        }
    return empty($diagnostics['issues']) && ! $hasConstraintIssue ? 0 : 1;
    }
}
