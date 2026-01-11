<?php

declare(strict_types=1);

namespace PluginManager\Service;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Log\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Plugin Installer Service
 *
 * Handles Composer-based plugin installation, updates, and removal.
 * Provides secure command execution with proper validation.
 */
class PluginInstallerService
{
    /**
     * Path to Composer executable.
     */
    private string $composerPath;

    /**
     * Project root directory.
     */
    private string $projectRoot;

    /**
     * Plugin discovery service for cache clearing.
     */
    private PluginDiscoveryService $discoveryService;

    /**
     * Plugin loader service for enabling plugins.
     */
    private PluginLoaderService $loaderService;

    /**
     * Allowed package vendors (whitelist).
     *
     * @var array<int, string>
     */
    private array $allowedVendors;

    /**
     * Constructor.
     *
     * @param PluginDiscoveryService $discoveryService Discovery service
     * @param PluginLoaderService $loaderService Loader service
     */
    public function __construct(
        PluginDiscoveryService $discoveryService,
        PluginLoaderService $loaderService
    ) {
        $this->discoveryService = $discoveryService;
        $this->loaderService = $loaderService;

        // Initialize projectRoot first as findComposer() depends on it
        $this->projectRoot = defined('ROOT') ? ROOT : getcwd();

        $this->composerPath = Configure::read(
            'PluginManager.composerPath',
            $this->findComposer()
        );

        $this->allowedVendors = Configure::read(
            'PluginManager.allowedVendors',
            []
        );
    }

    /**
     * Install a plugin via Composer.
     *
     * @param string $packageName Composer package name (vendor/name)
     * @param string|null $version Optional version constraint
     * @return array<string, mixed> Result with success, output, and error keys
     * @throws InvalidArgumentException If package name is invalid
     */
    public function install(string $packageName, ?string $version = null): array
    {
        $this->validatePackageName($packageName);

        $package = $version ? "{$packageName}:{$version}" : $packageName;

        Log::info("PluginInstaller: Installing {$package}");

        $result = $this->executeComposer("require {$package}");

        if ($result['success']) {
            $this->postInstall($packageName);
        }

        return $result;
    }

    /**
     * Update a plugin to the latest version.
     *
     * @param string $packageName Composer package name
     * @return array<string, mixed> Result with success, output, and error keys
     * @throws InvalidArgumentException If package name is invalid
     */
    public function update(string $packageName): array
    {
        $this->validatePackageName($packageName);

        Log::info("PluginInstaller: Updating {$packageName}");

        $result = $this->executeComposer("update {$packageName}");

        if ($result['success']) {
            $this->postUpdate($packageName);
        }

        return $result;
    }

    /**
     * Remove a plugin via Composer.
     *
     * @param string $packageName Composer package name
     * @param bool $force Force removal even if plugin is enabled
     * @return array<string, mixed> Result with success, output, and error keys
     * @throws InvalidArgumentException If package name is invalid
     * @throws RuntimeException If plugin is enabled and force is false
     */
    public function remove(string $packageName, bool $force = false): array
    {
        $this->validatePackageName($packageName);

        // Check if plugin is enabled
        $pluginName = $this->getPluginNameFromPackage($packageName);
        if ($pluginName && Plugin::isLoaded($pluginName) && !$force) {
            throw new RuntimeException(
                "Plugin '{$pluginName}' is currently enabled. Disable it first or use force removal."
            );
        }

        Log::info("PluginInstaller: Removing {$packageName}");

        // Disable first if force removing
        if ($pluginName && $force) {
            try {
                $this->loaderService->disablePlugin($pluginName);
            } catch (\Throwable $e) {
                // Ignore errors when disabling
            }
        }

        $result = $this->executeComposer("remove {$packageName}");

        if ($result['success']) {
            $this->postRemove($packageName);
        }

        return $result;
    }

    /**
     * Check if Composer is available.
     *
     * @return bool True if Composer is available
     */
    public function isComposerAvailable(): bool
    {
        $output = [];
        $exitCode = 0;

        exec(
            escapeshellcmd($this->composerPath) . ' --version 2>&1',
            $output,
            $exitCode
        );

        return $exitCode === 0;
    }

