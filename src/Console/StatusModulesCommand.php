<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Quick status summary for modules: counts + simple table.
 */
class StatusModulesCommand extends Command
{
    protected $signature = 'module:status {--json : Output JSON summary}';
    protected $description = 'Show module enablement and version summary';

    public function handle(): int
    {
        /** @var ModuleManager $manager */
        $manager = $this->laravel->make(ModuleManager::class);
        $io = new SymfonyStyle($this->input, $this->output);
        $all = $manager->discover();
        $enabled = [];
        $disabled = [];
        $rows = [];
        foreach ($all as $name => $path) {
            $isEnabled = $manager->enabled($name);
            $version = $manager->version($name) ?? 'n/a';
            if ($isEnabled) {
                $enabled[] = $name;
            } else {
                $disabled[] = $name;
            }
            $rows[] = [$name, $isEnabled ? 'ENABLED' : 'DISABLED', $version];
        }
        $summary = [
            'total' => count($all),
            'enabled_count' => count($enabled),
            'disabled_count' => count($disabled),
            'enabled' => $enabled,
            'disabled' => $disabled,
        ];

        if ($this->option('json')) {
            $payload = $summary + ['modules' => array_map(fn($r) => [
                'name' => $r[0],
                'status' => $r[1],
                'version' => $r[2],
            ], $rows)];
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $io->title('Module Status');
        $io->writeln(sprintf('<info>Total:</info> %d  <info>Enabled:</info> %d  <info>Disabled:</info> %d', $summary['total'], $summary['enabled_count'], $summary['disabled_count']));
        $io->newLine();
        $io->table(['Module','Status','Version'], $rows);
        return 0;
    }
}
