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

        // =====================================================================
        // Plugin Management Routes
        // =====================================================================

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

        // =====================================================================
        // Marketplace Routes
        // =====================================================================

        // Browse marketplace
        $builder->connect(
            '/marketplace',
            ['controller' => 'Plugins', 'action' => 'marketplace']
        );

        // Search marketplace (JSON API)
        $builder->connect(
            '/marketplace/search',
            ['controller' => 'Plugins', 'action' => 'marketplaceSearch']
        );

        // Refresh marketplace cache
        $builder->connect(
            '/marketplace/refresh',
            ['controller' => 'Plugins', 'action' => 'refreshMarketplace'],
            ['_method' => 'POST']
        );

        // Get plugin details from marketplace
        $builder->connect(
            '/marketplace/plugin/{package}',
            ['controller' => 'Plugins', 'action' => 'marketplaceDetails'],
            ['pass' => ['package']]
        );

        // Check for updates
        $builder->connect(
            '/check-updates',
            ['controller' => 'Plugins', 'action' => 'checkUpdates']
        );

        // Install plugin from marketplace
        $builder->connect(
            '/install/{package}',
            ['controller' => 'Plugins', 'action' => 'installPlugin'],
            ['pass' => ['package'], '_method' => 'POST']
        );

        // Update plugin
        $builder->connect(
            '/update/{name}',
            ['controller' => 'Plugins', 'action' => 'updatePlugin'],
            ['pass' => ['name'], '_method' => 'POST']
        );

        // Uninstall plugin
        $builder->connect(
            '/uninstall/{name}',
            ['controller' => 'Plugins', 'action' => 'uninstallPlugin'],
            ['pass' => ['name'], '_method' => 'POST']
        );
    }
);
