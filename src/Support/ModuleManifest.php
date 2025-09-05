<?php

declare(strict_types=1);

namespace Modules\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

class ModuleManifestException extends \RuntimeException {}

/**
 * Represents a single module's manifest (module.json) file.
 */
/**
 * Represents a single module's manifest (module.json) file.
 * Handles manifest loading, dependency/version parsing, and array conversion.
 *
 * @implements Arrayable<string, mixed>
 */
class ModuleManifest implements Arrayable
{
    public const MANIFEST_FILENAME = 'module.json';

    /** @var array<string,mixed> */
    protected array $data = [];

    /**
     * ModuleManifest constructor.
     *
     * @param string $basePath Path to the module directory.
     * @param Filesystem $files Filesystem instance (default: new Filesystem).
     */
    public function __construct(
        protected readonly string $basePath,
        protected readonly Filesystem $files = new Filesystem()
    ) {
        $this->load();
    }
    /**
     * Declared dependency version constraints (array: module => constraint)
     * @return array<string,string>
     */
    public function dependencyVersions(): array
    {
        $depVers = $this->data['dependency_versions'] ?? [];
        return is_array($depVers) ? $depVers : [];
    }
    /**
     * Write manifest data to file with checksum.
     */
    public static function writeWithChecksum(string $path, array $data, Filesystem $files = null): void
    {
    $start = microtime(true);
    $files = $files ?: new Filesystem();
    $copy = $data;
    $copy['checksum'] = self::calculateChecksum($copy);
    $files->put($path, json_encode($copy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $duration = microtime(true) - $start;
    Log::debug('[PERF] ModuleManifest::writeWithChecksum(' . $path . '): ' . number_format($duration, 4) . 's');
    }
    /**
     * Load and decode the manifest file.
     */
    protected function load(): void
    {
        $start = microtime(true);
    $path = $this->path();
        if (! $this->files->exists($path)) {
            $duration = microtime(true) - $start;
            Log::debug('[PERF] ModuleManifest::load (not found): ' . number_format($duration, 4) . 's');
            throw new ModuleManifestException("Module manifest not found at {$path}");
        }
        $json = $this->files->get($path);
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $json);
        try {
            $decoded = json_decode($sanitized, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            usleep(5000);
            $fresh = $this->files->get($path);
            $freshSanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $fresh);
            try {
                $decoded = json_decode($freshSanitized, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e2) {
                Log::error('[MANIFEST ERROR]', [
                    'path' => $path,
                    'exception' => $e2->getMessage(),
                ]);
                $duration = microtime(true) - $start;
                Log::debug('[PERF] ModuleManifest::load (decode fail): ' . number_format($duration, 4) . 's');
                throw new ModuleManifestException("Failed to decode manifest at $path: " . $e2->getMessage());
            }
        }
        if (! is_array($decoded)) {
            Log::error('[MANIFEST ERROR] Invalid structure', [
                'path' => $path,
                'decoded' => $decoded,
            ]);
            $duration = microtime(true) - $start;
            Log::debug('[PERF] ModuleManifest::load (invalid structure): ' . number_format($duration, 4) . 's');
            throw new ModuleManifestException('Invalid module manifest structure.');
        }
        foreach (['name','version'] as $required) {
            if (! array_key_exists($required, $decoded)) {
                $duration = microtime(true) - $start;
                Log::debug('[PERF] ModuleManifest::load (missing field): ' . number_format($duration, 4) . 's');
                throw new ModuleManifestException("Manifest missing required field '$required' at $path");
            }
        }
        if (isset($decoded['dependencies']) && ! is_array($decoded['dependencies'])) {
            $duration = microtime(true) - $start;
            Log::debug('[PERF] ModuleManifest::load (bad dependencies): ' . number_format($duration, 4) . 's');
            throw new ModuleManifestException("Field 'dependencies' must be array at $path");
        }
        if (isset($decoded['dependency_versions']) && ! is_array($decoded['dependency_versions'])) {
            $duration = microtime(true) - $start;
            Log::debug('[PERF] ModuleManifest::load (bad dependency_versions): ' . number_format($duration, 4) . 's');
            throw new ModuleManifestException("Field 'dependency_versions' must be object map at $path");
        }
        if (isset($decoded['checksum'])) {
            $expected = $decoded['checksum'];
            $actual = self::calculateChecksum($decoded);
            if ($expected !== $actual) {
                $duration = microtime(true) - $start;
                Log::debug('[PERF] ModuleManifest::load (checksum mismatch): ' . number_format($duration, 4) . 's');
                throw new ModuleManifestException("Manifest checksum mismatch at $path");
            }
        }
    $this->data = $decoded;
    $duration = microtime(true) - $start;
    Log::debug('[PERF] ModuleManifest::load: ' . number_format($duration, 4) . 's');
    }

    /**
     * Calculate checksum for manifest data (excluding checksum field itself).
     */
    public static function calculateChecksum(array $data): string
    {
        $copy = $data;
        unset($copy['checksum']);
    return hash('sha256', json_encode($copy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Verify manifest checksum.
     */
    public function verifyChecksum(): bool
    {
        if (!isset($this->data['checksum'])) return false;
        return $this->data['checksum'] === self::calculateChecksum($this->data);
    }

    /**
     * Get the path to the manifest file.
     */
    public function path(): string
    {
        return rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::MANIFEST_FILENAME;
    }

    /**
     * Get a value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Module name (Required).
     */
    public function name(): string
    {
        return (string) ($this->data['name'] ?? basename($this->basePath));
    }

    /**
     * Get version (defaults to 1.0.0).
     */
    public function version(): string
    {
        return (string) ($this->data['version'] ?? '1.0.0');
    }

    /**
     * Service provider FQCN.
     */
    public function provider(): ?string
    {
        return isset($this->data['provider']) ? (string) $this->data['provider'] : null;
    }

    /**
     * Declared dependencies (array of module names)
     * @return string[]
     */
    public function dependencies(): array
    {
        $deps = $this->data['dependencies'] ?? [];
        return array_values(array_filter(array_map('strval', is_array($deps) ? $deps : [])));
    }

    /**
     * Raw array representation.
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
