# Plugin Configuration

## Overview

PluginManager allows viewing and editing plugin configuration through the web dashboard.

## Configuration Storage

Configuration is saved to `config/app_local.php` under the plugin name.

## Accessing Configuration

```php
use Cake\Core\Configure;

// Read plugin config
$value = Configure::read('PluginName.settingKey');

// Read all plugin config
$config = Configure::read('PluginName');
```

## Supported Value Types

- **String**: `my value`
- **Integer**: `123`
- **Float**: `3.14`
- **Boolean**: `true`, `false`
- **Null**: `null`
- **JSON**: `["a","b"]`, `{"key":"value"}`

## DbConfig Integration

If the DbConfig plugin is loaded, configuration is also saved to the database automatically.

## Dashboard

The Config button appears for plugins that:
1. Have existing configuration
2. Are loaded or enabled

Configuration is displayed as cards with key as title and value as editable input.
