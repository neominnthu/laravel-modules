<?php

declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Modules\Support\ModuleManifest;

class VerifyManifestsCommand extends Command
{
    protected $signature = 'module:verify-manifests {--json : Output JSON report}';
    protected $description = 'Verify all module manifests for checksum integrity';

    public function handle(): int
    {
        /** @var ModuleManager $manager */
        $manager = $this->laravel->make(ModuleManager::class);
        $results = [];
        $ok = true;
        foreach ($manager->discover() as $name => $path) {
            $file = $path . DIRECTORY_SEPARATOR . ModuleManifest::MANIFEST_FILENAME;
            try {
                $manifest = new ModuleManifest($path);
                $valid = $manifest->verifyChecksum();
                $results[$name] = [
                    'path' => $file,
                    'valid' => $valid,
                ];
                if (!$valid) {
                    $ok = false;
                    $this->error("$name: checksum INVALID ($file)");
                } else {
                    $this->info("$name: checksum OK");
                }
            } catch (\Throwable $e) {
                $results[$name] = [
                    'path' => $file,
                    'valid' => false,
                    'error' => $e->getMessage(),
                ];
                $ok = false;
                $this->error("$name: manifest error - " . $e->getMessage());
            }
        }
        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        return $ok ? 0 : 1;
    }
}
