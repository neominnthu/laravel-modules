<?php

/**
 * Pest configuration for the package test suite.
 */

use Pest\Laravel\commands; // Keeps IDE helper reference

beforeEach(function () {
	// Ensure baseline: Blog enabled, Shop disabled
	if (file_exists(base_path('modules.json'))) {
		$state = json_decode(file_get_contents(base_path('modules.json')), true) ?: [];
		$changed = false;
		if (($state['Blog'] ?? null) !== true) { $state['Blog'] = true; $changed = true; }
		if (($state['Shop'] ?? null) !== false) { $state['Shop'] = false; $changed = true; }
		if ($changed) {
			file_put_contents(base_path('modules.json'), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		}
	}
});

