<?php

namespace Able\CommandSets\Core;

use Able\CommandSets\BaseCommand;
use Able\Helpers\CommandHelpers\Dialog;
use Able\Helpers\CommandHelpers\Logger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublishConfigCommand extends BaseCommand
{

	protected function configure()
	{
		$this
			->setName('core:publishconfig')
			->setDescription('Publish able core configuration.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		if (!is_dir('/etc/able')) {
			mkdir('/etc/able', 0777, true);
		}
		if (!is_file('/etc/able/config.php')) {
			copy(SCRIPTS_ROOT . '/config/config.yaml', '/etc/able/config.yaml');
		} else {
			if (Dialog::getInstance()->confirm('/etc/able/config.yaml already exists. Would you like to overwrite it?')) {
				copy(SCRIPTS_ROOT . '/config/config.yaml', '/etc/able/config.yaml');
			} else return;
		}
		Logger::getInstance()->log('Config published to /etc/able/config.yaml', 'green');
	}

}
