<?php

declare(strict_types=1);

namespace Modules\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

/**
 * Represents a single module's manifest (module.json) file.
 */
class ModuleManifest implements Arrayable
{
    public const MANIFEST_FILENAME = 'module.json';

    /** @var array<string,mixed> */
    protected array $data = [];

    public function __construct(
        protected readonly string $basePath,
        protected readonly Filesystem $files = new Filesystem()
    ) {
        $this->load();
    }

    /**
     * Load and decode the manifest file.
     */
    protected function load(): void
    {
        $path = $this->path();
        if (! $this->files->exists($path)) {
            throw new InvalidArgumentException("Module manifest not found at {$path}");
        }
        $json = $this->files->get($path);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Invalid module manifest structure.');
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
