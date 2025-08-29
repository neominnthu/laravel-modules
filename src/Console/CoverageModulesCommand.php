<?php

declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Artisan command to run Pest code coverage for all modules or a specific module.
 */
class CoverageModulesCommand extends Command
{
    protected $signature = 'module:coverage {module? : Module name (optional)}';
    protected $description = 'Run Pest code coverage for all modules or a specific module.';

    /**
     * Execute the coverage command.
     *
     * @return int Exit code (0 = success, non-zero = failure).
     */
    public function handle(): int
    {
    $io = new SymfonyStyle($this->input, $this->output);
        $module = $this->argument('module');
        $cmd = 'vendor\bin\pest --coverage';
        if ($module) {
            $cmd .= ' Modules/' . $module . '/';
        }
        $io->section('Running Pest Coverage');
        $io->text('Command: ' . $cmd);
        passthru($cmd, $exitCode);
        if ($exitCode !== 0) {
            $io->error('Coverage run failed.');
            return $exitCode;
        }
        $io->success('Coverage report complete.');
        return 0;
    }
}
