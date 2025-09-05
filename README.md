
# Laravel Modules (`neominnthu/laravel-modules`)

[![Latest Stable Version](https://img.shields.io/packagist/v/neominnthu/laravel-modules.svg)](https://packagist.org/packages/neominnthu/laravel-modules)
[![CI](https://github.com/neominnthu/laravel-modules/actions/workflows/ci.yml/badge.svg)](https://github.com/neominnthu/laravel-modules/actions)
[![License](https://img.shields.io/github/license/neominnthu/laravel-modules.svg)](LICENSE)


A professional modular system for Laravel 12 applications. Create, enable, disable, cache, and extend self-contained modules (routes, views, migrations, configs, events, middleware) with artisan generators.

> Status: Core phases 1–4 implemented (discovery, manager, providers, generators, lazy loading, auto middleware & event registration). Upcoming: advanced asset publishing, per-module tests, richer caching strategies.




## Features

- **Module scaffolding**: Quickly create new modules with all necessary structure using `php artisan module:make Blog --api`.
- **Auto discovery & lazy provider registration**: Modules are discovered automatically and can be registered lazily for performance.
- **Enable / disable modules**: Use `module:enable`, `module:disable`, and list with `module:list` to manage modules at runtime.
- **Status and Sync**: Use `module:status` for a summary of enabled/disabled modules, and `module:sync` to reconcile registry with filesystem (add/prune modules).
- **Resource loading**: Seamlessly load routes (web/api/console), views, migrations, and configs per module.
- **Generators**: Artisan commands for controller, model (with migration), event, listener, migration, seeder, factory, test, middleware.
- **Event & middleware auto-discovery**: Automatically register events and middleware defined in modules.
- **Cached manifest**: Fast boot with manifest cache (`bootstrap/cache/modules.php`), supports lazy provider boot.
- **Facade + helper**: Use `Module::call('Blog@method')` and `module_path('Blog')` for easy module interaction.
- **Strict dependency validation**: Prevents loading modules with missing, disabled, or version-incompatible dependencies.
- **Graph visualization**: Output module dependency graph in table, JSON, or DOT format.
- **Diagnostics**: Use `module:doctor` for health checks, dependency chain/cycle/missing path reporting, and version constraint validation. Auto-fix cache issues with `--fix` and sync registry with `--sync`.
- **Version commands**: Use `module:version:show` to list module versions, and `module:version:bump` to increment or set versions.
- **Pest test ready**: Scaffold and run tests for modules using Pest and Testbench.

## Why Use This?

- Modularize large Laravel applications for better maintainability and scalability.
- Clean separation of features, routes, and resources.
- Rapid prototyping with built-in generators.
- Strict dependency management and validation.
- Easy integration with CI and static analysis tools.


## Requirements

- PHP >= 8.3
- Laravel 12.x



## Installation

```bash
composer require neominnthu/laravel-modules
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.


## License

This package is open-sourced software licensed under the [MIT license](LICENSE).



## Quick Start


1. Publish config (optional):

```bash
php artisan module:publish-config
```

To overwrite an existing config file:

```bash
php artisan module:publish-config --force
```

2. Create a module:

```bash
php artisan module:make Blog --api
```

3. List modules:

```bash
php artisan module:list
```

4. Show status summary:

```bash
php artisan module:status
```

5. Sync registry with filesystem:

```bash
php artisan module:sync --enable-new --prune-missing
```

6. Disable / enable:

```bash
php artisan module:disable Blog
php artisan module:enable Blog
```

7. Show module versions:

```bash
php artisan module:version:show
php artisan module:version:show Blog --json
```

8. Bump module version:

```bash
php artisan module:version:bump Blog minor
php artisan module:version:bump Blog 2.0.0
```

9. Generate artifacts inside a module:

```bash
php artisan module:make:controller Blog Post
php artisan module:make:model Blog Post -m
php artisan module:make:event Blog PostPublished
php artisan module:make:listener Blog SendNotification --event=PostPublished
```

10. Run diagnostics and auto-fix:

```bash
php artisan module:doctor
php artisan module:doctor --fix
php artisan module:doctor --sync --enable-new --prune-missing
```

11. Call a module provider method lazily:

```php
Module::call('Blog@someMethod');
```



## Module Version Constraints & Diagnostics

Each module can declare required versions for its dependencies in `module.json`:

```json
{
	"name": "Shop",
	"version": "1.1.0",
	"provider": "Modules\\Shop\\Providers\\ShopServiceProvider",
	"dependencies": ["Blog"],
	"dependency_versions": { "Blog": ">=1.0.0" }
}
```

Run `php artisan module:doctor` to check for missing, disabled, or version-incompatible dependencies. The output will show dependency chains, cycles, and version constraint failures, with actionable paths for debugging.

When enabling or validating modules, the manager will check that all required dependency versions are satisfied. If a dependency does not meet the constraint, an error will be thrown.

Supported constraints: `^1.0.0`, `>=1.0.0`, `=1.2.3`, `<2.0.0`, etc.

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


## Hot Reload (Runtime Enable/Disable)

You can enable or disable modules at runtime without restarting the application. This is achieved by unregistering a module's service provider and related bindings.

**Unregister a provider at runtime:**

```php
app('modules.manager')->unregisterProvider('Blog');
```

This marks the provider as unregistered and optionally clears related bindings, aliases, and events. Re-enable the module and rebuild the cache to restore its provider:

```php
Module::enable('Blog');
app('modules.manager')->buildCache();
```

**Testing hot reload:**

Check if a provider is registered:

```php
$manager = app('modules.manager');
$manager->isProviderRegistered('Modules\\Blog\\Providers\\BlogServiceProvider'); // true or false
```

This feature is useful for development, testing, and advanced runtime scenarios where modules may need to be toggled without a full application restart.

## Roadmap

- Asset publishing tag for per-module assets
- Per-module test generators & discovery
- Command to sync / validate manifests (in progress)
- Module version constraints & dependency graph
- Hot reload / watch mode in dev
- Module dependency graph visualization


## Manifest Sync & Validation

Scan and optionally fix all module manifest files for required fields and schema:

```bash
php artisan module:manifest:sync
```

Add `--fix` to automatically add missing fields and normalize arrays:

```bash
php artisan module:manifest:sync --fix
```

Reports issues and actions taken. Ensures all manifests have `name`, `version`, and `provider` fields, and arrays are normalized.

## Code Coverage Reporting

Run Pest code coverage for all modules or a specific module:

```bash
php artisan module:coverage
php artisan module:coverage Blog
```

Shows coverage report for all modules or just the specified module. Useful for tracking test coverage and quality.

## Cache Status Reporting

Show module cache status and details:

```bash
php artisan module:cache:status
```

Displays cache file path, number of modules cached, last updated time, file size, and lists cached modules.

## Per-Module Test Listing

List all Pest test files in a module:

```bash
php artisan module:test:list Blog
```

Shows all test files in `Modules/Blog/Tests/`.

## Per-Module Test Generator

You can generate Pest test files inside any module using:

```bash
php artisan module:make:test Blog ExampleTest
```

This creates `Modules/Blog/Tests/ExampleTest.php` with a Pest test scaffold. The stub uses your module name and is ready for custom assertions.

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
| module:doctor | Diagnose module system health & mismatches |
| module:version:bump Name {patch\|minor\|major\|X.Y.Z} | Bump or set module version |
| module:graph | Show dependency graph (supports --json, --dot flags) |


## Publishing Module Resources

Each module supports publishing its config, views, and translations using Laravel's vendor:publish command.

- **Per-module tag:** `module-{name}` (e.g., `module-blog`)
- **Global tag:** `modules-resources` (publishes all modules)

To publish all module resources at once:

```bash
php artisan vendor:publish --tag=modules-resources
```

To publish resources for a single module (example for Blog):

```bash
php artisan vendor:publish --tag=module-blog
```

This will publish:

- Config: `Config/config.php` → `config/{name}.php`
- Views: `Resources/views` → `resources/views/vendor/{name}`
- Translations: `Resources/lang` → `resources/lang/vendor/{name}`

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

## Contributing & Community

See `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, and `SECURITY.md` for guidelines, conduct standards, and vulnerability reporting.
