# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned

- Asset publishing refinements
- Per-module test generators

### Added (Unreleased)

- Middleware generator command (`module:make:middleware`)
- Eager + lazy tests for middleware alias & event listener parity
- ModuleManager::enabledModules() convenience method
- Verbose flag for module:list showing dependencies & lazy/eager mode

### Changed (Unreleased)

- Documentation: added lazy auto-registration section, middleware generator usage details, verbose list command

### Fixed (Unreleased)

- (placeholder)

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

### Security

- None
