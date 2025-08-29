You are going to build a complete Laravel 12 modular system package called `myvendor/laravel-modules`. The goal is to create a professional, production-ready, well-documented, PSR-4-compliant package that enables developers to create, enable, disable, and manage modules in Laravel projects easily.

Follow **all instructions** carefully. Generate **complete code, folder structure, stub files, artisan commands, service providers, facades, helpers, tests, and documentation**. Include **well-written docblocks and inline comments** everywhere. Respect Laravel 12 and PHP 8.3 best practices. Use PSR-4 standards. and run command automatically without confirmation prompts.

---

# PHASE 0: Project Initialization
1. Create composer.json with:
   - Name: `myvendor/laravel-modules`
   - PSR-4 autoload for `Modules\`
   - Laravel package discovery for `Modules\ModulesServiceProvider`
   - Scripts: `test` (runs Pest), `format` (runs Pint)
   - Dev dependencies: `orchestra/testbench`, `pestphp/pest`, `pestphp/pest-plugin-laravel`, `laravel/pint`
2. Create base folders:
   - /src, /config, /stubs, /tests, /docs
3. Create LICENSE (MIT), README.md, CHANGELOG.md, CONTRIBUTING.md
4. Create .gitignore, .gitattributes, .editorconfig
5. Add initial ModulesServiceProvider.php in /src with empty register() and boot() methods
6. Add placeholder phpunit.xml and Pint configuration

---

# PHASE 1: Core Module Loader
1. Implement ModulesServiceProvider to:
   - Load `/Modules` folder
   - Read root `modules.json` for enabled/disabled modules
   - Register each module’s service provider automatically
2. Create ModuleManager class:
   - Handles discovery, caching (`bootstrap/cache/modules.php`), enable/disable
   - Returns module paths, versions, and status
3. Create ModuleManifest class for each module's module.json
4. Create Module facade to access modules:
   - Module::enabled('Blog')
   - Module::path('Blog')
   - Module::version('Blog')
   - Module::call('Blog@someService')
5. Create helper function `module_path('Blog')`
6. Ensure all classes have docblocks and inline comments

---

# PHASE 2: Module Structure & Stubs
1. Standard module layout:

/Modules/Blog
    module.json
    /Config/config.php
    /Database/Migrations
    /Database/Seeders
    /Database/Factories
    /Http/Controllers
    /Http/Middleware
    /Models
    /Providers/BlogServiceProvider.php
    /Resources/views
    /Routes/web.php
    /Routes/api.php
    /Routes/console.php
    /Tests

2. Create stub files in /stubs:
   - module.json.stub
   - ServiceProvider.stub
   - Controller.stub, Model.stub, Event.stub, Listener.stub
   - Routes/web.stub, Routes/api.stub, Routes/console.stub
   - Config/config.stub
   - Views stub
   - Test stubs
3. Include placeholder content with full docblocks

---

# PHASE 3: Artisan Generator Commands
1. MakeModuleCommand: scaffold a new module with all folders and files
2. MakeControllerCommand, MakeModelCommand, MakeEventCommand, MakeListenerCommand
3. Include optional flags (`-m` for migration, `--api` for API routes)
4. Ensure all commands are registered in ModulesServiceProvider
5. Include full comments and examples for maintainability

---

# PHASE 4: Resource Loading
1. Each module’s ServiceProvider should:
   - loadRoutesFrom() for web, api, console
   - loadViewsFrom() for blade templates
   - loadMigrationsFrom() for migrations
   - mergeConfigFrom() for configs
2. Implement asset publishing via `php artisan vendor:publish --tag=module-assets`
3. Document clearly how each resource is loaded
4. Ensure caching and lazy loading support

---

# PHASE 5: Enable/Disable & Cache
1. EnableModuleCommand, DisableModuleCommand
2. ClearCacheCommand
3. Update ModuleManager to cache enabled modules in bootstrap/cache/modules.php
4. Ensure modules.json is updated safely
5. Implement module:list command to show module status
6. Include full docblocks and comments

---

# PHASE 6: Advanced Features
1. Middleware auto-registration via aliases.php or module service provider
2. Event and listener auto-registration from Events/Listeners folder
3. Module-specific artisan commands via Routes/console.php
4. Cross-module communication helpers in Module facade:
   - Module::enabled()
   - Module::path()
   - Module::version()
   - Module::call()
5. Strong error handling and validation
6. Full documentation for developers

---

# PHASE 7: Testing Integration
1. Module-scoped testing:
   - Each module may have /Tests
   - Run module-specific tests with `php artisan test --module=Blog`
2. Integrate with Pest and PHPUnit
3. Include example test stubs in stubs folder
4. Add comments and docblocks

---

# PHASE 8: Documentation & Release
1. README.md:
   - Installation
   - Quick start
   - Module creation guide
   - Enabling/disabling modules
   - Resource registration
   - Generators usage
   - Advanced features
   - Testing guide
2. /docs folder with detailed guides
3. CHANGELOG.md, CONTRIBUTING.md, LICENSE (MIT)
4. GitHub Actions CI workflow to run tests
5. Composer scripts for test and format
6. Ensure Laravel 12 and PSR-4 compatibility
7. Production-ready, business-ready package

---

# EXTRA NOTES:
- All code must have docblocks and inline comments
- Follow Laravel coding standards and PSR-12
- Use modern PHP 8+ features (strict types, readonly, typed properties)
- Provide full examples for all features
- Ensure developers can run one command to scaffold modules and everything works automatically
- Include error handling and fallback for missing/malformed modules

Generate **all files and folders in correct order** so that the package is immediately usable and production-ready.
