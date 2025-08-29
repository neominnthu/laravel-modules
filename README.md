# Laravel Modules (myvendor/laravel-modules)

A professional modular system for Laravel 12 applications. Create, enable, disable, cache and extend self‑contained modules (routes, views, migrations, configs, events, middleware) with artisan generators.

> Status: Core phases 1–4 implemented (discovery, manager, providers, generators, lazy loading, auto middleware & event registration). Upcoming: advanced asset publishing, per-module tests, richer caching strategies.

## Features

- Module scaffolding: `php artisan module:make Blog --api`
- Auto discovery & (optional lazy) provider registration
- Enable / disable modules: `module:enable`, `module:disable`, list with `module:list`
- Resource loading (routes web/api/console, views, migrations, configs)
- Generators: controller, model (with `-m` migration), event, listener
- Additional generators: migration, seeder, factory, test, middleware
- Event & middleware auto-discovery
- Cached manifest (`bootstrap/cache/modules.php`) with optional lazy provider boot
- Facade + helper (`Module::call('Blog@method')`, `module_path('Blog')`)
- Pest test ready (scaffold placeholder)

## Requirements

- PHP >= 8.3
- Laravel 12.x

## Installation (Once Published)

```bash
composer require myvendor/laravel-modules
```

## Quick Start

1. Publish config (optional):

```bash
php artisan vendor:publish --tag=modules-config
```

1. Create a module:

```bash
php artisan module:make Blog --api
```

1. List modules:


```bash
php artisan module:list
```

1. Disable / enable:

```bash
php artisan module:disable Blog
php artisan module:enable Blog
```

1. Generate artifacts inside a module:

```bash
php artisan module:make:controller Blog Post
php artisan module:make:model Blog Post -m
php artisan module:make:event Blog PostPublished
php artisan module:make:listener Blog SendNotification --event=PostPublished
```

1. Call a module provider method lazily:

```php
Module::call('Blog@someMethod');
```

## Configuration

`config/modules.php` options:

- `cache` (bool): write & reuse manifest cache file.
- `lazy` (bool): defer provider registration until first `Module::call()`.
- `strict_dependencies` (bool): skip registering modules whose declared dependencies are missing or disabled.
- `autoload_factories` (bool): auto-load factories from each enabled module.
- `lazy_auto_register` (bool): when lazy mode, also auto-register middleware & listeners on first provider call.

## Caching

Rebuild module cache after structural changes:

```bash
php artisan module:cache:clear
```

Build the cache file explicitly:

```bash
php artisan module:cache
```

The cache file is stored at `bootstrap/cache/modules.php` and is safe to commit ignore.

## Continuous Integration

This package includes a GitHub Actions CI workflow that runs static analysis, tests, and code style checks on every push and pull request to `main`.

## Lazy Loading

When `lazy` = true, providers are not registered at package boot. First `Module::call()` or an explicit manual registration will register the provider then.

### Lazy Auto Registration

With `lazy_auto_register` enabled (default) the first `Module::call()` also performs the same lightweight side-effects as eager boot:

- Registers middleware aliases discovered under `Http/Middleware/*Middleware.php`
- Registers event listeners under `Listeners/` by reflecting the first `handle()` parameter type
- Loads factories if `autoload_factories` is true

Example:

```php
// config('modules.lazy') true; Blog module enabled
\Modules\Blog\Listeners\RecordPing::$records = [];
Module::call('Blog@testPing'); // triggers provider registration & auto-registration

// Listener recorded event origin 'call'
assert(in_array('call', \Modules\Blog\Listeners\RecordPing::$records, true));

// Middleware alias now available
$aliasExists = array_key_exists('blogsample', app('router')->getMiddleware());
```

Disable this behavior by setting `lazy_auto_register` to false if you prefer explicit manual wiring after lazy registration.

## Middleware & Events

Middleware classes ending with `Middleware` under `Modules/Name/Http/Middleware` are auto-aliased (basename without `Middleware`). Listeners with a `handle()` method type-hinted event are automatically wired.

## Module Structure (Generated)

