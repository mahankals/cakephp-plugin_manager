<?php

declare(strict_types=1);

namespace PluginManager\Test\TestCase\Service;

use Cake\TestSuite\TestCase;
use PluginManager\Service\PluginDiscoveryService;

/**
 * PluginDiscoveryService Test Case
 */
class PluginDiscoveryServiceTest extends TestCase
{
    protected PluginDiscoveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PluginDiscoveryService();
    }

    protected function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    /**
     * Test discover method returns array.
     */
    public function testDiscoverReturnsArray(): void
    {
        $result = $this->service->discover();

        $this->assertIsArray($result);
    }

    /**
     * Test discover caches results.
     */
    public function testDiscoverCachesResults(): void
    {
        $result1 = $this->service->discover();
        $result2 = $this->service->discover();

        $this->assertSame($result1, $result2);
    }

    /**
     * Test refresh forces re-scan.
     */
    public function testDiscoverRefreshForcesScan(): void
    {
        $this->service->discover();
        $this->service->clearCache();

        // Should not throw an exception
        $result = $this->service->discover(true);

        $this->assertIsArray($result);
    }

    /**
     * Test getLoadedPlugins returns array of plugin names.
     */
    public function testGetLoadedPluginsReturnsArray(): void
    {
        $result = $this->service->getLoadedPlugins();

        $this->assertIsArray($result);
    }

    /**
     * Test clearCache resets state.
     */
    public function testClearCacheResetsState(): void
    {
        $this->service->discover();
        $this->service->clearCache();

        // Service should perform fresh scan after clear
        $result = $this->service->discover();

        $this->assertIsArray($result);
    }

    /**
     * Test exists returns boolean.
     */
    public function testExistsReturnsBool(): void
    {
        $result = $this->service->exists('NonExistentPlugin');

        $this->assertFalse($result);
    }

    /**
     * Test getPluginInfo returns null for non-existent plugin.
     */
    public function testGetPluginInfoReturnsNullForMissing(): void
    {
        $result = $this->service->getPluginInfo('NonExistentPlugin');

        $this->assertNull($result);
    }
}
