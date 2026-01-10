# Architecture

## Overview

PluginManager uses a service-based architecture with three main components:

```
PluginManager/
├── Controller/
│   ├── AppController.php      # Base controller (standalone)
│   └── PluginsController.php  # Main API controller
├── Service/
│   ├── PluginDiscoveryService.php   # Plugin discovery
│   ├── PluginLoaderService.php      # High-level facade
│   └── PluginRegistryService.php    # State management
└── PluginManagerPlugin.php    # Plugin bootstrap
```

## Services

### PluginDiscoveryService

Discovers available plugins in the `plugins/` directory.

```php
$discovery = new PluginDiscoveryService();
$plugins = $discovery->discover();
```

### PluginRegistryService

Manages plugin state (enabled/disabled) with caching.

```php
$registry = new PluginRegistryService();
$registry->enable('MyPlugin');
$registry->disable('MyPlugin');
```

### PluginLoaderService

High-level service combining discovery and registry.

```php
$loader = new PluginLoaderService($discovery, $registry);
$plugins = $loader->listPlugins();
$loader->enablePlugin('MyPlugin');
```

## Standalone Design

The plugin extends `Cake\Controller\Controller` directly instead of `App\Controller\AppController`, making it work with base CakePHP 5 without dependencies.

## CakeSPA Compatibility

The controller detects AJAX requests and disables layout automatically:

```php
public function beforeRender(EventInterface $event): ?Response
{
    if ($this->request->is('ajax')) {
        $this->viewBuilder()->disableAutoLayout();
    }
    return null;
}
```
