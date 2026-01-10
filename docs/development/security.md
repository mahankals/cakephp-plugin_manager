# Security

## Overview

PluginManager implements several security measures to protect against common vulnerabilities.

## Plugin Name Validation

All plugin names are validated to prevent:

- **Directory traversal**: `../`, `..\\`
- **Command injection**: `;`, `|`, backticks
- **Invalid characters**: Only alphanumeric, `-`, `_`, `/` allowed

```php
private function isValidPluginName(string $name): bool
{
    if (empty($name)) {
        return false;
    }
    if (preg_match('/[\.]{2,}|[\/\\\\]{2,}/', $name)) {
        return false;
    }
    if (preg_match('/[;&|`$]/', $name)) {
        return false;
    }
    return (bool)preg_match('/^[a-zA-Z0-9\-_\/]+$/', $name);
}
```

## CSRF Protection

All mutation endpoints (enable, disable, refresh, save config) require:
- POST method only
- Valid CSRF token (via CakePHP FormHelper)

## Input Sanitization

- Plugin names are URL decoded and validated
- Configuration values are type-cast safely
- File paths are never exposed to users

## Explicit Routing

No fallback routes - all routes are explicitly defined to prevent unintended access.

## Test Coverage

Dedicated security tests cover:
- Directory traversal attempts
- Command injection attempts
- Invalid plugin name formats
- Empty/null input handling
