<?php

namespace DrupalComposer\DrupalParanoia;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\IO\IOInterface;

/**
 * Class AssetFileTypesEvent.
 *
 * @package DrupalComposer\DrupalParanoia
 */
class AssetFileTypesEvent extends Event {

  /**
   * This event occurs after the asset file types have been set.
   *
   * @var string
   */
  const POST_DRUPAL_SET_ASSET_FILE_TYPES = 'post-drupal-set-asset-file-types';

  /**
   * Composer object.
   *
   * @var \Composer\Composer
   */
  private $composer;

  /**
   * IO object.
   *
   * @var \Composer\IO\IOInterface
   */
  private $io;

  /**
   * Asset file types.
   *
   * @var array
   */
  private $assetFileTypes;

  /**
   * AssetFileTypesEvent constructor.
   *
   * @param array $assetFileTypes
   *   The asset file types.
   * @param \Composer\Composer $composer
   *   The composer object.
   * @param \Composer\IO\IOInterface $io
   *   The IOInterface object.
   * @param array $args
   *   Arguments passed by the user
   * @param array $flags
   *   Optional flags to pass data not as argument.
   */
  public function __construct(array $assetFileTypes, Composer $composer, IOInterface $io, array $args = array(), array $flags = array()) {
    parent::__construct(self::POST_DRUPAL_SET_ASSET_FILE_TYPES, $args, $flags);

    $this->composer = $composer;
    $this->io = $io;
    $this->assetFileTypes = $assetFileTypes;
  }

  /**
   * @return \Composer\Composer
   */
  public function getComposer() {
    return $this->composer;
  }

  /**
   * @return \Composer\IO\IOInterface
   */
  public function getIo() {
    return $this->io;
  }

  /**
   * @return array
   */
  public function getAssetFileTypes() {
    return $this->assetFileTypes;
  }

  /**
   * @param array $assetFileTypes
   */
  public function setAssetFileTypes(array $assetFileTypes) {
    $this->assetFileTypes = $assetFileTypes;
  }

}
