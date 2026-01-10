<?php

declare(strict_types=1);

namespace PluginManager\Service;

use Cake\Cache\Cache;
use Cake\Core\Configure;

/**
 * Service for managing plugin state and configuration.
 *
 * Handles persistence of plugin enabled/disabled state
 * and plugin-specific configuration.
 */
class PluginRegistryService
{
    /**
     * @var string Cache key prefix
     */
    private const CACHE_PREFIX = 'plugin_manager_';

    /**
     * @var string|null Cache configuration to use (null if cache unavailable)
     */
    private ?string $cacheConfig = null;

    /**
     * @var array<string, array<string, mixed>> In-memory registry
     */
    private array $registry = [];

    /**
     * @var bool Whether registry has been loaded
     */
    private bool $loaded = false;

    /**
     * Constructor.
     *
     * @param string|null $cacheConfig Cache configuration to use
     */
    public function __construct(?string $cacheConfig = null)
    {
        $this->cacheConfig = $this->resolveCache($cacheConfig);
    }

    /**
     * Resolve available cache configuration.
     *
     * @param string|null $preferred Preferred cache config name
     * @return string|null Available cache config or null if none available
     */
    private function resolveCache(?string $preferred): ?string
    {
        // Check preferred config first
        if ($preferred !== null && Cache::getConfig($preferred) !== null) {
            return $preferred;
        }

        // Check configured PluginManager cache
        $configured = Configure::read('PluginManager.cache.config');
        if ($configured !== null && Cache::getConfig($configured) !== null) {
            return $configured;
        }

        // Try common cache configs in order of preference
        $fallbacks = ['default', '_cake_core_', '_cake_model_'];
        foreach ($fallbacks as $config) {
            if (Cache::getConfig($config) !== null) {
                return $config;
            }
        }

        // No cache available - will use in-memory only
        return null;
    }

    /**
     * Get all registered plugins and their configuration.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAll(): array
    {
        $this->loadRegistry();

        return $this->registry;
    }

    /**
     * Get configuration for a specific plugin.
     *
     * @param string $pluginName The plugin name
     * @return array<string, mixed>|null
     */
    public function get(string $pluginName): ?array
    {
        $this->loadRegistry();

        return $this->registry[$pluginName] ?? null;
    }

    /**
     * Register or update a plugin's configuration.
     *
     * @param string $pluginName The plugin name
     * @param array<string, mixed> $config Plugin configuration
     * @return void
     */
    public function set(string $pluginName, array $config): void
    {
        $this->loadRegistry();

        $this->registry[$pluginName] = array_merge(
            $this->registry[$pluginName] ?? [],
            $config
        );

        $this->saveRegistry();
    }

    /**
     * Check if a plugin is enabled.
     *
     * @param string $pluginName The plugin name
     * @return bool
     */
    public function isEnabled(string $pluginName): bool
    {
        $config = $this->get($pluginName);

        return ($config['enabled'] ?? false) === true;
    }

    /**
     * Enable a plugin.
     *
     * @param string $pluginName The plugin name
     * @return void
     */
    public function enable(string $pluginName): void
    {
        $this->set($pluginName, ['enabled' => true]);
    }

    /**
     * Disable a plugin.
     *
     * @param string $pluginName The plugin name
     * @return void
     */
    public function disable(string $pluginName): void
    {
        $this->set($pluginName, ['enabled' => false]);
    }

    /**
     * Remove a plugin from the registry.
     *
     * @param string $pluginName The plugin name
     * @return void
     */
    public function remove(string $pluginName): void
    {
        $this->loadRegistry();

        unset($this->registry[$pluginName]);

        $this->saveRegistry();
    }

    /**
     * Load the registry from persistent storage.
     *
     * @return void
     */
    private function loadRegistry(): void
    {
        if ($this->loaded) {
            return;
        }

        // Try to load from cache first (if cache is available)
        if ($this->cacheConfig !== null) {
            $cached = Cache::read(self::CACHE_PREFIX . 'registry', $this->cacheConfig);

            if (is_array($cached)) {
                $this->registry = $cached;
                $this->loaded = true;
                return;
            }
        }

        // Load from plugins.local.php as fallback
        $localPath = CONFIG . 'plugins.local.php';

        if (file_exists($localPath) && is_readable($localPath)) {
            try {
                $plugins = require $localPath;
                if (is_array($plugins)) {
                    foreach ($plugins as $name => $config) {
                        $this->registry[$name] = array_merge(
                            ['enabled' => true],
                            is_array($config) ? $config : []
                        );
                    }
                }
            } catch (\Throwable $e) {
                // Log error but continue with empty registry
            }
        }

        $this->loaded = true;
        $this->saveRegistry();
    }

    /**
     * Save the registry to persistent storage.
     *
     * @return void
     */
    private function saveRegistry(): void
    {
        if ($this->cacheConfig !== null) {
            Cache::write(self::CACHE_PREFIX . 'registry', $this->registry, $this->cacheConfig);
        }
    }

    /**
     * Clear the registry cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        if ($this->cacheConfig !== null) {
            Cache::delete(self::CACHE_PREFIX . 'registry', $this->cacheConfig);
        }
        $this->registry = [];
        $this->loaded = false;
    }

    /**
     * Export registry to plugins.local.php format.
     *
     * @return string PHP code for plugins.local.php
     */
    public function exportToPhp(): string
    {
        $this->loadRegistry();

        $enabledPlugins = array_filter(
            $this->registry,
            fn(array $config): bool => ($config['enabled'] ?? false) === true
        );

        $exportArray = [];
        foreach ($enabledPlugins as $name => $config) {
            unset($config['enabled']);
            $exportArray[$name] = $config;
        }

        $export = var_export($exportArray, true);

        return "<?php\n\nreturn {$export};\n";
    }
}
