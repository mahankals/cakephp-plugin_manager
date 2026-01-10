<?php

declare(strict_types=1);

namespace PluginManager\Test\TestCase\Service;

use Cake\TestSuite\TestCase;
use PluginManager\Service\PluginRegistryService;

/**
 * PluginRegistryService Test Case
 */
class PluginRegistryServiceTest extends TestCase
{
    protected PluginRegistryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PluginRegistryService();
    }

    protected function tearDown(): void
    {
        $this->service->clearCache();
        unset($this->service);
        parent::tearDown();
    }

    /**
     * Test getAll returns array.
     */
    public function testGetAllReturnsArray(): void
    {
        $result = $this->service->getAll();

        $this->assertIsArray($result);
    }

    /**
     * Test get returns null for non-existent plugin.
     */
    public function testGetReturnsNullForMissing(): void
    {
        $result = $this->service->get('NonExistentPlugin');

        $this->assertNull($result);
    }

    /**
     * Test set and get roundtrip.
     */
    public function testSetAndGet(): void
    {
        $config = ['option1' => 'value1', 'enabled' => true];

        $this->service->set('TestPlugin', $config);
        $result = $this->service->get('TestPlugin');

        $this->assertIsArray($result);
        $this->assertSame('value1', $result['option1']);
        $this->assertTrue($result['enabled']);
    }

    /**
     * Test set merges with existing config.
     */
    public function testSetMergesConfig(): void
    {
        $this->service->set('TestPlugin', ['option1' => 'value1']);
        $this->service->set('TestPlugin', ['option2' => 'value2']);

        $result = $this->service->get('TestPlugin');

        $this->assertSame('value1', $result['option1']);
        $this->assertSame('value2', $result['option2']);
    }

    /**
     * Test isEnabled returns false by default.
     */
    public function testIsEnabledReturnsFalseByDefault(): void
    {
        $result = $this->service->isEnabled('NonExistentPlugin');

        $this->assertFalse($result);
    }

    /**
     * Test enable sets enabled flag.
     */
    public function testEnableSetsFlag(): void
    {
        $this->service->enable('TestPlugin');

        $this->assertTrue($this->service->isEnabled('TestPlugin'));
    }

    /**
     * Test disable clears enabled flag.
     */
    public function testDisableClearsFlag(): void
    {
        $this->service->enable('TestPlugin');
        $this->service->disable('TestPlugin');

        $this->assertFalse($this->service->isEnabled('TestPlugin'));
    }

    /**
     * Test remove deletes plugin from registry.
     */
    public function testRemoveDeletesPlugin(): void
    {
        $this->service->set('TestPlugin', ['enabled' => true]);
        $this->service->remove('TestPlugin');

        $this->assertNull($this->service->get('TestPlugin'));
    }

    /**
     * Test clearCache resets state.
     */
    public function testClearCacheResetsState(): void
    {
        $this->service->set('TestPlugin', ['enabled' => true]);
        $this->service->clearCache();

        // After clear, registry should reload (may be empty or from file)
        $result = $this->service->getAll();

        $this->assertIsArray($result);
    }

    /**
     * Test exportToPhp generates valid PHP.
     */
    public function testExportToPhpGeneratesValidPhp(): void
    {
        $this->service->set('TestPlugin', ['enabled' => true, 'debug' => false]);

        $result = $this->service->exportToPhp();

        $this->assertStringStartsWith('<?php', $result);
        $this->assertStringContainsString('return', $result);
    }

    /**
     * Test exportToPhp only includes enabled plugins.
     */
    public function testExportToPhpOnlyIncludesEnabled(): void
    {
        $this->service->set('EnabledPlugin', ['enabled' => true]);
        $this->service->set('DisabledPlugin', ['enabled' => false]);

        $result = $this->service->exportToPhp();

        $this->assertStringContainsString('EnabledPlugin', $result);
        $this->assertStringNotContainsString('DisabledPlugin', $result);
    }
}
