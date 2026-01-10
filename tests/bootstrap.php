<?php

declare(strict_types=1);

/**
 * Test bootstrap file for PluginManager plugin.
 */

// Load the main application bootstrap
require dirname(__DIR__, 3) . '/config/bootstrap.php';

// Load plugin test utilities
use Cake\Cache\Cache;
use Cake\Core\Configure;

// Configure test database
Configure::write('debug', true);

// Configure cache for tests if not already configured
if (!Cache::getConfig('_cake_core_')) {
    Cache::setConfig('_cake_core_', [
        'className' => 'Cake\Cache\Engine\ArrayEngine',
        'prefix' => 'plugin_manager_test_',
    ]);
}

if (!Cache::getConfig('default')) {
    Cache::setConfig('default', [
        'className' => 'Cake\Cache\Engine\ArrayEngine',
        'prefix' => 'plugin_manager_test_default_',
    ]);
}

// Define test-specific constants
if (!defined('PLUGIN_TESTS')) {
    define('PLUGIN_TESTS', dirname(__DIR__) . DS . 'tests' . DS);
}
