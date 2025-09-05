# System Verification

This document explains how to verify that the Laravel Modules system is working correctly.

## Quick Verification

### Option 1: PHP Script (No Dependencies Required)
Run the standalone verification script:
```bash
php verify-system.php
```

This script performs basic checks:
- ✓ Module registry system
- ✓ Module discovery
- ✓ Module manifests
- ✓ Core source files
- ✓ Test infrastructure

### Option 2: Artisan Health Check Command
If you have Laravel installed and the package is integrated:
```bash
php artisan module:health-check
```

For JSON output:
```bash
php artisan module:health-check --json
```

### Option 3: Run the Test Suite
If you have the development dependencies installed:
```bash
./vendor/bin/pest tests/SystemWorkingTest.php
```

## What Gets Checked

The verification process tests these core areas:

1. **Module Discovery**: Can the system find modules in the `Modules/` directory?
2. **Module Registry**: Is the `modules.json` file readable and valid?
3. **Module Manifests**: Are all module.json files valid and contain required fields?
4. **Module Operations**: Can modules be enabled/disabled and their metadata accessed?
5. **Cache Functionality**: Can the system build and maintain module cache?
6. **Command Registration**: Are the module management commands available?

## Expected Output

✅ **Success**: All checks pass, system is working
❌ **Failure**: One or more issues found, see details

## Common Issues

- **No modules found**: Check that modules exist in `Modules/` directory
- **Invalid JSON**: Check syntax in `modules.json` or module manifest files
- **Missing manifests**: Ensure each module has a `module.json` file
- **Command not found**: Verify the service provider is registered

## Answer to "Is This Working?"

If all checks pass, the answer is: **YES, the Laravel Modules system is working correctly!**