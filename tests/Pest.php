<?php

/**
 * Pest configuration for the package test suite.
 */

use Pest\Laravel\commands; // Keeps IDE helper reference

// Ensure quiet mode env honored early (can still be disabled by explicitly exporting different value before running tests)
if (getenv('QUIET_MODULE_TESTS') === false) {
    putenv('QUIET_MODULE_TESTS=1');
}

beforeEach(function () {
    // Baseline: Blog enabled, Shop disabled (tests rely on this start state)
    if (file_exists(base_path('modules.json'))) {
        $state = json_decode(file_get_contents(base_path('modules.json')), true) ?: [];
        $state['Blog'] = true;
        $state['Shop'] = $state['Shop'] ?? false; // do not force true
        file_put_contents(base_path('modules.json'), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
});

afterEach(function () {
    // No heavy restore here; TestCase handles manifest rewrites. Only ensure registry reset.
    if (file_exists(base_path('modules.json'))) {
        $state = json_decode(file_get_contents(base_path('modules.json')), true) ?: [];
        $state['Blog'] = true;
        $state['Shop'] = false; // revert
        file_put_contents(base_path('modules.json'), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
});
