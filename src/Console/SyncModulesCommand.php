<?php
declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Synchronize modules registry with actual filesystem directories.
 * Adds new modules (optionally enabled) and prunes stale registry entries.
 */
class SyncModulesCommand extends Command
{
    protected $signature = 'module:sync {--enable-new : Automatically enable newly discovered modules}'
        . ' {--prune-missing : Remove registry entries whose directories disappeared}'
        . ' {--dry-run : Show planned changes without writing}'
        . ' {--json : JSON output}';
    protected $description = 'Synchronize module registry with filesystem (add missing, prune removed)';

    public function handle(): int
    {
        /** @var ModuleManager $manager */
        $manager = $this->laravel->make(ModuleManager::class);
        $io = new SymfonyStyle($this->input, $this->output);
        $registry = $manager->registry();
        $discovered = $manager->discover(); // name => path

        $enableNew = (bool) $this->option('enable-new');
        $pruneMissing = (bool) $this->option('prune-missing');
        $dry = (bool) $this->option('dry-run');
        $asJson = (bool) $this->option('json');

        $toAdd = [];
        $toPrune = [];

        // Determine modules present on disk but absent from registry
        foreach (array_keys($discovered) as $name) {
            if (! array_key_exists($name, $registry)) {
                $toAdd[] = $name;
            }
        }
        // Determine registry entries whose directory vanished
        foreach (array_keys($registry) as $name) {
            if (! isset($discovered[$name])) {
                $toPrune[] = $name;
            }
        }

        $actions = [
            'add' => $toAdd,
            'prune' => $toPrune,
            'will_enable' => $enableNew ? $toAdd : [],
            'enable_new' => $enableNew,
            'prune_missing' => $pruneMissing,
            'dry_run' => $dry,
        ];

        if ($asJson) {
            $this->line(json_encode($actions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $io->title('Module Sync Plan');
            $io->writeln('New modules: ' . ($toAdd ? implode(', ', $toAdd) : '(none)'));
            $io->writeln('Missing directories (stale registry): ' . ($toPrune ? implode(', ', $toPrune) : '(none)'));
            $io->writeln('Enable new: ' . ($enableNew ? 'yes' : 'no'));
            $io->writeln('Prune missing: ' . ($pruneMissing ? 'yes' : 'no'));
            $io->writeln('Dry run: ' . ($dry ? 'yes' : 'no'));
        }

        if (! $dry) {
            foreach ($toAdd as $name) {
                if ($enableNew) {
                    $manager->enable($name);
                } else {
                    $manager->addToRegistry($name, false);
                }
            }
            if ($pruneMissing) {
                foreach ($toPrune as $name) {
                    $manager->pruneFromRegistry($name);
                }
            }
        }

        if (! $asJson) {
            if ($dry) {
                $io->success('Dry run complete. No changes written.');
            } else {
                $io->success('Sync applied.');
            }
        }
        return 0;
    }
}
