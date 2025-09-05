<?php

declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;

class PublishConfigCommand extends Command
{
    protected $signature = 'module:publish-config {--force : Overwrite existing config file if present}';
    protected $description = "Publish the package's configuration file to your app's config directory (use --force to overwrite)";

    public function handle(): int
    {
        $source = __DIR__ . '/../Config/modules.php';
        $target = config_path('modules.php');
        if (!is_file($source)) {
            $this->error('Source config file not found: ' . $source);
            return 1;
        }
        $force = (bool) $this->option('force');
        if (is_file($target) && ! $force) {
            $this->warn('Config file already exists: ' . $target . ' (use --force to overwrite)');
            return 0;
        }
        if (!copy($source, $target)) {
            $this->error('Failed to copy config file.');
            return 1;
        }
        $this->info('Config file published to: ' . $target . ($force ? ' (overwritten)' : ''));
        return 0;
    }
}
