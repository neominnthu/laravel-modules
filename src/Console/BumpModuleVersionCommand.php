<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;

class BumpModuleVersionCommand extends Command
{
    protected $signature = 'module:version:bump {module : Module name} {type=patch : patch|minor|major|set explicit version}';
    protected $description = 'Bump a module version (semantic increment or explicit)';

    public function handle(): int
    {
        /** @var ModuleManager $manager */
        $manager = $this->laravel->make(ModuleManager::class);
        $name = $this->argument('module');
        if (!\Modules\Support\ModuleManager::validateModuleName($name)) {
            $this->error("Invalid module name: [$name]");
            return 1;
        }
        $type = $this->argument('type');
        $path = $manager->path($name);
        if (! $path) {
            $this->error("Module [$name] not found.");
            return 1;
        }
        $file = $path . DIRECTORY_SEPARATOR . 'module.json';
        if (! is_file($file)) {
            $this->error('module.json missing for ' . $name);
            return 1;
        }
        $raw = file_get_contents($file);
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            $this->error('Invalid JSON in module.json');
            return 1;
        }
        $current = (string) ($data['version'] ?? '0.0.0');
        if (str_contains($type, '.')) {
            $new = $type; // explicit set
        } else {
            [$maj,$min,$pat] = array_pad(array_map('intval', explode('.', $current)), 3, 0);
            switch ($type) {
                case 'major':
                    $maj++; $min = 0; $pat = 0; break;
                case 'minor':
                    $min++; $pat = 0; break;
                case 'patch':
                default:
                    $pat++; break;
            }
            $new = $maj . '.' . $min . '.' . $pat;
        }
        $data['version'] = $new;
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $manager->refreshManifest($name);
        $this->info("$name version bumped: $current -> $new");
        return 0;
    }
}