    /**
     * Get installed package version.
     *
     * @param string $packageName Package name
     * @return string|null Version or null if not installed
     */
    public function getInstalledVersion(string $packageName): ?string
    {
        $lockFile = $this->projectRoot . '/composer.lock';

        if (!file_exists($lockFile)) {
            return null;
        }

        try {
            $content = file_get_contents($lockFile);
            if ($content === false) {
                return null;
            }

            $lock = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            $packages = array_merge(
                $lock['packages'] ?? [],
                $lock['packages-dev'] ?? []
            );

            foreach ($packages as $package) {
                if (($package['name'] ?? '') === $packageName) {
                    return $package['version'] ?? null;
                }
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get all installed packages with versions.
     *
     * @return array<string, string> Map of package name => version
     */
    public function getInstalledPackages(): array
    {
        $lockFile = $this->projectRoot . '/composer.lock';

        if (!file_exists($lockFile)) {
            return [];
        }

        try {
            $content = file_get_contents($lockFile);
            if ($content === false) {
                return [];
            }

            $lock = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }

            $packages = array_merge(
                $lock['packages'] ?? [],
                $lock['packages-dev'] ?? []
            );

            $result = [];
            foreach ($packages as $package) {
                $name = $package['name'] ?? null;
                $version = $package['version'] ?? null;

                if ($name && $version) {
                    // Only include CakePHP plugins
                    $type = $package['type'] ?? '';
                    if ($type === 'cakephp-plugin') {
                        $result[$name] = $version;
                    }
                }
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Validate a Composer package name.
     *
     * @param string $packageName Package name to validate
     * @return void
     * @throws InvalidArgumentException If invalid
     */
    private function validatePackageName(string $packageName): void
    {
        // Must match Composer package format: vendor/name
        if (!preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9]([_.-]?[a-z0-9]+)*$/i', $packageName)) {
            throw new InvalidArgumentException(
                "Invalid package name format: '{$packageName}'. Expected format: vendor/package"
            );
        }

        // Check vendor whitelist if configured
        if (!empty($this->allowedVendors)) {
            $vendor = explode('/', $packageName)[0];
            if (!in_array($vendor, $this->allowedVendors, true)) {
                throw new InvalidArgumentException(
                    "Vendor '{$vendor}' is not in the allowed vendors list."
                );
            }
        }

        // Prevent special characters that could be used for command injection
        if (preg_match('/[;&|`$<>]/', $packageName)) {
            throw new InvalidArgumentException(
                "Package name contains invalid characters."
            );
        }
    }

    /**
     * Execute a Composer command.
     *
     * @param string $command Composer command (without 'composer' prefix)
     * @return array<string, mixed> Result array
     */
    private function executeComposer(string $command): array
    {
        $fullCommand = sprintf(
            'cd %s && %s %s --no-interaction --no-ansi 2>&1',
            escapeshellarg($this->projectRoot),
            escapeshellcmd($this->composerPath),
            $command
        );

        $output = [];
        $exitCode = 0;

        exec($fullCommand, $output, $exitCode);

        $outputStr = implode("\n", $output);

        if ($exitCode !== 0) {
            Log::error("PluginInstaller: Command failed with exit code {$exitCode}: {$outputStr}");

            return [
                'success' => false,
                'exitCode' => $exitCode,
                'output' => $outputStr,
                'error' => $this->parseComposerError($outputStr),
            ];
        }

        return [
            'success' => true,
            'exitCode' => 0,
            'output' => $outputStr,
            'error' => null,
        ];
    }

    /**
     * Find the Composer executable.
     *
     * @return string Path to Composer
     */
    private function findComposer(): string
    {
        // Check for local composer.phar
        if (file_exists($this->projectRoot . '/composer.phar')) {
            return 'php ' . $this->projectRoot . '/composer.phar';
        }

        // Check common locations
        $paths = [
            '/usr/local/bin/composer',
            '/usr/bin/composer',
            'composer',
        ];

        foreach ($paths as $path) {
            $output = [];
            $exitCode = 0;
            exec("which {$path} 2>/dev/null", $output, $exitCode);

            if ($exitCode === 0 && !empty($output[0])) {
                return $output[0];
            }
        }

        // Default to 'composer' and hope it's in PATH
        return 'composer';
    }

    /**
     * Post-installation tasks.
     *
     * @param string $packageName Package name
     * @return void
     */
    private function postInstall(string $packageName): void
    {
        // Clear discovery cache
        $this->discoveryService->clearCache();

        // Get plugin name
        $pluginName = $this->getPluginNameFromPackage($packageName);

        if ($pluginName) {
            // Run migrations if available
            $this->runMigrations($pluginName);

            // Auto-enable if configured
            if (Configure::read('PluginManager.autoEnable', false)) {
                try {
                    $this->loaderService->enablePlugin($pluginName);
                    Log::info("PluginInstaller: Auto-enabled plugin '{$pluginName}'");
                } catch (\Throwable $e) {
                    Log::warning("PluginInstaller: Failed to auto-enable '{$pluginName}': {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Post-update tasks.
     *
     * @param string $packageName Package name
     * @return void
     */
    private function postUpdate(string $packageName): void
    {
        $this->discoveryService->clearCache();

        $pluginName = $this->getPluginNameFromPackage($packageName);

        if ($pluginName) {
            $this->runMigrations($pluginName);
        }
    }

    /**
     * Post-removal tasks.
     *
     * @param string $packageName Package name
     * @return void
     */
    private function postRemove(string $packageName): void
    {
        $this->discoveryService->clearCache();
    }

    /**
     * Run migrations for a plugin.
     *
     * @param string $pluginName Plugin name
     * @return void
     */
    private function runMigrations(string $pluginName): void
    {
        $migrationsPath = ROOT . '/plugins/' . $pluginName . '/config/Migrations';
        $vendorMigrationsPath = ROOT . '/vendor/*/*/config/Migrations';

        // Check if migrations exist (simplified check)
        $hasMigrations = is_dir($migrationsPath);

        if ($hasMigrations) {
            try {
                $command = sprintf(
                    'cd %s && bin/cake migrations migrate --plugin %s 2>&1',
                    escapeshellarg($this->projectRoot),
                    escapeshellarg($pluginName)
                );

                $output = [];
                $exitCode = 0;
                exec($command, $output, $exitCode);

                if ($exitCode !== 0) {
                    Log::warning("PluginInstaller: Migrations for '{$pluginName}' failed: " . implode("\n", $output));
                } else {
                    Log::info("PluginInstaller: Migrations for '{$pluginName}' completed");
                }
            } catch (\Throwable $e) {
                Log::warning("PluginInstaller: Error running migrations for '{$pluginName}': {$e->getMessage()}");
            }
        }
    }

    /**
     * Get plugin name from Composer package name.
     *
     * @param string $packageName Composer package name
     * @return string|null Plugin name or null
     */
    private function getPluginNameFromPackage(string $packageName): ?string
    {
        // Try to find in composer.json autoload
        $lockFile = $this->projectRoot . '/composer.lock';

        if (!file_exists($lockFile)) {
            return null;
        }

        try {
            $content = file_get_contents($lockFile);
            if ($content === false) {
                return null;
            }

            $lock = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            $packages = array_merge(
                $lock['packages'] ?? [],
                $lock['packages-dev'] ?? []
            );

            foreach ($packages as $package) {
                if (($package['name'] ?? '') !== $packageName) {
                    continue;
                }

                // Check for CakePHP plugin marker
                $extra = $package['extra']['cakephp-plugin'] ?? null;
                if (is_array($extra) && isset($extra['name'])) {
                    return $extra['name'];
                }

                // Try to derive from autoload
                $autoload = $package['autoload']['psr-4'] ?? [];
                foreach ($autoload as $namespace => $path) {
                    // Remove trailing backslash
                    $namespace = rtrim($namespace, '\\');

                    // If it looks like a plugin namespace
                    if (str_ends_with($path, 'src/') || $path === 'src') {
                        return $namespace;
                    }
                }
            }

            // Fallback: convert package name to PascalCase
            $parts = explode('/', $packageName);
            $name = end($parts);
            return str_replace(
                ['-', '_', ' '],
                '',
                ucwords(str_replace(['-', '_'], ' ', $name))
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parse Composer error output to get a user-friendly message.
     *
     * @param string $output Composer output
     * @return string User-friendly error message
     */
    private function parseComposerError(string $output): string
    {
        // Common error patterns
        if (str_contains($output, 'Could not find a matching version')) {
            return 'Package version not found. Please check the version constraint.';
        }

        if (str_contains($output, 'Package not found')) {
            return 'Package not found. Please verify the package name.';
        }

        if (str_contains($output, 'Your requirements could not be resolved')) {
            return 'Version conflict. The package requirements conflict with existing packages.';
        }

        if (str_contains($output, 'Permission denied')) {
            return 'Permission denied. Please check file permissions.';
        }

        if (str_contains($output, 'network')) {
            return 'Network error. Please check your internet connection.';
        }

        // Return last meaningful line or generic error
        $lines = array_filter(explode("\n", $output), 'trim');
        foreach (array_reverse($lines) as $line) {
            if (str_starts_with(trim($line), '[') && str_contains($line, ']')) {
                return trim($line);
            }
        }

        return 'Installation failed. Check the output for details.';
    }
}
