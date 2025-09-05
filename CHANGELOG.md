# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).



## [1.1.0] - 2025-09-05

### Added

- `module:status` command for summary of enabled/disabled modules (table/JSON)
- `module:sync` command to reconcile registry with filesystem (add/prune modules)
- `module:doctor` extended with `--fix` (auto cache rebuild) and `--sync` (registry sync), plus `--enable-new` and `--prune-missing` options
- Dependency chain/cycle/missing path reporting in doctor diagnostics
- Version constraint validation in doctor diagnostics (with actionable output and exit code)
- `module:version:show` command for listing module versions (table/JSON)
- Registry mutation API (`addToRegistry`, `pruneFromRegistry`) for safe programmatic registry changes

### Changed

- README updated for new commands, diagnostics, and version constraint features
- Doctor command now returns exit code 1 for version constraint failures

### Fixed

- Reflection removed from sync command (uses new registry API)
- Improved diagnostics output for dependency chains and version constraints

### Documentation

- README and docs updated for all new features, commands, and diagnostics

## [1.0.0] - 2025-08-29

### Features

- Modular architecture for Laravel 12
- Module discovery, enable/disable, registry, and manifest management
- Lazy/eager provider registration with parity for middleware/events
- Generators: module, controller, model (+migration), event, listener, migration, seeder, factory, test, middleware
- Dependency validation, cycle detection, and graph visualization (table, JSON, DOT)
- Helper functions and facade for DX
- Module cache build/clear commands
- Dedicated exception class for manifest errors
- CI workflow (tests, static analysis, code style)
- Comprehensive documentation and meta files
- Full test suite for core, helpers, error scenarios, and commands

### Documentation

- README and docs updated for all features, commands, and CI

### Security

- Manifest validation and strict dependency enforcement

## [0.2.0] - 2025-08-29

### Added (0.2.0)

- Module dependency graph command (`module:graph`) with JSON & DOT output
- Migration / Seeder / Factory / Test generator commands
- Autoload factories configuration (`autoload_factories`)
- Per-module publish tags (`module-{name}`) and aggregated `modules-resources`
- Helper functions: `module_enabled`, `module_call`, `module_version`, `module_manifest`, `modules_manager`
- Negative-case helper tests & additional documentation sections (factories, graph visualization, helpers)
- Lazy auto-registration parity for middleware & event listeners (`lazy_auto_register`)
- Sample event, listener, middleware in Blog module with tests

### Changed (0.2.0)

- README: commands table, graph visualization, helper functions, factories auto-load docs

### Fixed

- Improved graph command output options

## [0.1.0] - 2025-08-29

### Added

- Core module discovery & manifest management
- ModuleManager facade & helper
- Blog sample module scaffold
- Generators: module, controller, model (+migration), event, listener
- Enable/disable/list/cache/validate artisan commands
- Lazy provider registration & Module::call indirection
- Auto middleware aliasing & event listener registration
- Dependency declaration & validation (strict filtering)
- README, configuration file, stubs, initial tests

### Changed

- README expanded with usage, configuration, roadmap

#### Security (legacy)

- None
