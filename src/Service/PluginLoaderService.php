<?php

declare(strict_types=1);

namespace PluginManager\Service;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Core\PluginCollection;
use RuntimeException;

/**
 * Service for loading and managing plugin state at runtime.
 *
 * Provides a unified interface for discovering, loading, and
 * managing CakePHP plugins.
 */
class PluginLoaderService
{
    /**
     * Constructor.
     *
     * @param \PluginManager\Service\PluginDiscoveryService $discoveryService Plugin discovery service
     * @param \PluginManager\Service\PluginRegistryService $registryService Plugin registry service
     */
    public function __construct(
        private PluginDiscoveryService $discoveryService,
        private PluginRegistryService $registryService
    ) {
    }

    /**
     * Get list of all available plugins with their status.
     *
     * @return array<string, array<string, mixed>>
     */
    public function listPlugins(): array
    {
        $discovered = $this->discoveryService->discover();
        $registry = $this->registryService->getAll();
        $loadedPlugins = $this->discoveryService->getLoadedPlugins();

        $plugins = [];

        foreach ($discovered as $name => $info) {
            $registryConfig = $registry[$name] ?? [];
            $normalizedName = $this->normalizeName($name);
            $pluginConfig = Configure::read($normalizedName);

            $plugins[$name] = [
                'name' => $name,
                'path' => $info['path'],
                'version' => $info['version'],
                'description' => $info['description'],
                'isLoaded' => in_array($normalizedName, $loadedPlugins, true),
                'isEnabled' => $registryConfig['enabled'] ?? false,
                'hasConfig' => !empty($pluginConfig) && is_array($pluginConfig),
                'config' => $registryConfig,
            ];
        }

        return $plugins;
    }

    /**
     * Get status of a specific plugin.
     *
     * @param string $pluginName The plugin name
     * @return array<string, mixed>|null
     */
    public function getPluginStatus(string $pluginName): ?array
    {
        $plugins = $this->listPlugins();

        // Try exact match first
        if (isset($plugins[$pluginName])) {
            return $plugins[$pluginName];
        }

        // Try normalized name match
        $normalized = $this->normalizeName($pluginName);
        foreach ($plugins as $name => $info) {
            if ($this->normalizeName($name) === $normalized) {
                return $info;
            }
        }

        return null;
    }

    /**
     * Check if a plugin is available (discovered).
     *
     * @param string $pluginName The plugin name
     * @return bool
     */
    public function isAvailable(string $pluginName): bool
    {
        return $this->discoveryService->exists($pluginName);
    }

    /**
     * Check if a plugin is currently loaded.
     *
     * @param string $pluginName The plugin name
     * @return bool
     */
    public function isLoaded(string $pluginName): bool
    {
        $normalized = $this->normalizeName($pluginName);

        return Plugin::isLoaded($normalized);
    }

    /**
     * Enable a plugin in the registry.
     *
     * Note: This does not load the plugin immediately. The plugin
     * will be loaded on next bootstrap.
     *
     * @param string $pluginName The plugin name
     * @param array<string, mixed> $config Optional plugin configuration
     * @return void
     */
    public function enablePlugin(string $pluginName, array $config = []): void
    {
        if (!$this->isAvailable($pluginName)) {
            throw new RuntimeException(
                sprintf('Plugin "%s" is not available', $pluginName)
            );
        }

        $this->registryService->set($pluginName, array_merge($config, ['enabled' => true]));
    }

    /**
     * Disable a plugin in the registry.
     *
     * Note: This does not unload an already-loaded plugin.
     * The change takes effect on next bootstrap.
     *
     * @param string $pluginName The plugin name
     * @return void
     */
    public function disablePlugin(string $pluginName): void
    {
        $this->registryService->disable($pluginName);
    }

    /**
     * Refresh plugin discovery cache.
     *
     * @return void
     */
    public function refresh(): void
    {
        $this->discoveryService->clearCache();
        $this->registryService->clearCache();
    }

    /**
     * Export current plugin configuration to file.
     *
     * @param string|null $path Optional custom path, defaults to config/plugins.local.php
     * @return bool Success
     */
    public function exportConfiguration(?string $path = null): bool
    {
        $path = $path ?? CONFIG . 'plugins.local.php';

        $content = $this->registryService->exportToPhp();

        $result = file_put_contents($path, $content);

        return $result !== false;
    }

    /**
     * Normalize plugin name to CakePHP format.
     *
     * @param string $name The plugin name
     * @return string Normalized name
     */
    private function normalizeName(string $name): string
    {
        // Handle vendor/package format
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);
            $name = end($parts);
        }

        // Convert kebab-case and snake_case to PascalCase
        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);

        return $name;
    }
}
