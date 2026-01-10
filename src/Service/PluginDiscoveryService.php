<?php

declare(strict_types=1);

namespace PluginManager\Service;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use DirectoryIterator;
use RuntimeException;

/**
 * Service for discovering available CakePHP plugins.
 *
 * Scans configured plugin paths to find installed plugins
 * and their metadata.
 */
class PluginDiscoveryService
{
    /**
     * @var array<string, array<string, mixed>> Cache of discovered plugins
     */
    private array $discoveredPlugins = [];

    /**
     * @var bool Whether discovery has been performed
     */
    private bool $scanned = false;

    /**
     * Discover all available plugins in configured plugin paths.
     *
     * @param bool $refresh Force re-scan even if cached
     * @return array<string, array<string, mixed>> Array of plugin data keyed by plugin name
     */
    public function discover(bool $refresh = false): array
    {
        if ($this->scanned && !$refresh) {
            return $this->discoveredPlugins;
        }

        $this->discoveredPlugins = [];
        $pluginPaths = $this->getPluginPaths();

        foreach ($pluginPaths as $path) {
            if (!is_dir($path) || !is_readable($path)) {
                continue;
            }

            $this->scanDirectory($path);
        }

        $this->scanned = true;

        return $this->discoveredPlugins;
    }

    /**
     * Get all configured plugin paths.
     *
     * Only scans the application's plugins directory.
     * Vendor plugins are managed by Composer and should be
     * loaded via config/plugins.php, not discovered dynamically.
     *
     * @return array<string>
     */
    private function getPluginPaths(): array
    {
        return App::path('plugins');
    }

    /**
     * Scan a directory for plugins.
     *
     * @param string $path The directory to scan
     * @return void
     */
    private function scanDirectory(string $path): void
    {
        try {
            $iterator = new DirectoryIterator($path);
        } catch (\Exception $e) {
            return;
        }

        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            if (!$item->isDir()) {
                continue;
            }

            $name = $item->getFilename();

            // Skip hidden directories
            if (str_starts_with($name, '.')) {
                continue;
            }

            // Check if this looks like a CakePHP plugin
            $pluginInfo = $this->extractPluginInfo($item->getPathname(), $name);

            if ($pluginInfo !== null) {
                $this->discoveredPlugins[$name] = $pluginInfo;
            }
        }
    }

    /**
     * Extract plugin information from a directory.
     *
     * @param string $path The plugin directory path
     * @param string $name The directory name
     * @return array<string, mixed>|null Plugin info or null if not a valid plugin
     */
    private function extractPluginInfo(string $path, string $name): ?array
    {
        // Convert directory name to PascalCase for class name detection
        $pascalCaseName = $this->toCakePluginName($name);

        // Check for Plugin.php or src/{Name}Plugin.php
        // CakePHP 5 uses {PluginName}Plugin.php convention
        $pluginClassExists = file_exists($path . DS . 'src' . DS . 'Plugin.php')
            || file_exists($path . DS . 'src' . DS . $pascalCaseName . 'Plugin.php');

        // Check for composer.json with cakephp-plugin indicator
        $composerPath = $path . DS . 'composer.json';
        $isCakePlugin = false;
        $composerData = [];

        if (file_exists($composerPath)) {
            $content = file_get_contents($composerPath);
            if ($content !== false) {
                try {
                    $composerData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                    $isCakePlugin = ($composerData['type'] ?? '') === 'cakephp-plugin'
                        || isset($composerData['extra']['cakephp-plugin']);
                } catch (\JsonException $e) {
                    // Invalid JSON, skip
                }
            }
        }

        // Must be a CakePHP plugin (has Plugin class or composer marker)
        if (!$pluginClassExists && !$isCakePlugin) {
            return null;
        }

        return [
            'name' => $name,
            'path' => $path,
            'version' => $composerData['version'] ?? 'unknown',
            'description' => $composerData['description'] ?? '',
            'loaded' => Plugin::isLoaded($this->toCakePluginName($name)),
            'hasComposer' => !empty($composerData),
            'type' => $composerData['type'] ?? 'unknown',
        ];
    }

    /**
     * Convert vendor/package name to CakePHP plugin name format.
     *
     * @param string $name The package name
     * @return string The CakePHP plugin name
     */
    private function toCakePluginName(string $name): string
    {
        // Handle vendor/package format
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);
            $name = end($parts);
        }

        // Convert kebab-case to PascalCase
        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);

        return $name;
    }

    /**
     * Check if a specific plugin exists.
     *
     * @param string $pluginName The plugin name to check
     * @return bool
     */
    public function exists(string $pluginName): bool
    {
        $this->discover();

        return isset($this->discoveredPlugins[$pluginName]);
    }

    /**
     * Get info for a specific plugin.
     *
     * @param string $pluginName The plugin name
     * @return array<string, mixed>|null
     */
    public function getPluginInfo(string $pluginName): ?array
    {
        $this->discover();

        return $this->discoveredPlugins[$pluginName] ?? null;
    }

    /**
     * Get list of loaded plugins.
     *
     * @return array<string>
     */
    public function getLoadedPlugins(): array
    {
        return Plugin::loaded();
    }

    /**
     * Clear the discovery cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->discoveredPlugins = [];
        $this->scanned = false;
    }
}
