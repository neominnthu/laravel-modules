<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;

/**
 * Display version information for one or all modules.
 */
class ShowModuleVersionCommand extends Command
{
    protected $signature = 'module:version:show {module? : Specific module name} {--json : Output JSON}';
    protected $description = 'Show module version(s)';

    public function handle(): int
    {
        /** @var ModuleManager $manager */
        $manager = $this->laravel->make(ModuleManager::class);
        $target = $this->argument('module');

        $data = [];
        if ($target) {
            if (!ModuleManager::validateModuleName($target)) {
                $this->error("Invalid module name: [{$target}]");
                return 1;
            }
            $path = $manager->path($target);
            if (! $path) {
                $this->error("Module [{$target}] not found.");
                return 1;
            }
            $data[$target] = [
                'name' => $target,
                'version' => $manager->version($target),
                'enabled' => $manager->enabled($target),
                'path' => $path,
            ];
        } else {
            foreach ($manager->discover() as $name => $path) {
                $data[$name] = [
                    'name' => $name,
                    'version' => $manager->version($name),
                    'enabled' => $manager->enabled($name),
                    'path' => $path,
                ];
            }
            ksort($data);
        }

        if ($this->option('json')) {
            $this->line(json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $this->table(['Name','Version','Enabled','Path'], array_map(function($row){
            return [
                $row['name'],
                $row['version'] ?? '-',
                $row['enabled'] ? 'yes' : 'no',
                $row['path'],
            ];
        }, $data));
        return 0;
    }
}
