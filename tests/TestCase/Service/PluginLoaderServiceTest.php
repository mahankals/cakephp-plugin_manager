<?php

declare(strict_types=1);

namespace PluginManager\Test\TestCase\Service;

use Cake\TestSuite\TestCase;
use PluginManager\Service\PluginDiscoveryService;
use PluginManager\Service\PluginLoaderService;
use PluginManager\Service\PluginRegistryService;
use RuntimeException;

/**
 * PluginLoaderService Test Case
 */
class PluginLoaderServiceTest extends TestCase
{
    protected PluginLoaderService $service;
    protected PluginDiscoveryService $discoveryService;
    protected PluginRegistryService $registryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discoveryService = new PluginDiscoveryService();
        $this->registryService = new PluginRegistryService();
        $this->service = new PluginLoaderService(
            $this->discoveryService,
            $this->registryService
        );
    }

    protected function tearDown(): void
    {
        $this->registryService->clearCache();
        unset($this->service, $this->discoveryService, $this->registryService);
        parent::tearDown();
    }

    /**
     * Test listPlugins returns array.
     */
    public function testListPluginsReturnsArray(): void
    {
        $result = $this->service->listPlugins();

        $this->assertIsArray($result);
    }

    /**
     * Test listPlugins includes expected fields.
     */
    public function testListPluginsIncludesRequiredFields(): void
    {
        $result = $this->service->listPlugins();

        foreach ($result as $plugin) {
            $this->assertArrayHasKey('name', $plugin);
            $this->assertArrayHasKey('path', $plugin);
            $this->assertArrayHasKey('isLoaded', $plugin);
            $this->assertArrayHasKey('isEnabled', $plugin);
        }
    }

    /**
     * Test getPluginStatus returns null for non-existent plugin.
     */
    public function testGetPluginStatusReturnsNullForMissing(): void
    {
        $result = $this->service->getPluginStatus('NonExistentPlugin12345');

        $this->assertNull($result);
    }

    /**
     * Test isAvailable returns boolean.
     */
    public function testIsAvailableReturnsBool(): void
    {
        $result = $this->service->isAvailable('NonExistentPlugin');

        $this->assertFalse($result);
    }

    /**
     * Test isLoaded returns boolean.
     */
    public function testIsLoadedReturnsBool(): void
    {
        $result = $this->service->isLoaded('NonExistentPlugin');

        $this->assertFalse($result);
    }

    /**
     * Test enablePlugin throws for non-existent plugin.
     */
    public function testEnablePluginThrowsForMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not available');

        $this->service->enablePlugin('NonExistentPlugin12345');
    }

    /**
     * Test disablePlugin works without error.
     */
    public function testDisablePluginSucceeds(): void
    {
        // Should not throw
        $this->service->disablePlugin('SomePlugin');

        $this->assertFalse($this->registryService->isEnabled('SomePlugin'));
    }

    /**
     * Test refresh clears caches.
     */
    public function testRefreshClearsCaches(): void
    {
        // Should not throw
        $this->service->refresh();

        // After refresh, should still work
        $result = $this->service->listPlugins();

        $this->assertIsArray($result);
    }
}
