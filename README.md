[![Packagist Downloads](https://img.shields.io/packagist/dt/drupal-composer/drupal-paranoia.svg)](https://packagist.org/packages/drupal-composer/drupal-paranoia)
[![Build Status](https://travis-ci.org/drupal-composer/drupal-paranoia.svg?branch=1.x)](https://travis-ci.org/drupal-composer/drupal-paranoia)

# Drupal Paranoia
Composer plugin for improving the website security for composer-based Drupal websites by moving all __PHP files out of docroot__.

## Why use this Plugin?
The critical security issue with [Coder](https://www.drupal.org/project/coder) is a good example to consider moving PHP files outside of docroot:
- [Remote Code Execution - SA-CONTRIB-2016-039](https://www.drupal.org/node/2765575)
- https://twitter.com/drupalsecurity/status/753263548458004480

More related links:
- [Moving all PHP files out of the docroot](https://www.drupal.org/node/2767907)
- [#1672986: Option to have all php files outside of web root](https://www.drupal.org/node/1672986)

## Requirements
Except for Windows, this plugin should work on environments that have Composer support. [Windows support issue](https://github.com/drupal-composer/drupal-paranoia/issues/5).

## Configuration
Make sure you have a based [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project) project created.

Rename your current docroot directory to `app`.
```
mv web app
```

Update the `composer.json` of your root package with the following changes:
```json
"extra": {
    "installer-paths": {
        "app/core": ["type:drupal-core"],
        "app/libraries/{$name}": ["type:drupal-library"],
        "app/modules/contrib/{$name}": ["type:drupal-module"],
        "app/profiles/contrib/{$name}": ["type:drupal-profile"],
        "app/themes/contrib/{$name}": ["type:drupal-theme"],
        "drush/contrib/{$name}": ["type:drupal-drush"]
    },
    "drupal-paranoia": {
        "app-dir": "app",
        "web-dir": "web"
    },
    "..."
}
```

Use `composer require ...` to install this Plugin on your project.
```
composer require drupal-composer/drupal-paranoia:~1
```

Done! The plugin and the new docroot are now installed.

### Optional Configurations

#### Modify the asset file types

To extend the list of assets file types you can use the `asset-files` config:
```json
"extra": {
    "...",
    "drupal-paranoia": {
        "app-dir": "app",
        "web-dir": "web",
        "asset-files": [
            "somefile.txt",
            "*.md"
        ]
    },
    "..."
}
```

If you need to modify it you can use the
`post-drupal-set-asset-file-types` event:
```json
"scripts": {
    "post-drupal-set-asset-file-types": [
        "DrupalProject\\composer\\ScriptHandler::setAssetFileTypes"
    ],
    "..."
},
```

```php
<?php

/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */

namespace DrupalProject\composer;

use DrupalComposer\DrupalParanoia\AssetFileTypesEvent;

class ScriptHandler {

  public static function setAssetFileTypes(AssetFileTypesEvent $event) {
    $asset_file_types = $event->getAssetFileTypes();
    // Do what you want with the asset file types.
    $event->setAssetFileTypes($asset_file_types);
  }

}
```

#### Plugin events
This plugin fires the following named event during its execution process:

- __drupal-paranoia-post-command-run__: Occurs after the command `drupal:paranoia` is executed.

##### Example of event subscriber

```php
<?php

namespace MyVendor;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use DrupalComposer\DrupalParanoia\PluginEvents as DrupalParanoiaPluginEvents;

class MyClass implements PluginInterface, EventSubscriberInterface
{
    protected $composer;
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public static function getSubscribedEvents()
    {
        return array(
            DrupalParanoiaPluginEvents::POST_COMMAND_RUN => 'postDrupalParanoiaCommand',
        );
    }

    public function postDrupalParanoiaCommand(CommandEvent $event) {
        // Add your custom action.
    }
}
```

## Folder structure
Your project now is basically structured on two folders.
- __app__: Contains the files and folders of the full Drupal installation.
- __web__: Contains only the __symlinks of the assets files__ and the __PHP stub files__ from the `app` folder.

Every time that you install or update a Drupal package via Composer, the `web` folder is automatically recreated.

If necessary, you can rebuild it manually, running the command
```
composer drupal:paranoia
```

This could be necessary when updating themes images, CSS and JS files.

### Public files
This plugin assumes that the public files folder exists at `app/sites/<site>/files` and symlinks `web/sites/<site>/files -> ../../../app/sites/<site>/files`.

## Important
The document root configuration of your web server should point to the `web` path.
