<?php

declare(strict_types=1);

use Modules\Tests\TestCase;
use Modules\Support\ModuleManager;

uses(TestCase::class);

it('verifies the module system is working correctly', function () {
    // This test answers the question "check if this is working?"
    // by verifying core functionality is operational
    
    $manager = $this->app->make(ModuleManager::class);
    
    // Test 1: Module discovery works
    $discovered = $manager->discover();
    expect($discovered)->toBeArray();
    expect($discovered)->toHaveKey('Blog');
    expect($discovered)->toHaveKey('Shop');
    
    // Test 2: Module registry works
    $registry = $manager->registry();
    expect($registry)->toBeArray();
    expect($registry)->toHaveKey('Blog');
    expect($registry)->toHaveKey('Shop');
    
    // Test 3: Module enabling/disabling works
    expect($manager->enabled('Blog'))->toBeTrue();
    expect($manager->enabled('Shop'))->toBeTrue();
    
    // Test 4: Module manifests are readable
    $blogManifest = $manager->manifest('Blog');
    expect($blogManifest)->toBeArray();
    expect($blogManifest)->toHaveKey('name');
    expect($blogManifest)->toHaveKey('version');
    expect($blogManifest)->toHaveKey('provider');
    expect($blogManifest['name'])->toBe('Blog');
    
    $shopManifest = $manager->manifest('Shop');
    expect($shopManifest)->toBeArray();
    expect($shopManifest)->toHaveKey('name');
    expect($shopManifest)->toHaveKey('version');
    expect($shopManifest)->toHaveKey('provider');
    expect($shopManifest['name'])->toBe('Shop');
    
    // Test 5: Module paths are resolvable
    expect($manager->path('Blog'))->toBeString();
    expect($manager->path('Shop'))->toBeString();
    expect(file_exists($manager->path('Blog')))->toBeTrue();
    expect(file_exists($manager->path('Shop')))->toBeTrue();
    
    // Test 6: Module versions are accessible
    expect($manager->version('Blog'))->toBeString();
    expect($manager->version('Shop'))->toBeString();
    
    // Test 7: Cache building works
    $cache = $manager->buildCache();
    expect($cache)->toBeArray();
    expect($cache)->toHaveKey('Blog');
    expect($cache)->toHaveKey('Shop');
    
    // Test 8: Module disable/enable cycle works
    $manager->disable('Blog');
    expect($manager->enabled('Blog'))->toBeFalse();
    
    $manager->enable('Blog');
    expect($manager->enabled('Blog'))->toBeTrue();
    
    // Success - the module system is working!
});

it('verifies module dependency validation is working', function () {
    $manager = $this->app->make(ModuleManager::class);
    
    // Shop depends on Blog according to the test manifests
    $shopManifest = $manager->manifest('Shop');
    expect($shopManifest)->toHaveKey('dependencies');
    expect($shopManifest['dependencies'])->toContain('Blog');
    
    // Test dependency resolution
    if (isset($shopManifest['dependencies'])) {
        foreach ($shopManifest['dependencies'] as $dep) {
            expect($manager->discover())->toHaveKey($dep, "Dependency $dep should be discoverable");
        }
    }
});

it('verifies command registration is working', function () {
    // Test that key commands are registered and accessible
    $commands = $this->app->make('Illuminate\Contracts\Console\Kernel')->all();
    
    $expectedCommands = [
        'module:validate',
        'module:doctor',
    ];
    
    foreach ($expectedCommands as $command) {
        expect($commands)->toHaveKey($command, "Command $command should be registered");
    }
});

it('answers the question: is the system working?', function () {
    // This is the main test that directly answers "check if this is working?"
    
    try {
        $manager = $this->app->make(ModuleManager::class);
        
        // Core functionality check
        $discovered = $manager->discover();
        $registry = $manager->registry();
        $cache = $manager->buildCache();
        
        // Basic sanity checks
        expect(count($discovered))->toBeGreaterThan(0, 'Should discover at least one module');
        expect(count($registry))->toBeGreaterThan(0, 'Registry should have at least one module');
        expect(count($cache))->toBeGreaterThan(0, 'Cache should contain at least one module');
        
        // Module operations check
        $firstModule = array_keys($discovered)[0];
        $isEnabled = $manager->enabled($firstModule);
        $manifest = $manager->manifest($firstModule);
        $path = $manager->path($firstModule);
        $version = $manager->version($firstModule);
        
        expect($manifest)->toBeArray('Manifest should be readable');
        expect($path)->toBeString('Path should be resolvable');
        expect($version)->toBeString('Version should be accessible');
        expect(file_exists($path))->toBeTrue('Module path should exist');
        
        // Yes, the system is working!
        expect(true)->toBeTrue('✅ YES - The Laravel Modules system is working correctly!');
        
    } catch (Throwable $e) {
        // No, something is broken
        expect(false)->toBeTrue('❌ NO - The system is NOT working: ' . $e->getMessage());
    }
});