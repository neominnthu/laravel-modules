<?php

declare(strict_types=1);

namespace Modules\Support;

/**
 * Semantic version constraint checker for modules.
 */
class Semver
{
    /**
     * Check if a version satisfies a (possibly composite) constraint.
     * Supported operators: ^, ~, >=, >, <=, <, = (or no operator for exact)
     * Composite forms:
     *  - AND: separate constraints by a single space (e.g. ">=1.2.0 <2.0.0")
     *  - OR: use the pipe symbol (e.g. "^1.0 | ^2.0")
     */
    public static function satisfies(string $version, string $constraint): bool
    {
        $version = ltrim($version, 'v');
        $constraint = trim($constraint);
        if ($constraint === '' || $constraint === '*') {
            return true;
        }

        // OR groups
        foreach (preg_split('/\s*\|\|?\s*/', $constraint) as $group) {
            if ($group === '') {
                continue;
            }
            $all = true;
            // AND parts (space separated)
            foreach (preg_split('/\s+/', trim($group)) as $part) {
                if ($part === '') {
                    continue;
                }
                if (! self::satisfiesSingle($version, $part)) {
                    $all = false;
                    break;
                }
            }
            if ($all) {
                return true;
            }
        }
        return false;
    }

    /**
     * Evaluate a single non-composite constraint part.
     */
    protected static function satisfiesSingle(string $version, string $part): bool
    {
        $part = trim($part);
        if ($part === '' || $part === '*') {
            return true;
        }

        // Caret ^X.Y.Z -> >=X.Y.Z and <(X+1).0.0
        if (preg_match('/^\^([0-9]+)\.([0-9]+)\.([0-9]+)$/', $part, $m)) {
            $min = sprintf('%d.%d.%d', $m[1], $m[2], $m[3]);
            $nextMajor = ((int) $m[1]) + 1;
            $max = sprintf('%d.0.0', $nextMajor);
            return version_compare($version, $min, '>=') && version_compare($version, $max, '<');
        }
        // Tilde ~X.Y.Z -> >=X.Y.Z and <X.(Y+1).0
        if (preg_match('/^~([0-9]+)\.([0-9]+)\.([0-9]+)$/', $part, $m)) {
            $min = sprintf('%d.%d.%d', $m[1], $m[2], $m[3]);
            $nextMinor = ((int) $m[2]) + 1;
            $max = sprintf('%d.%d.0', $m[1], $nextMinor);
            return version_compare($version, $min, '>=') && version_compare($version, $max, '<');
        }
        if (preg_match('/^>=([\d\.]+)$/', $part, $m)) {
            return version_compare($version, $m[1], '>=');
        }
        if (preg_match('/^>([\d\.]+)$/', $part, $m)) {
            return version_compare($version, $m[1], '>');
        }
        if (preg_match('/^<=([\d\.]+)$/', $part, $m)) {
            return version_compare($version, $m[1], '<=');
        }
        if (preg_match('/^<([\d\.]+)$/', $part, $m)) {
            return version_compare($version, $m[1], '<');
        }
        if (preg_match('/^=([\d\.]+)$/', $part, $m)) {
            return version_compare($version, $m[1], '==');
        }
        // Bare version -> exact
        if (preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $part)) {
            return version_compare($version, $part, '==');
        }
        // Fallback: attempt exact
        return version_compare($version, $part, '==');
    }
}
