# Changelog

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

### Added
- Initial release of PluginManager for CakePHP 5
- Plugin discovery service for scanning plugin directories
- Plugin registry service for state management
- Plugin loader service as high-level facade
- RESTful API endpoints for plugin management
- Security validation for plugin names (prevents directory traversal, injection attacks)
- Caching support with configurable cache backends
- Support for `plugins.local.php` configuration file
- Comprehensive test suite with security tests
- Service registration via CakePHP DI container
- Web dashboard template (`templates/Plugins/index.php`)
- JSON API support with `.json` extension
- `isJsonRequest()` helper method for content type detection
- Comprehensive README documentation
- Troubleshooting section in documentation
- Auto-dismiss flash messages after 3 seconds with fade-out animation
- Flash messages pause dismissal timer on hover, resume on mouse leave

### Changed
- **Standalone Compatibility**: Plugin extends `Cake\Controller\Controller` directly instead of `App\Controller\AppController`
- Plugin works with base CakePHP 5 without any additional dependencies
- Compatible with or without CakeSPA plugin
- Only scans `plugins/` directory (vendor plugins not listed)

### Fixed
- Test bootstrap properly configures cache for standalone testing
- Removed duplicate route definitions (routes now only in `config/routes.php`)
- PluginRegistryService gracefully handles missing cache configurations
- PluginDiscoveryService properly detects plugin classes with PascalCase names

### Security
- Plugin name validation prevents directory traversal attacks
- POST-only mutations for enable/disable/refresh actions
- No fallback routes - explicit route definitions only
- Input sanitization and validation throughout

---

## Planned

- CLI commands for plugin management
- Plugin dependency resolution
- Plugin update notifications
- Backup/restore plugin configurations
