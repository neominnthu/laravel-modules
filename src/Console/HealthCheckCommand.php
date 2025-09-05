<?php

declare(strict_types=1);

namespace Modules\Console;

use Illuminate\Console\Command;
use Modules\Support\ModuleManager;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Health check command to verify the module system is working.
 * This command answers the question: "check if this is working?"
 */
class HealthCheckCommand extends Command
{
    protected $signature = 'module:health-check {--json : Output results as JSON}';
    protected $description = 'Check if the module system is working correctly';

    public function handle(): int
    {
        $manager = $this->laravel->make(ModuleManager::class);
        $io = new SymfonyStyle($this->input, $this->output);
        
        $checks = [];
        $issues = [];
        $overallStatus = 'HEALTHY';
        
        try {
            // Check 1: Module discovery
            $discovered = $manager->discover();
            if (empty($discovered)) {
                $issues[] = 'No modules discovered';
                $overallStatus = 'UNHEALTHY';
            } else {
                $checks[] = [
                    'check' => 'Module Discovery',
                    'status' => 'PASS',
                    'detail' => count($discovered) . ' modules discovered: ' . implode(', ', array_keys($discovered))
                ];
            }
            
            // Check 2: Registry functionality
            $registry = $manager->registry();
            if (empty($registry)) {
                $issues[] = 'Module registry is empty';
                $overallStatus = 'UNHEALTHY';
            } else {
                $enabled = array_filter($registry);
                $checks[] = [
                    'check' => 'Module Registry',
                    'status' => 'PASS',
                    'detail' => count($enabled) . ' modules enabled, ' . (count($registry) - count($enabled)) . ' disabled'
                ];
            }
            
            // Check 3: Manifest validation
            $manifestIssues = [];
            foreach (array_keys($discovered) as $module) {
                try {
                    $manifest = $manager->manifest($module);
                    $required = ['name', 'version', 'provider'];
                    $missing = array_diff($required, array_keys($manifest));
                    if (!empty($missing)) {
                        $manifestIssues[] = "$module: missing " . implode(', ', $missing);
                    }
                } catch (\Throwable $e) {
                    $manifestIssues[] = "$module: " . $e->getMessage();
                }
            }
            
            if (!empty($manifestIssues)) {
                $issues = array_merge($issues, $manifestIssues);
                $overallStatus = 'UNHEALTHY';
            } else {
                $checks[] = [
                    'check' => 'Module Manifests',
                    'status' => 'PASS',
                    'detail' => 'All module manifests are valid'
                ];
            }
            
            // Check 4: Cache functionality
            try {
                $cache = $manager->buildCache();
                $checks[] = [
                    'check' => 'Cache Building',
                    'status' => 'PASS',
                    'detail' => count($cache) . ' modules cached successfully'
                ];
            } catch (\Throwable $e) {
                $issues[] = 'Cache building failed: ' . $e->getMessage();
                $overallStatus = 'UNHEALTHY';
            }
            
            // Check 5: Module operations
            $operationIssues = [];
            foreach (array_keys($discovered) as $module) {
                try {
                    $enabled = $manager->enabled($module);
                    $path = $manager->path($module);
                    $version = $manager->version($module);
                    
                    if (!file_exists($path)) {
                        $operationIssues[] = "$module: path does not exist ($path)";
                    }
                    if (empty($version)) {
                        $operationIssues[] = "$module: version is empty";
                    }
                } catch (\Throwable $e) {
                    $operationIssues[] = "$module: " . $e->getMessage();
                }
            }
            
            if (!empty($operationIssues)) {
                $issues = array_merge($issues, $operationIssues);
                $overallStatus = 'UNHEALTHY';
            } else {
                $checks[] = [
                    'check' => 'Module Operations',
                    'status' => 'PASS',
                    'detail' => 'All modules operational'
                ];
            }
            
        } catch (\Throwable $e) {
            $issues[] = 'Critical error: ' . $e->getMessage();
            $overallStatus = 'CRITICAL';
        }
        
        // Output results
        if ($this->option('json')) {
            $result = [
                'overall_status' => $overallStatus,
                'checks' => $checks,
                'issues' => $issues,
                'timestamp' => date('Y-m-d H:i:s'),
                'answer' => $overallStatus === 'HEALTHY' ? 'YES - The system is working!' : 'NO - The system has issues'
            ];
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $io->title('Module System Health Check');
            
            // Show the answer to "is this working?"
            if ($overallStatus === 'HEALTHY') {
                $io->success('✅ YES - The Laravel Modules system is working correctly!');
            } else {
                $io->error('❌ NO - The system has issues that need attention.');
            }
            
            $io->section('Check Results');
            if (!empty($checks)) {
                $tableData = array_map(function($check) {
                    return [$check['check'], $check['status'], $check['detail']];
                }, $checks);
                $io->table(['Check', 'Status', 'Details'], $tableData);
            }
            
            if (!empty($issues)) {
                $io->section('Issues Found');
                foreach ($issues as $issue) {
                    $io->writeln("• $issue");
                }
            }
            
            $io->section('Summary');
            $io->writeln("Overall Status: <comment>$overallStatus</comment>");
            $io->writeln("Checks Passed: " . count($checks));
            $io->writeln("Issues Found: " . count($issues));
        }
        
        return $overallStatus === 'HEALTHY' ? 0 : 1;
    }
}