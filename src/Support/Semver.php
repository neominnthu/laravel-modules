<?php

declare(strict_types=1);

namespace Modules\Support;

/**
 * Semantic version constraint checker for modules.
 */
class Semver
{
    /**
     * Check if a version satisfies a constraint (basic ^, >=, =, <, > support).
     *
     * @param string $version The version string (e.g. "1.2.3").
     * @param string $constraint The constraint string (e.g. ">=1.2.0", "^1.0.0").
     * @return bool True if the version satisfies the constraint.
     */
    public static function satisfies(string $version, string $constraint): bool
    {
        $version = ltrim($version, 'v');
        $constraint = trim($constraint);
        if ($constraint === '' || $constraint === '*') {
            return true;
        }
        if (preg_match('/^\^([\d\.]+)$/', $constraint, $m)) {
            return version_compare($version, $m[1], ">=");
        }
        if (preg_match('/^>=([\d\.]+)$/', $constraint, $m)) {
            return version_compare($version, $m[1], ">=");
        }
        if (preg_match('/^>([\d\.]+)$/', $constraint, $m)) {
            return version_compare($version, $m[1], ">=") && $version !== $m[1];
        }
        if (preg_match('/^<=([\d\.]+)$/', $constraint, $m)) {
            return version_compare($version, $m[1], "<=");
        }
        if (preg_match('/^<([\d\.]+)$/', $constraint, $m)) {
            return version_compare($version, $m[1], "<");
        }
        if (preg_match('/^=([\d\.]+)$/', $constraint, $m)) {
            return version_compare($version, $m[1], "==");
        }
        // Exact match fallback
        return version_compare($version, $constraint, "==");
    }
}
