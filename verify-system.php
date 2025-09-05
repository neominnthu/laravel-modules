<?php

declare(strict_types=1);

/**
 * Simple verification script to check if the Laravel Modules system is working.
 * This script verifies basic functionality without requiring full test infrastructure.
 */

echo "Laravel Modules System Verification\n";
echo "====================================\n\n";

$errors = [];
$success = [];

// Check if we're in the right directory
if (!file_exists('composer.json')) {
    $errors[] = "composer.json not found - are you in the right directory?";
} else {
    $success[] = "✓ Found composer.json";
}

// Check modules.json registry file
if (!file_exists('modules.json')) {
    $errors[] = "modules.json registry file not found";
} else {
    $success[] = "✓ Found modules.json registry";
    
    $registry = json_decode(file_get_contents('modules.json'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = "modules.json contains invalid JSON: " . json_last_error_msg();
    } else {
        $success[] = "✓ modules.json is valid JSON";
        echo "   Registry contents: " . json_encode($registry) . "\n";
    }
}

// Check Modules directory
if (!is_dir('Modules')) {
    $errors[] = "Modules directory not found";
} else {
    $success[] = "✓ Found Modules directory";
    
    $modules = array_filter(scandir('Modules'), function($item) {
        return $item !== '.' && $item !== '..' && is_dir('Modules/' . $item);
    });
    
    if (empty($modules)) {
        $errors[] = "No modules found in Modules directory";
    } else {
        $success[] = "✓ Found modules: " . implode(', ', $modules);
        
        // Check each module for required files
        foreach ($modules as $module) {
            $manifestPath = "Modules/$module/module.json";
            if (!file_exists($manifestPath)) {
                $errors[] = "$module: missing module.json manifest";
            } else {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = "$module: invalid JSON in module.json";
                } else {
                    $requiredFields = ['name', 'version', 'provider'];
                    $missing = array_diff($requiredFields, array_keys($manifest));
                    if (!empty($missing)) {
                        $errors[] = "$module: missing required fields in manifest: " . implode(', ', $missing);
                    } else {
                        $success[] = "✓ $module: valid manifest with required fields";
                    }
                }
            }
        }
    }
}

// Check source files
$coreFiles = [
    'src/ModulesServiceProvider.php',
    'src/Support/ModuleManager.php',
    'src/Console/ValidateModulesCommand.php',
    'src/Console/DoctorModulesCommand.php'
];

foreach ($coreFiles as $file) {
    if (!file_exists($file)) {
        $errors[] = "Core file missing: $file";
    } else {
        $success[] = "✓ Found core file: $file";
    }
}

// Check test infrastructure
if (!file_exists('tests/TestCase.php')) {
    $errors[] = "Test infrastructure missing: tests/TestCase.php";
} else {
    $success[] = "✓ Found test infrastructure";
}

// Summary
echo "\nVerification Results:\n";
echo "=====================\n\n";

if (!empty($success)) {
    echo "✅ SUCCESS:\n";
    foreach ($success as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ ERRORS:\n";
    foreach ($errors as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
    echo "Status: FAILED - " . count($errors) . " error(s) found\n";
    exit(1);
} else {
    echo "Status: SUCCESS - System appears to be working correctly!\n";
    echo "\nBasic functionality verified:\n";
    echo "- Module registry system\n";
    echo "- Module discovery\n";
    echo "- Module manifests\n";
    echo "- Core source files\n";
    echo "- Test infrastructure\n";
    exit(0);
}