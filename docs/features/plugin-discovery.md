# Plugin Discovery

## Overview

PluginManager automatically discovers plugins in the `plugins/` directory.

## Discovery Process

1. Scans configured plugin paths (default: `plugins/`)
2. Reads `composer.json` for plugin metadata
3. Detects plugin class (Plugin.php or {Name}Plugin.php)
4. Extracts version and description

## Plugin Detection

A directory is recognized as a plugin if it has:

- `src/Plugin.php` OR
- `src/{PascalCaseName}Plugin.php`

## Metadata Extraction

From `composer.json`:
- `version` - Plugin version
- `description` - Plugin description

## Caching

Discovery results are cached for performance. Use the "Refresh Plugin Cache" button or API to update.

## API

```php
$discovery = new PluginDiscoveryService();

// Discover all plugins
$plugins = $discovery->discover();

// Check if plugin exists
$exists = $discovery->exists('MyPlugin');

// Get loaded plugins
$loaded = $discovery->getLoadedPlugins();

// Clear cache
$discovery->clearCache();
```