```text
Modules/Blog/
	module.json
	Providers/BlogServiceProvider.php
	Routes/{web,api,console}.php
	Resources/views/index.blade.php
	Config/config.php
	Database/{Migrations,Seeders,Factories}
	Http/{Controllers,Middleware}
	Models/
	Events/ (optional)
	Listeners/ (optional)
```

## Dependencies

Declare dependencies in a module `module.json`:

```json
{
	"name": "Shop",
	"version": "1.1.0",
	"provider": "Modules\\Shop\\Providers\\ShopServiceProvider",
	"dependencies": ["Blog"]
}
```

When `strict_dependencies` is true (default) the module will be skipped if any listed dependency is missing or disabled. Run validation:

```bash
php artisan module:validate
```

## Calling Methods on Module Providers

Invoke public methods on a module's service provider using the `Module::call()` helper with `Module@method` syntax:

```php
use Modules\\Facades\\Module;

$status = Module::call('Blog@testPing'); // 'ok'
```

If you enable the `Shop` module (which depends on `Blog`) you can call its provider method as well:

```php
Module::enable('Shop');
app('modules.manager')->buildCache(); // rebuild module cache after enabling
$shopStatus = Module::call('Shop@testPing'); // 'shop-ok'
```

When a module is disabled (e.g. `Shop` initially) `Module::call('Shop@testPing')` returns `null` silently.

## Relaxing Dependency Strictness

To temporarily allow a module to load even with unmet dependencies you can disable strict mode at runtime (useful in tests):

```php
config(['modules.strict_dependencies' => false]);
app('modules.manager')->buildCache();
```

Recommended: keep `strict_dependencies` enabled in production for safety.

## Roadmap

- Asset publishing tag for per-module assets
- Per-module test generators & discovery
- Command to sync / validate manifests (in progress)
- Module version constraints & dependency graph
- Hot reload / watch mode in dev
- Module dependency graph visualization

## Commands Overview

| Command | Description |
|---------|-------------|
| module:make Name | Scaffold a new module |
| module:list | List discovered modules and status |
| module:list --verbose | List modules with dependencies & registration mode |
| module:enable Name / module:disable Name | Toggle module state |
| module:cache:clear | Rebuild module cache file |
| module:cache | Build the cache file explicitly |
| module:make:controller Module Name | Generate controller inside module |
| module:make:model Module Name -m | Generate model (and optional migration) |
| module:make:event Module EventName | Generate event class |
| module:make:listener Module ListenerName --event=EventName | Generate listener |
| module:make:middleware Module SomeName | Generate middleware (adds Middleware suffix automatically if absent) |
| module:make:migration Module create_users_table --create=users | Create a create-table migration |
| module:make:seeder Module Sample | Create a seeder class |
| module:make:factory Module Post --model=Post | Create a factory targeting a model |
| module:make:test Module ExampleTest | Create a Pest test file inside module |
| module:validate | Validate dependency integrity |
| module:graph | Show dependency graph (supports --json, --dot flags) |

## Publishing Module Resources

Each module automatically registers a publish tag `module-{name}` (lowercased). You can publish all module resources at once:

```bash
php artisan vendor:publish --tag=modules-resources
```

Or a single module (example for Blog):

```bash
php artisan vendor:publish --tag=module-blog
```

## Auto-loading Factories

When `autoload_factories` is true (default) any `Database/Factories` directory inside an enabled module is automatically registered so Laravel can discover its model factories without manual includes.

## Graph Visualization

Table view:

```bash
php artisan module:graph
```

JSON view:

```bash
php artisan module:graph --json
```

Graphviz DOT (pipe to dot to render an image):

```bash
php artisan module:graph --dot | dot -Tpng -o modules.png
```

## Helper Functions

These global helpers are available once the package is loaded:

| Helper | Description |
|--------|-------------|
| module_path(name) | Absolute path to module or null |
| module_enabled(name) | Whether module is enabled |
| module_call("Name@method", params=[]) | Invoke provider method (null if unavailable) |
| module_version(name) | Returns module version string or null |
| module_manifest(name) | Raw manifest array |
| modules_manager() | Underlying ModuleManager instance |
| modules_enabled() | Array of enabled module names |

## License

MIT. See `LICENSE`.

## Contributing & Community

See `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, and `SECURITY.md` for guidelines, conduct standards, and vulnerability reporting.
