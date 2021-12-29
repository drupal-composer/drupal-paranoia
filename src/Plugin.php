<?php

namespace DrupalComposer\DrupalParanoia;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin to move all PHP files out of the docroot.
 */
class Plugin implements PluginInterface, EventSubscriberInterface, Capable {

  /**
   * The installer object.
   *
   * @var \DrupalComposer\DrupalParanoia\Installer
   */
  protected $installer;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->installer = new Installer($composer, $io);
  }

  /**
   * {@inheritdoc}
   */
  public function deactivate(Composer $composer, IOInterface $io) {}

  /**
   * {@inheritdoc}
   */
  public function uninstall(Composer $composer, IOInterface $io) {}

  /**
   * {@inheritdoc}
   */
  public function getCapabilities() {
    return [
      'Composer\Plugin\Capability\CommandProvider' => 'DrupalComposer\DrupalParanoia\CommandProvider',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PackageEvents::POST_PACKAGE_INSTALL => ['postPackage'],
      PackageEvents::POST_PACKAGE_UPDATE => ['postPackage'],
      PackageEvents::POST_PACKAGE_UNINSTALL => ['postPackage'],
      ScriptEvents::POST_INSTALL_CMD => ['postCmd', -1],
      ScriptEvents::POST_UPDATE_CMD => ['postCmd', -1],
    ];
  }

  /**
   * Post package event behaviour.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   PackageEvent object.
   */
  public function postPackage(PackageEvent $event) {
    $this->installer->onPostPackageEvent($event);
  }

  /**
   * Post command event callback.
   *
   * @param \Composer\Script\Event $event
   *   Event object.
   */
  public function postCmd(Event $event) {
    $this->installer->onPostCmdEvent();
  }

}
