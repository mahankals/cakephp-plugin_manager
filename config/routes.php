<?php

declare(strict_types=1);

/**
 * PluginManager Routes
 *
 * This file defines routes for the PluginManager plugin.
 * Routes are also defined in the plugin class for flexibility.
 */

use Cake\Routing\RouteBuilder;

/** @var \Cake\Routing\RouteBuilder $routes */
$routes->plugin(
    'PluginManager',
    ['path' => '/plugin-manager'],
    function (RouteBuilder $builder): void {
        // Enable JSON extension for API access
        $builder->setExtensions(['json']);

        $builder->connect(
            '/',
            ['controller' => 'Plugins', 'action' => 'index']
        );
        $builder->connect(
            '/list',
            ['controller' => 'Plugins', 'action' => 'list']
        );
        $builder->connect(
            '/status/{name}',
            ['controller' => 'Plugins', 'action' => 'status'],
            ['pass' => ['name']]
        );
        $builder->connect(
            '/enable/{name}',
            ['controller' => 'Plugins', 'action' => 'enable'],
            ['pass' => ['name'], '_method' => 'POST']
        );
        $builder->connect(
            '/disable/{name}',
            ['controller' => 'Plugins', 'action' => 'disable'],
            ['pass' => ['name'], '_method' => 'POST']
        );
        $builder->connect(
            '/refresh',
            ['controller' => 'Plugins', 'action' => 'refresh'],
            ['_method' => 'POST']
        );
        $builder->connect(
            '/config/{name}',
            ['controller' => 'Plugins', 'action' => 'config'],
            ['pass' => ['name']]
        );
        $builder->connect(
            '/config/{name}/save',
            ['controller' => 'Plugins', 'action' => 'saveConfig'],
            ['pass' => ['name'], '_method' => 'POST']
        );
    }
);
