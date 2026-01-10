<?php

declare(strict_types=1);

namespace PluginManager\Controller;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Response;
use PluginManager\Service\PluginLoaderService;
use RuntimeException;

/**
 * Plugins Controller
 *
 * Provides API endpoints for plugin management.
 */
class PluginsController extends AppController
{
    /**
     * Plugin loader service.
     */
    private PluginLoaderService $pluginLoader;

    /**
     * Initialize controller.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        // Get service from container
        $container = $this->getRequest()->getAttribute('container');
        if ($container !== null && $container->has(PluginLoaderService::class)) {
            $this->pluginLoader = $container->get(PluginLoaderService::class);
        } else {
            // Fallback: create manually (for testing or when container not available)
            $discovery = new \PluginManager\Service\PluginDiscoveryService();
            $registry = new \PluginManager\Service\PluginRegistryService();
            $this->pluginLoader = new PluginLoaderService($discovery, $registry);
        }
    }

    /**
     * Index action - Dashboard view.
     *
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $plugins = $this->pluginLoader->listPlugins();

        $this->set(compact('plugins'));

        // Only serialize to JSON for API requests
        if ($this->isJsonRequest()) {
            $this->viewBuilder()->setClassName('Json');
            $this->viewBuilder()->setOption('serialize', ['plugins']);
        }

        return null;
    }

    /**
     * List all available plugins (JSON API).
     *
     * @return \Cake\Http\Response|null
     */
    public function list(): ?Response
    {
        $plugins = $this->pluginLoader->listPlugins();

        $this->set([
            'success' => true,
            'plugins' => $plugins,
            'count' => count($plugins),
        ]);
        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->setOption('serialize', ['success', 'plugins', 'count']);

        return null;
    }

    /**
     * Get status of a specific plugin (JSON API).
     *
     * @param string $name The plugin name (URL encoded)
     * @return \Cake\Http\Response|null
     */
    public function status(string $name): ?Response
    {
        $pluginName = urldecode($name);

        $status = $this->pluginLoader->getPluginStatus($pluginName);

        if ($status === null) {
            $this->set([
                'success' => false,
                'error' => 'Plugin not found',
            ]);
            $this->viewBuilder()->setClassName('Json');
            $this->viewBuilder()->setOption('serialize', ['success', 'error']);
            $this->response = $this->response->withStatus(404);

            return null;
        }

        $this->set([
            'success' => true,
            'plugin' => $status,
        ]);
        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->setOption('serialize', ['success', 'plugin']);

        return null;
    }

    /**
     * Enable a plugin.
     *
     * @param string $name The plugin name (URL encoded)
     * @return \Cake\Http\Response|null
     */
    public function enable(string $name): ?Response
    {
        $this->request->allowMethod(['POST']);

        $pluginName = urldecode($name);

        try {
            $this->pluginLoader->enablePlugin($pluginName);

            // Redirect for HTML requests
            if (!$this->isJsonRequest()) {
                $this->Flash->success("Plugin '{$pluginName}' has been enabled. Restart required.");
                return $this->redirect(['action' => 'index']);
            }

            $this->set([
                'success' => true,
                'message' => "Plugin '{$pluginName}' has been enabled. Restart required.",
            ]);
        } catch (RuntimeException $e) {
            // Redirect for HTML requests
            if (!$this->isJsonRequest()) {
                $this->Flash->error($e->getMessage());
                return $this->redirect(['action' => 'index']);
            }

            $this->set([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
            $this->response = $this->response->withStatus(400);
        }

        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->setOption('serialize', ['success', 'message', 'error']);

        return null;
    }

    /**
     * Disable a plugin.
     *
     * @param string $name The plugin name (URL encoded)
     * @return \Cake\Http\Response|null
     */
    public function disable(string $name): ?Response
    {
        $this->request->allowMethod(['POST']);

        $pluginName = urldecode($name);

        $this->pluginLoader->disablePlugin($pluginName);

        // Redirect for HTML requests
        if (!$this->isJsonRequest()) {
            $this->Flash->success("Plugin '{$pluginName}' has been disabled. Restart required.");
            return $this->redirect(['action' => 'index']);
        }

        $this->set([
            'success' => true,
            'message' => "Plugin '{$pluginName}' has been disabled. Restart required.",
        ]);
        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);

        return null;
    }

    /**
     * Refresh plugin discovery cache.
     *
     * @return \Cake\Http\Response|null
     */
    public function refresh(): ?Response
    {
        $this->request->allowMethod(['POST']);

        $this->pluginLoader->refresh();

        // Redirect for HTML requests
        if (!$this->isJsonRequest()) {
            $this->Flash->success('Plugin cache has been refreshed.');
            return $this->redirect(['action' => 'index']);
        }

        $this->set([
            'success' => true,
            'message' => 'Plugin cache has been refreshed.',
        ]);
        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);

        return null;
    }

    /**
     * Configure a plugin.
     *
     * @param string $name The plugin name (URL encoded)
     * @return \Cake\Http\Response|null
     */
    public function config(string $name): ?Response
    {
        $pluginName = urldecode($name);

        $status = $this->pluginLoader->getPluginStatus($pluginName);

        if ($status === null) {
            $this->Flash->error("Plugin '{$pluginName}' not found.");
            return $this->redirect(['action' => 'index']);
        }

        // Get current config for this plugin
        $currentConfig = Configure::read($this->normalizePluginName($pluginName)) ?? [];

        $this->set([
            'pluginName' => $pluginName,
            'pluginStatus' => $status,
            'currentConfig' => $currentConfig,
        ]);

        return null;
    }

