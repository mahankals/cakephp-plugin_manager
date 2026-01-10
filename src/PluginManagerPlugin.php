<?php

declare(strict_types=1);

namespace PluginManager;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use PluginManager\Service\PluginDiscoveryService;
use PluginManager\Service\PluginLoaderService;
use PluginManager\Service\PluginRegistryService;

/**
 * PluginManager Plugin
 *
 * Provides a secure and maintainable way to manage CakePHP plugins
 * at runtime. Supports plugin discovery, loading, and state management.
 */
class PluginManagerPlugin extends BasePlugin
{
    /**
     * @var bool Whether bootstrap has been executed
     */
    private bool $bootstrapped = false;

    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        // Prevent double bootstrap
        if ($this->bootstrapped) {
            return;
        }
        $this->bootstrapped = true;

        // Load plugin configuration
        $this->loadConfiguration();

        // Load additional plugins defined in plugins.local.php
        $this->loadManagedPlugins($app);
    }

    /**
     * Load plugin-specific configuration.
     *
     * @return void
     */
    private function loadConfiguration(): void
    {
        // Configuration is loaded via config/bootstrap.php if it exists
        $bootstrapPath = $this->getPath() . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';
        if (file_exists($bootstrapPath)) {
            require $bootstrapPath;
        }
    }

    /**
     * Load managed plugins from the local configuration file.
     *
     * This safely loads plugins defined in config/plugins.local.php
     * with proper validation and error handling.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    private function loadManagedPlugins(PluginApplicationInterface $app): void
    {
        $localPath = CONFIG . 'plugins.local.php';

        if (!file_exists($localPath)) {
            return;
        }

        if (!is_readable($localPath)) {
            trigger_error(
                sprintf('PluginManager: Cannot read plugins configuration file: %s', $localPath),
                E_USER_WARNING
            );
            return;
        }

        try {
            $plugins = require $localPath;
        } catch (\Throwable $e) {
            trigger_error(
                sprintf('PluginManager: Error loading plugins configuration: %s', $e->getMessage()),
                E_USER_WARNING
            );
            return;
        }

        // Validate return value
        if (!is_array($plugins)) {
            trigger_error(
                'PluginManager: plugins.local.php must return an array',
                E_USER_WARNING
            );
            return;
        }

        // Register validated plugins
        foreach ($plugins as $pluginName => $config) {
            if (!$this->isValidPluginName($pluginName)) {
                trigger_error(
                    sprintf('PluginManager: Invalid plugin name: %s', $pluginName),
                    E_USER_WARNING
                );
                continue;
            }

            if (!is_array($config)) {
                $config = [];
            }

            try {
                $app->addPlugin($pluginName, $config);
            } catch (\Throwable $e) {
                trigger_error(
                    sprintf('PluginManager: Failed to load plugin "%s": %s', $pluginName, $e->getMessage()),
                    E_USER_WARNING
                );
            }
        }
    }

    /**
     * Validate plugin name to prevent directory traversal and injection attacks.
     *
     * @param mixed $pluginName The plugin name to validate
     * @return bool
     */
    private function isValidPluginName(mixed $pluginName): bool
    {
        if (!is_string($pluginName)) {
            return false;
        }

        if (empty($pluginName)) {
            return false;
        }

        // Plugin names should only contain alphanumeric, underscores, hyphens, and forward slashes (for vendor/plugin format)
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_\/\-]*$/', $pluginName)) {
            return false;
        }

        // Prevent directory traversal
        if (str_contains($pluginName, '..')) {
            return false;
        }

        return true;
    }

    /**
     * Add routes for the plugin.
     *
     * Routes are defined in config/routes.php to avoid duplication.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        // Load routes from config/routes.php
        parent::routes($routes);
    }

    /**
     * Add middleware for the plugin.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        // No custom middleware needed
        return $middlewareQueue;
    }

    /**
     * Add console commands for the plugin.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update.
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands = parent::console($commands);

        return $commands;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     */
    public function services(ContainerInterface $container): void
    {
        // Register plugin management services
        $container->addShared(PluginDiscoveryService::class);
        $container->addShared(PluginRegistryService::class);
        $container->addShared(PluginLoaderService::class)
            ->addArgument(PluginDiscoveryService::class)
            ->addArgument(PluginRegistryService::class);
    }
}
