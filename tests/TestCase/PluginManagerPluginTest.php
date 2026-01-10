<?php

declare(strict_types=1);

namespace PluginManager\Test\TestCase;

use Cake\Core\Container;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\TestSuite\TestCase;
use PluginManager\PluginManagerPlugin;
use PluginManager\Service\PluginDiscoveryService;
use PluginManager\Service\PluginLoaderService;
use PluginManager\Service\PluginRegistryService;

/**
 * PluginManagerPlugin Test Case
 */
class PluginManagerPluginTest extends TestCase
{
    protected PluginManagerPlugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = new PluginManagerPlugin();
    }

    protected function tearDown(): void
    {
        unset($this->plugin);
        parent::tearDown();
    }

    /**
     * Test plugin has correct name.
     */
    public function testPluginName(): void
    {
        $this->assertSame('PluginManager', $this->plugin->getName());
    }

    /**
     * Test routes method adds plugin routes.
     */
    public function testRoutesAddsPluginRoutes(): void
    {
        $collection = new RouteCollection();
        $routes = new RouteBuilder($collection, '/');

        $this->plugin->routes($routes);

        // Plugin should have added routes
        $this->assertNotEmpty($collection->routes());
    }

    /**
     * Test middleware returns MiddlewareQueue.
     */
    public function testMiddlewareReturnsQueue(): void
    {
        $queue = new MiddlewareQueue();

        $result = $this->plugin->middleware($queue);

        $this->assertInstanceOf(MiddlewareQueue::class, $result);
    }

    /**
     * Test services registers expected services.
     */
    public function testServicesRegistersServices(): void
    {
        $container = new Container();

        $this->plugin->services($container);

        $this->assertTrue($container->has(PluginDiscoveryService::class));
        $this->assertTrue($container->has(PluginRegistryService::class));
        $this->assertTrue($container->has(PluginLoaderService::class));
    }

    /**
     * Test services can be resolved from container.
     */
    public function testServicesCanBeResolved(): void
    {
        $container = new Container();

        $this->plugin->services($container);

        $discovery = $container->get(PluginDiscoveryService::class);
        $registry = $container->get(PluginRegistryService::class);
        $loader = $container->get(PluginLoaderService::class);

        $this->assertInstanceOf(PluginDiscoveryService::class, $discovery);
        $this->assertInstanceOf(PluginRegistryService::class, $registry);
        $this->assertInstanceOf(PluginLoaderService::class, $loader);
    }
}
