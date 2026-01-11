<?php

declare(strict_types=1);

namespace PluginManager\Service;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Exception\NetworkException;
use Cake\Http\Client\Exception\RequestException;
use Cake\Log\Log;

/**
 * Marketplace Service
 *
 * Handles communication with remote plugin registry for browsing,
 * searching, and retrieving plugin information from the marketplace.
 */
class MarketplaceService
{
    /**
     * HTTP client for API requests.
     */
    private Client $httpClient;

    /**
     * Remote registry base URL.
     */
    private string $registryUrl;

    /**
     * Cache configuration name.
     */
    private string $cacheConfig = 'default';

    /**
     * Cache key prefix.
     */
    private string $cachePrefix = 'marketplace_';

    /**
     * Cache duration in seconds (1 hour default).
     */
    private int $cacheDuration = 3600;

    /**
     * Path to local fallback registry.
     */
    private string $localRegistryPath;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'PluginManager/1.0',
            ],
        ]);

        $this->registryUrl = Configure::read(
            'PluginManager.registryUrl',
            'https://plugins.cloudpe.io/api/v1'
        );

        $this->localRegistryPath = dirname(__DIR__, 2) . '/config/registry.json';

        // Configure cache settings
        $cacheTimeout = Configure::read('PluginManager.cacheTimeout');
        if ($cacheTimeout) {
            $this->cacheDuration = $this->parseCacheTimeout($cacheTimeout);
        }
    }

    /**
     * Get the full plugin catalog from the registry.
     *
     * @param bool $refresh Force refresh from remote/local source
     * @return array<string, mixed> Catalog data with 'plugins' and 'categories' keys
     */
    public function getCatalog(bool $refresh = false): array
    {
        $cacheKey = $this->cachePrefix . 'catalog';

        if (!$refresh) {
            $cached = $this->getFromCache($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Try remote registry first
        $catalog = $this->fetchRemoteCatalog();

        // Fall back to local registry if remote fails
        if ($catalog === null) {
            $catalog = $this->loadLocalRegistry();
        }

        // Ensure we have a valid structure
        if (!is_array($catalog)) {
            $catalog = ['plugins' => [], 'categories' => []];
        }

        // Cache the result
        $this->saveToCache($cacheKey, $catalog);

        return $catalog;
    }

    /**
     * Search plugins by query string.
     *
     * @param string $query Search query
     * @param array<string, mixed> $filters Optional filters (category, tags, etc.)
     * @return array<int, array<string, mixed>> Matching plugins
     */
    public function search(string $query, array $filters = []): array
    {
        // Try remote search first
        $results = $this->remoteSearch($query, $filters);

        // Fall back to local search if remote fails
        if ($results === null) {
            $results = $this->localSearch($query, $filters);
        }

        return $results ?? [];
    }

    /**
     * Get detailed information about a specific plugin.
     *
     * @param string $packageName Composer package name (vendor/name)
     * @return array<string, mixed>|null Plugin details or null if not found
     */
    public function getPluginDetails(string $packageName): ?array
    {
        $cacheKey = $this->cachePrefix . 'plugin_' . md5($packageName);

        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Try remote first
        $details = $this->fetchRemotePluginDetails($packageName);

        // Fall back to local
        if ($details === null) {
            $details = $this->getLocalPluginDetails($packageName);
        }

        if ($details !== null) {
            $this->saveToCache($cacheKey, $details);
        }

        return $details;
    }

    /**
     * Get available versions for a plugin.
     *
     * @param string $packageName Composer package name
     * @return array<int, array<string, mixed>> Version list
     */
    public function getVersions(string $packageName): array
    {
        $cacheKey = $this->cachePrefix . 'versions_' . md5($packageName);

        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $versions = $this->fetchRemoteVersions($packageName);

        // Fall back: try Packagist API
        if ($versions === null) {
            $versions = $this->fetchPackagistVersions($packageName);
        }

        $versions = $versions ?? [];

        if (!empty($versions)) {
            $this->saveToCache($cacheKey, $versions);
        }

        return $versions;
    }

    /**
     * Check for available updates for installed plugins.
     *
     * @param array<string, string> $installedPlugins Map of package name => current version
     * @return array<string, array<string, mixed>> Plugins with available updates
     */
    public function checkUpdates(array $installedPlugins): array
    {
        $updates = [];

        foreach ($installedPlugins as $packageName => $currentVersion) {
            $versions = $this->getVersions($packageName);

            if (empty($versions)) {
                continue;
            }

            // Get latest stable version
            $latestVersion = $this->getLatestStableVersion($versions);

            if ($latestVersion && version_compare($latestVersion, $currentVersion, '>')) {
                $updates[$packageName] = [
                    'currentVersion' => $currentVersion,
                    'latestVersion' => $latestVersion,
                    'versions' => $versions,
                ];
            }
        }

        return $updates;
    }

    /**
     * Get list of available categories.
     *
     * @return array<int, string> Category names
     */
    public function getCategories(): array
    {
        $catalog = $this->getCatalog();

        return $catalog['categories'] ?? [];
    }

    /**
     * Clear all marketplace caches.
     *
     * @return void
     */
    public function clearCache(): void
    {
        try {
            Cache::clear($this->cacheConfig);
        } catch (\Throwable $e) {
            // Silently ignore cache errors
        }
    }

    /**
     * Fetch catalog from remote registry.
     *
     * @return array<string, mixed>|null Catalog or null on failure
     */
    private function fetchRemoteCatalog(): ?array
    {
        try {
            $response = $this->httpClient->get($this->registryUrl . '/catalog');

            if (!$response->isSuccess()) {
                Log::warning("Marketplace: Failed to fetch catalog, status: {$response->getStatusCode()}");
                return null;
            }

            $data = $response->getJson();

            if (!is_array($data)) {
                return null;
            }

            return $data;
        } catch (NetworkException | RequestException $e) {
            Log::warning("Marketplace: Network error fetching catalog: {$e->getMessage()}");
            return null;
        } catch (\Throwable $e) {
            Log::error("Marketplace: Error fetching catalog: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Load local fallback registry.
     *
     * @return array<string, mixed>|null Registry data or null if not found
     */
    private function loadLocalRegistry(): ?array
    {
        if (!file_exists($this->localRegistryPath)) {
            return null;
        }

        try {
            $content = file_get_contents($this->localRegistryPath);
            if ($content === false) {
                return null;
            }

            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Marketplace: Invalid JSON in local registry");
                return null;
            }

            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            Log::error("Marketplace: Error loading local registry: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Perform remote search.
     *
     * @param string $query Search query
     * @param array<string, mixed> $filters Search filters
     * @return array<int, array<string, mixed>>|null Results or null on failure
     */
    private function remoteSearch(string $query, array $filters): ?array
    {
        try {
            $params = ['q' => $query];

            if (!empty($filters['category'])) {
                $params['category'] = $filters['category'];
            }
            if (!empty($filters['tags'])) {
                $params['tags'] = implode(',', (array)$filters['tags']);
            }
            if (!empty($filters['sort'])) {
                $params['sort'] = $filters['sort'];
            }

            $response = $this->httpClient->get($this->registryUrl . '/search', $params);

            if (!$response->isSuccess()) {
                return null;
            }

            $data = $response->getJson();

            return is_array($data['plugins'] ?? null) ? $data['plugins'] : null;
        } catch (\Throwable $e) {
            Log::debug("Marketplace: Remote search failed: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Perform local search on cached/fallback data.
     *
     * @param string $query Search query
     * @param array<string, mixed> $filters Search filters
     * @return array<int, array<string, mixed>> Results
     */
    private function localSearch(string $query, array $filters): array
    {
        $catalog = $this->getCatalog();
        $plugins = $catalog['plugins'] ?? [];
        $results = [];

        $query = strtolower($query);
        $category = $filters['category'] ?? null;
        $tags = $filters['tags'] ?? [];

        foreach ($plugins as $plugin) {
            // Check category filter
            if ($category && ($plugin['category'] ?? '') !== $category) {
                continue;
            }

            // Check tags filter
            if (!empty($tags)) {
                $pluginTags = $plugin['tags'] ?? [];
                if (empty(array_intersect($tags, $pluginTags))) {
                    continue;
                }
            }

            // Search in name and description
            $searchText = strtolower(
                ($plugin['name'] ?? '') . ' ' .
                ($plugin['displayName'] ?? '') . ' ' .
                ($plugin['description'] ?? '')
            );

            if (str_contains($searchText, $query)) {
                $results[] = $plugin;
            }
        }

        // Sort results
        $sort = $filters['sort'] ?? 'downloads';
        usort($results, function ($a, $b) use ($sort) {
            return match ($sort) {
                'name' => strcasecmp($a['displayName'] ?? $a['name'] ?? '', $b['displayName'] ?? $b['name'] ?? ''),
                'rating' => ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0),
                'updated' => strtotime($b['updatedAt'] ?? '0') <=> strtotime($a['updatedAt'] ?? '0'),
                default => ($b['downloads'] ?? 0) <=> ($a['downloads'] ?? 0),
            };
        });

        return $results;
    }

    /**
     * Fetch plugin details from remote registry.
     *
     * @param string $packageName Package name
     * @return array<string, mixed>|null Plugin details
     */
    private function fetchRemotePluginDetails(string $packageName): ?array
    {
        try {
            $url = $this->registryUrl . '/plugins/' . urlencode($packageName);
            $response = $this->httpClient->get($url);

            if (!$response->isSuccess()) {
                return null;
            }

            $data = $response->getJson();

            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get plugin details from local registry.
     *
     * @param string $packageName Package name
     * @return array<string, mixed>|null Plugin details
     */
    private function getLocalPluginDetails(string $packageName): ?array
    {
        $catalog = $this->getCatalog();
        $plugins = $catalog['plugins'] ?? [];

        foreach ($plugins as $plugin) {
            if (($plugin['name'] ?? '') === $packageName) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * Fetch versions from remote registry.
     *
     * @param string $packageName Package name
     * @return array<int, array<string, mixed>>|null Versions
     */
    private function fetchRemoteVersions(string $packageName): ?array
    {
        try {
            $url = $this->registryUrl . '/plugins/' . urlencode($packageName) . '/versions';
            $response = $this->httpClient->get($url);

            if (!$response->isSuccess()) {
                return null;
            }

            $data = $response->getJson();

            return is_array($data['versions'] ?? null) ? $data['versions'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Fetch versions from Packagist API as fallback.
     *
     * @param string $packageName Package name
     * @return array<int, array<string, mixed>>|null Versions
     */
    private function fetchPackagistVersions(string $packageName): ?array
    {
        try {
            $url = 'https://repo.packagist.org/p2/' . $packageName . '.json';
            $response = $this->httpClient->get($url);

            if (!$response->isSuccess()) {
                return null;
            }

            $data = $response->getJson();
            $packages = $data['packages'][$packageName] ?? [];

            $versions = [];
            foreach ($packages as $versionData) {
                $versions[] = [
                    'version' => $versionData['version'] ?? 'unknown',
                    'time' => $versionData['time'] ?? null,
                    'require' => $versionData['require'] ?? [],
                ];
            }

            return $versions;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get the latest stable version from a version list.
     *
     * @param array<int, array<string, mixed>> $versions Version list
     * @return string|null Latest stable version
     */
    private function getLatestStableVersion(array $versions): ?string
    {
        $stableVersions = [];

        foreach ($versions as $versionData) {
            $version = $versionData['version'] ?? '';

            // Skip dev/alpha/beta/RC versions
            if (preg_match('/(dev|alpha|beta|rc)/i', $version)) {
                continue;
            }

            // Normalize version (remove 'v' prefix)
            $normalized = ltrim($version, 'v');

            if (preg_match('/^\d+\.\d+/', $normalized)) {
                $stableVersions[] = $normalized;
            }
        }

        if (empty($stableVersions)) {
            return null;
        }

        // Sort versions and get the latest
        usort($stableVersions, 'version_compare');

        return end($stableVersions);
    }

    /**
     * Get data from cache.
     *
     * @param string $key Cache key
     * @return mixed|null Cached data or null
     */
    private function getFromCache(string $key): mixed
    {
        try {
            $data = Cache::read($key, $this->cacheConfig);
            return $data !== false ? $data : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Save data to cache.
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @return void
     */
    private function saveToCache(string $key, mixed $data): void
    {
        try {
            Cache::write($key, $data, $this->cacheConfig);
        } catch (\Throwable $e) {
            // Silently ignore cache errors
        }
    }

    /**
     * Parse cache timeout string to seconds.
     *
     * @param string|int $timeout Timeout value (e.g., "1 hour", 3600)
     * @return int Seconds
     */
    private function parseCacheTimeout(string|int $timeout): int
    {
        if (is_int($timeout)) {
            return $timeout;
        }

        $parsed = strtotime($timeout, 0);

        return $parsed !== false ? $parsed : 3600;
    }
}
