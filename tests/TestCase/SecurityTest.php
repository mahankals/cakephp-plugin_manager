<?php

declare(strict_types=1);

namespace PluginManager\Test\TestCase;

use Cake\TestSuite\TestCase;
use ReflectionClass;

/**
 * Security Test Case
 *
 * Tests for security-related functionality in the plugin.
 */
class SecurityTest extends TestCase
{
    /**
     * Test plugin name validation rejects directory traversal.
     */
    public function testPluginNameValidationRejectsTraversal(): void
    {
        $plugin = new \PluginManager\PluginManagerPlugin();
        $reflection = new ReflectionClass($plugin);
        $method = $reflection->getMethod('isValidPluginName');
        $method->setAccessible(true);

        // Directory traversal attempts
        $this->assertFalse($method->invoke($plugin, '../../../etc/passwd'));
        $this->assertFalse($method->invoke($plugin, 'plugin/../../../etc'));
        $this->assertFalse($method->invoke($plugin, '..'));
        $this->assertFalse($method->invoke($plugin, 'vendor/..'));
    }

    /**
     * Test plugin name validation rejects special characters.
     */
    public function testPluginNameValidationRejectsSpecialChars(): void
    {
        $plugin = new \PluginManager\PluginManagerPlugin();
        $reflection = new ReflectionClass($plugin);
        $method = $reflection->getMethod('isValidPluginName');
        $method->setAccessible(true);

        // Special characters
        $this->assertFalse($method->invoke($plugin, 'plugin;rm -rf'));
        $this->assertFalse($method->invoke($plugin, 'plugin`whoami`'));
        $this->assertFalse($method->invoke($plugin, 'plugin$(cat /etc/passwd)'));
        $this->assertFalse($method->invoke($plugin, 'plugin|ls'));
        $this->assertFalse($method->invoke($plugin, "plugin\ncommand"));
        $this->assertFalse($method->invoke($plugin, 'plugin<script>'));
    }

    /**
     * Test plugin name validation rejects empty values.
     */
    public function testPluginNameValidationRejectsEmpty(): void
    {
        $plugin = new \PluginManager\PluginManagerPlugin();
        $reflection = new ReflectionClass($plugin);
        $method = $reflection->getMethod('isValidPluginName');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($plugin, ''));
        $this->assertFalse($method->invoke($plugin, null));
        $this->assertFalse($method->invoke($plugin, 0));
        $this->assertFalse($method->invoke($plugin, []));
    }

    /**
     * Test plugin name validation rejects names starting with special chars.
     */
    public function testPluginNameValidationRejectsInvalidStart(): void
    {
        $plugin = new \PluginManager\PluginManagerPlugin();
        $reflection = new ReflectionClass($plugin);
        $method = $reflection->getMethod('isValidPluginName');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($plugin, '123Plugin'));
        $this->assertFalse($method->invoke($plugin, '-Plugin'));
        $this->assertFalse($method->invoke($plugin, '_Plugin'));
        $this->assertFalse($method->invoke($plugin, '/Plugin'));
    }

    /**
     * Test plugin name validation accepts valid names.
     */
    public function testPluginNameValidationAcceptsValid(): void
    {
        $plugin = new \PluginManager\PluginManagerPlugin();
        $reflection = new ReflectionClass($plugin);
        $method = $reflection->getMethod('isValidPluginName');
        $method->setAccessible(true);

        // Valid plugin names
        $this->assertTrue($method->invoke($plugin, 'MyPlugin'));
        $this->assertTrue($method->invoke($plugin, 'My_Plugin'));
        $this->assertTrue($method->invoke($plugin, 'My-Plugin'));
        $this->assertTrue($method->invoke($plugin, 'vendor/plugin'));
        $this->assertTrue($method->invoke($plugin, 'DebugKit'));
        $this->assertTrue($method->invoke($plugin, 'Bake'));
        $this->assertTrue($method->invoke($plugin, 'CakeSPA'));
    }
}