    /**
     * Save plugin configuration.
     *
     * @param string $name The plugin name (URL encoded)
     * @return \Cake\Http\Response|null
     */
    public function saveConfig(string $name): ?Response
    {
        $this->request->allowMethod(['POST']);

        $pluginName = urldecode($name);
        $normalizedName = $this->normalizePluginName($pluginName);

        $status = $this->pluginLoader->getPluginStatus($pluginName);

        if ($status === null) {
            if ($this->isJsonRequest()) {
                $this->set(['success' => false, 'error' => 'Plugin not found']);
                $this->viewBuilder()->setClassName('Json');
                $this->viewBuilder()->setOption('serialize', ['success', 'error']);
                return $this->response->withStatus(404);
            }
            $this->Flash->error("Plugin '{$pluginName}' not found.");
            return $this->redirect(['action' => 'index']);
        }

        // Get config data from request
        $configData = $this->request->getData('config', []);

        // Parse the config (supports key=value format)
        $parsedConfig = $this->parseConfigInput($configData);

        // Save to app_local.php
        $saved = $this->saveToAppLocal($normalizedName, $parsedConfig);

        // If DbConfig plugin is loaded, also save to database
        $savedToDb = false;
        if (Plugin::isLoaded('DbConfig')) {
            $savedToDb = $this->saveToDbConfig($normalizedName, $parsedConfig);
        }

        if ($this->isJsonRequest()) {
            $this->set([
                'success' => $saved,
                'savedToFile' => $saved,
                'savedToDatabase' => $savedToDb,
                'message' => $saved ? 'Configuration saved successfully.' : 'Failed to save configuration.',
            ]);
            $this->viewBuilder()->setClassName('Json');
            $this->viewBuilder()->setOption('serialize', ['success', 'savedToFile', 'savedToDatabase', 'message']);
            return null;
        }

        if ($saved) {
            $message = 'Configuration saved successfully.';
            if ($savedToDb) {
                $message .= ' Also saved to database.';
            }
            $this->Flash->success($message);
        } else {
            $this->Flash->error('Failed to save configuration.');
        }

        return $this->redirect(['action' => 'config', $name]);
    }

    /**
     * Normalize plugin name to PascalCase for config key.
     *
     * @param string $name Plugin name
     * @return string Normalized name
     */
    private function normalizePluginName(string $name): string
    {
        // Convert kebab-case and snake_case to PascalCase
        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }

    /**
     * Parse config input from form.
     *
     * @param mixed $configData Raw config data
     * @return array<string, mixed> Parsed config
     */
    private function parseConfigInput(mixed $configData): array
    {
        if (!is_array($configData)) {
            return [];
        }

        $result = [];
        foreach ($configData as $item) {
            // Handle form structure: ['key' => 'name', 'value' => 'val']
            if (is_array($item) && isset($item['key']) && isset($item['value'])) {
                $key = trim((string)$item['key']);
                $value = trim((string)$item['value']);

                if (empty($key)) {
                    continue;
                }

                // Try to parse special values
                if (in_array($value, ['true', 'false', 'null'], true)) {
                    $value = json_decode($value);
                } elseif (is_numeric($value)) {
                    $value = str_contains($value, '.') ? (float)$value : (int)$value;
                } elseif (str_starts_with($value, '[') || str_starts_with($value, '{')) {
                    // Try to parse JSON arrays/objects
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $decoded;
                    }
                }

                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Save plugin config to app_local.php.
     *
     * @param string $pluginName Normalized plugin name
     * @param array<string, mixed> $config Config to save
     * @return bool Success
     */
    private function saveToAppLocal(string $pluginName, array $config): bool
    {
        $appLocalPath = CONFIG . 'app_local.php';

        // Read existing config
        $existingConfig = [];
        if (file_exists($appLocalPath) && is_readable($appLocalPath)) {
            $existingConfig = require $appLocalPath;
            if (!is_array($existingConfig)) {
                $existingConfig = [];
            }
        }

        // Merge plugin config
        $existingConfig[$pluginName] = $config;

        // Generate PHP code
        $export = var_export($existingConfig, true);
        $content = "<?php\n\nreturn {$export};\n";

        // Write to file
        $result = file_put_contents($appLocalPath, $content);

        // Also update runtime config
        if ($result !== false) {
            Configure::write($pluginName, $config);
        }

        return $result !== false;
    }

    /**
     * Save plugin config to DbConfig database.
     *
     * @param string $pluginName Normalized plugin name
     * @param array<string, mixed> $config Config to save
     * @return bool Success
     */
    private function saveToDbConfig(string $pluginName, array $config): bool
    {
        try {
            // Check if DbConfig service is available
            $container = $this->getRequest()->getAttribute('container');
            if ($container !== null && $container->has('DbConfig.ConfigService')) {
                $configService = $container->get('DbConfig.ConfigService');
                foreach ($config as $key => $value) {
                    $configService->set("{$pluginName}.{$key}", $value);
                }
                return true;
            }

            // Fallback: Try to use the model directly
            $settingsTable = $this->fetchTable('DbConfig.AppSettings');
            foreach ($config as $key => $value) {
                $configKey = "{$pluginName}.{$key}";
                $existing = $settingsTable->find()
                    ->where(['config_key' => $configKey])
                    ->first();

                $valueStr = is_array($value) ? json_encode($value) : (string)$value;
                $type = is_array($value) ? 'json' : 'string';

                if ($existing) {
                    $existing->value = $valueStr;
                    $existing->type = $type;
                    $settingsTable->save($existing);
                } else {
                    $newSetting = $settingsTable->newEntity([
                        'module' => $pluginName,
                        'config_key' => $configKey,
                        'value' => $valueStr,
                        'type' => $type,
                    ]);
                    $settingsTable->save($newSetting);
                }
            }
            return true;
        } catch (\Throwable $e) {
            // Log error but don't fail
            return false;
        }
    }
}
