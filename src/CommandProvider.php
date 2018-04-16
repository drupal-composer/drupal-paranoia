<?php

namespace DrupalComposer\DrupalParanoia;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * Class CommandProvider.
 *
 * @package DrupalComposer\DrupalParanoia
 */
class CommandProvider implements CommandProviderCapability {

  /**
   * {@inheritdoc}
   */
  public function getCommands() {
    return array(
      new DrupalParanoiaCommand(),
    );
  }

}
