<?php

declare(strict_types=1);

namespace PluginManager;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;

/**
 * Plugin for PluginManager
 */
class PluginManagerPlugin extends BasePlugin
{
  /**
   * Load all the plugin configuration and bootstrap logic.
   *
   * The host application is provided as an argument. This allows you to load
   * additional plugin dependencies, or attach events.
   *
   * @param \Cake\Core\PluginApplicationInterface $app The host application
   * @return void
   */
  public function bootstrap(PluginApplicationInterface $app): void
  {
    $plugins = [];

    // Load system-level plugins from plugins.local.php
    $localPath = CONFIG . 'plugins.local.php';
    if (file_exists($localPath)) {
      $localPlugins = require $localPath;
      $plugins = array_merge($plugins, $localPlugins);
    }

    // Register enabled plugins
    foreach ($plugins as $plugin => $config) {
      $app->addPlugin($plugin, $config);
    }
  }

  /**
   * Add routes for the plugin.
   *
   * If your plugin has many routes and you would like to isolate them into a separate file,
   * you can create `$plugin/config/routes.php` and delete this method.
   *
   * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
   * @return void
   */
  public function routes(RouteBuilder $routes): void
  {
    // remove this method hook if you don't need it
    $routes->plugin(
      'PluginManager',
      ['path' => '/plugin-manager'],
      function (RouteBuilder $builder) {
        // Add custom routes here

        $builder->fallbacks();
      }
    );
    parent::routes($routes);
  }

  /**
   * Add middleware for the plugin.
   *
   * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update.
   * @return \Cake\Http\MiddlewareQueue
   */
  public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
  {
    // Add your middlewares here
    // remove this method hook if you don't need it

    return $middlewareQueue;
  }

  /**
   * Add commands for the plugin.
   *
   * @param \Cake\Console\CommandCollection $commands The command collection to update.
   * @return \Cake\Console\CommandCollection
   */
  public function console(CommandCollection $commands): CommandCollection
  {
    // Add your commands here
    // remove this method hook if you don't need it

    $commands = parent::console($commands);

    return $commands;
  }

  /**
   * Register application container services.
   *
   * @param \Cake\Core\ContainerInterface $container The Container to update.
   * @return void
   * @link https://book.cakephp.org/5/en/development/dependency-injection.html#dependency-injection
   */
  public function services(ContainerInterface $container): void
  {
    // Add your services here
    // remove this method hook if you don't need it
  }
}
