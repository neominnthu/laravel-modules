<?php

declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Modules\Support\ModuleManager;

class CacheModulesCommand extends Command
{
    protected $signature = 'module:cache';
    protected $description = 'Build and write the compiled module manifest cache file';

    public function handle(ModuleManager $manager): int
    {
        $io = new SymfonyStyle($this->input, $this->output);
        $data = $manager->buildCache();
        $count = count($data);
        $io->success("Module cache built for {$count} enabled module(s).");
        $io->writeln('Cache file: ' . $manager::CACHE_FILE);
        return 0;
    }
}
