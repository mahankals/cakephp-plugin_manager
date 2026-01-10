# PluginManager for CakePHP 5

[![CakePHP 5](https://img.shields.io/badge/CakePHP-5.x-red.svg)](https://cakephp.org)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A secure plugin manager for CakePHP 5 applications. Discover, enable, disable, and configure plugins through a web dashboard or JSON API.

## Features

- Plugin discovery from `plugins/` directory
- Enable/disable plugins with persistent state
- Plugin configuration management
- Web dashboard interface
- RESTful JSON API
- Security validation for plugin names
- Works with or without CakeSPA

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | >= 8.1 |
| CakePHP | ^5.0 |

**No additional dependencies required.**

## Installation

1. Copy the plugin to `plugins/plugin_manager`

2. Add autoload entry to `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "PluginManager\\": "plugins/plugin_manager/src/"
        }
    }
}
```

3. Regenerate autoloader:

```bash
composer dump-autoload
```

4. Load the plugin in `config/plugins.php`:

```php
return [
    'PluginManager' => [],
];
```

## How It Works

1. **Discovery**: Scans `plugins/` directory for available plugins
2. **Registry**: Maintains plugin state in cache and `config/plugins.local.php`
3. **Dashboard**: Web interface at `/plugin-manager` for management
4. **API**: JSON endpoints for programmatic access

### Web Dashboard

Access at: `https://your-app.local/plugin-manager`

### JSON API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/plugin-manager/list.json` | List all plugins |
| GET | `/plugin-manager/status/{name}.json` | Get plugin status |
| POST | `/plugin-manager/enable/{name}` | Enable plugin |
| POST | `/plugin-manager/disable/{name}` | Disable plugin |
| POST | `/plugin-manager/refresh` | Refresh cache |
| GET | `/plugin-manager/config/{name}` | Plugin configuration |

## Documentation

See the [docs](docs/) folder for detailed documentation:

- [Features](docs/features/) - Feature documentation
- [Development](docs/development/) - Implementation details
- [Bugfixes](docs/bugfixes/) - Bug fix history

## Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## Author

[Atul Mahankal](https://atulmahankal.github.io/atulmahankal/)

## License

MIT License - see [LICENSE](LICENSE) file.
