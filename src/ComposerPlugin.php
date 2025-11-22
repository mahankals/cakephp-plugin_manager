<?php

namespace PluginManager;

use Composer\Composer;
use Composer\Script\Event;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
  private Composer $composer;
  private IOInterface $io;

  public function activate(Composer $composer, IOInterface $io)
  {
    $this->composer = $composer;
    $this->io = $io;

    try {
      $rootPath = dirname($composer->getConfig()->getConfigSource()->getName());

      // Create plugins/.gitignore file
      $gitignorePath = $rootPath . '/.gitignore';
      if (!file_exists($gitignorePath)) {
        $gitignoreContent = "*\n!.gitignore\n";
        file_put_contents($gitignorePath, $gitignoreContent);
        // $io->write("<info>Created plugins/.gitignore</info>");
      }

      // Create config/plugins.local.php file
      $configPath = $rootPath . '/config/plugins.local.php';
      if (!file_exists($configPath)) {
        $configContent = "<?php\nreturn [\n    // plugins array comes here\n];";
        file_put_contents($configPath, $configContent);
        // $io->write("<info>Created {$$configPath}</info>");
      }


      // gitignore config/plugins.local.php file
      $gitignoreFile = $rootPath . '/.gitignore';
      $lineToAdd = 'config/plugins.local.php';

      // Read existing lines
      $lines = file_exists($gitignoreFile) ? file($gitignoreFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

      // Check if the line already exists
      if (!in_array($lineToAdd, $lines, true)) {
        file_put_contents($gitignoreFile, PHP_EOL . $lineToAdd . PHP_EOL, FILE_APPEND);
      }

      // Delete plugins/.gitkeep if it exists
      $gitkeepPath = $rootPath . '/plugins/.gitkeep';
      if (file_exists($gitkeepPath)) {
        unlink($gitkeepPath);
      }

      $this->createComposerJson($rootPath);
      $this->addPlugin();
    } catch (\Throwable $e) {
      $io->writeError("<error>PluginManager installation error: " . $e->getMessage() . "</error>");
    }
  }

  public function deactivate(Composer $composer, IOInterface $io) {}

  public function uninstall(Composer $composer, IOInterface $io) {}

  public static function getSubscribedEvents(): array
  {
    return [
      // 'post-install-cmd' => 'onPostInstall',
      // 'post-update-cmd'  => 'onPostUpdate',
    ];
  }

  public function onPostInstall(Event $event): void
  {
    $io = $event->getIO();
    $io->write("<comment>[PluginManager]</comment> Running post-install tasks...");
  }

  public function onPostUpdate(Event $event): void
  {
    $io = $event->getIO();
    $io->write("<comment>[PluginManager]</comment> Running post-update tasks...");
  }

  public function createComposerJson($rootPath)
  {
    // Create plugins/composer.json file
    $composerJsonPath = $rootPath . '/composer.json';

    if (!file_exists($composerJsonPath)) {
      $composerJsonContent = <<<JSON
{
    "name": "cakephp/plugins",
    "config": {
        "vendor-dir": "../vendors"
    },
    "repositories": [
        {
            "type": "path",
            "url": "./*",
            "options": {
                "symlink": true
            }
        }
    ],
    "extra": {
        "merge-plugin": {
            "include": [
                "../composer.json"
            ],
            "recurse": true,
            "replace": false,
            "ignore-duplicates": false,
            "merge-dev": true,
            "merge-extra": false,
            "merge-extra-deep": false,
            "merge-replace": true,
            "merge-scripts": false,
            "merge-repository": true,
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
JSON;
      file_put_contents($composerJsonPath, $composerJsonContent);
      // $this->io->write("<info>Created plugins/composer.json</info>");
    }
  }

  public function addPlugin()
  {
    $vendorDir = $this->composer->getConfig()->get('vendor-dir');
    $projectRoot = dirname($vendorDir); // one level up from vendor
    $appFile = $projectRoot . '/src/Application.php';
    $lineToAdd = "        \$this->addPlugin('PluginManager');";

    if (!file_exists($appFile)) {
      $this->io->write("<error>Application.php not found!</error>");
      return;
    }

    $contents = file($appFile);

    // Check if line exists
    foreach ($contents as $line) {
      if (strpos($line, $lineToAdd) !== false) {
        // $this->io->write("PluginManager already registered.");
        return;
      }
    }

    // // Find parent::bootstrap(); line
    // $insertLine = null;
    // foreach ($contents as $i => $line) {
    //   if (strpos($line, 'parent::bootstrap();') !== false) {
    //     $insertLine = $i + 1; // insert after this line
    //     break;
    //   }
    // }

    // Find the closing brace of bootstrap() method
    $insideBootstrap = false;
    $braceCount = 0;
    $insertLine = null;

    foreach ($contents as $i => $line) {
      // Detect start of bootstrap()
      if (preg_match('/public function bootstrap\s*\(/', $line)) {
        $insideBootstrap = true;
        // Count opening braces on the same line
        $braceCount += substr_count($line, '{') - substr_count($line, '}');
        continue;
      }

      if ($insideBootstrap) {
        // Count braces to detect end of method
        $braceCount += substr_count($line, '{') - substr_count($line, '}');

        if ($braceCount === 0) {
          // End of bootstrap() method, insert before this line
          $insertLine = $i;
          break;
        }
      }
    }

    if ($insertLine === null) {
      $this->io->write("<error>parent::bootstrap(); not found in Application.php</error>");
      return;
    }

    array_splice($contents, $insertLine, 0, PHP_EOL . $lineToAdd . PHP_EOL);
    file_put_contents($appFile, implode("", $contents));

    $this->io->write("PluginManager registered in Application.php");
  }
}
