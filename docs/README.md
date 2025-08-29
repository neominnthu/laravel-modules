# Documentation


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

Generate Pest test files inside a module:

```bash
php artisan module:make:test Blog ExampleTest
```

This creates `Modules/Blog/Tests/ExampleTest.php` with a Pest test scaffold. The stub uses your module name and is ready for custom assertions.

The modular system supports hot reload: you can enable or disable modules at runtime without restarting the application. This is achieved by unregistering a module's service provider and related bindings.

**Unregister a provider at runtime:**

```php
app('modules.manager')->unregisterProvider('Blog');
```

This will mark the provider as unregistered and optionally clear related bindings, aliases, and events. You can re-enable the module and rebuild the cache to restore its provider:

```php
Module::enable('Blog');
app('modules.manager')->buildCache();
```

**Testing hot reload:**

You can check if a provider is registered:

```php
$manager = app('modules.manager');
$manager->isProviderRegistered('Modules\\Blog\\Providers\\BlogServiceProvider'); // true or false
```

This feature is useful for development, testing, and advanced runtime scenarios where modules may need to be toggled without a full application restart.
This folder hosts extended documentation. Planned sections:

- Architecture Overview
- Module Lifecycle & Lazy Loading
- Generators & Stubs
- Dependency Graph & Validation
- Caching Strategy
- Testing Strategy
- Release & Versioning Policy


## Asset Publishing

Each module can publish its config, views, and translations using Laravel's vendor:publish command.

**Tags:**
- Per-module: `module-{name}` (e.g., `module-blog`)
- Global: `modules-resources` (all modules)

**Published paths:**
- Config: `Config/config.php` → `config/{name}.php`
- Views: `Resources/views` → `resources/views/vendor/{name}`
- Translations: `Resources/lang` → `resources/lang/vendor/{name}`

**Usage:**

Publish all module resources:
```bash
php artisan vendor:publish --tag=modules-resources
```

Publish a single module's resources:
```bash
php artisan vendor:publish --tag=module-blog
```
