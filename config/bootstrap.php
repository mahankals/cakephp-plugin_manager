<?php

declare(strict_types=1);

/**
 * PluginManager Bootstrap
 *
 * This file is loaded by the PluginManager plugin during bootstrap.
 * Add any plugin-specific configuration here.
 */

use Cake\Core\Configure;

// Default plugin configuration
Configure::write('PluginManager', [
    'cache' => [
        'config' => '_cake_core_',
        'duration' => '+1 hour',
    ],
    'discovery' => [
        'scanVendor' => true,
        'excludePatterns' => [
            'cakephp/*',
            'phpunit/*',
            'symfony/*',
        ],
    ],
]);
