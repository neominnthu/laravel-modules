<?php

declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Modules\Support\ModuleManifest;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Artisan command to scan and optionally fix module manifest files.
 * Ensures required fields and correct types in module.json files.
 */
class SyncManifestCommand extends Command
{
    protected $signature = 'module:manifest:sync {--fix : Automatically fix missing fields}';
    protected $description = 'Scan and optionally fix module manifest files (schema, required fields, types)';

    /**
     * Execute the manifest sync command.
     *
     * @return int Exit code (0 = success).
     */
    public function handle(): int
    {
        $manager = $this->laravel->make(ModuleManager::class);
        $io = new SymfonyStyle($this->input, $this->output);
        $all = $manager->discover();
        $issues = [];
        $fixed = [];
        foreach ($all as $name => $path) {
            $manifestPath = $path . DIRECTORY_SEPARATOR . ModuleManifest::MANIFEST_FILENAME;
            if (!file_exists($manifestPath)) {
                $issues[] = [$name, 'missing manifest', $manifestPath];
                continue;
            }
            $json = file_get_contents($manifestPath);
            $data = json_decode($json, true);
            if (!is_array($data)) {
                $issues[] = [$name, 'invalid JSON', 'Could not decode'];
                continue;
            }
            $required = ['name', 'version', 'provider'];
            $changed = false;
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    $issues[] = [$name, 'missing field', $field];
                    if ($this->option('fix')) {
                        if ($field === 'name') $data['name'] = $name;
                        if ($field === 'version') $data['version'] = '1.0.0';
                        if ($field === 'provider') $data['provider'] = "Modules\\$name\\Providers\\{$name}ServiceProvider";
                        $changed = true;
                        $fixed[] = [$name, 'added field', $field];
                    }
                }
            }
            // Normalize dependencies to array
            if (isset($data['dependencies']) && !is_array($data['dependencies'])) {
                $data['dependencies'] = (array) $data['dependencies'];
                $changed = true;
                $fixed[] = [$name, 'normalized', 'dependencies'];
            }
            // Normalize dependency_versions to array
            if (isset($data['dependency_versions']) && !is_array($data['dependency_versions'])) {
                $data['dependency_versions'] = (array) $data['dependency_versions'];
                $changed = true;
                $fixed[] = [$name, 'normalized', 'dependency_versions'];
            }
            if ($changed && $this->option('fix')) {
                file_put_contents($manifestPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }
        if ($issues) {
            $io->table(['Module','Issue','Detail'], $issues);
            $io->warning(count($issues) . ' issue(s) found.');
        }
        if ($fixed) {
            $io->table(['Module','Action','Field'], $fixed);
            $io->success(count($fixed) . ' manifest(s) fixed.');
        }
        if (!$issues && !$fixed) {
            $io->success('All manifests are valid.');
        }
        return 0;
    }
}
