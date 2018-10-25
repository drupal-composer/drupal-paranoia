<?php

namespace DrupalComposer\DrupalParanoia;

use Composer\Command\BaseCommand;
use Composer\Plugin\CommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DrupalParanoiaCommand.
 *
 * @package DrupalComposer\DrupalParanoia
 */
class DrupalParanoiaCommand extends BaseCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setName('drupal:paranoia')
      ->setAliases(['drupal-paranoia'])
      ->setDescription('Execute the installation of the paranoia mode.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $installer = new Installer($this->getComposer(), $this->getIO());
    $installer->install();

    $composer = $this->getComposer();
    $commandEvent = new CommandEvent(PluginEvents::POST_COMMAND_RUN, 'drupal:paranoia', $input, $output);
    $composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);
  }

}
