<?php

declare(strict_types=1);

namespace Modules\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

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
     * Load and decode the manifest file.
     */
    protected function load(): void
    {
        $path = $this->path();
        if (! $this->files->exists($path)) {
            throw new ModuleManifestException("Module manifest not found at {$path}");
        }
        $json = $this->files->get($path);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new ModuleManifestException('Invalid module manifest structure.');
        }
        $this->data = $decoded;
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
