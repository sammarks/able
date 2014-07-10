<?php

namespace Able\CommandSets\Core;

use Able\CommandSets\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigCommand extends BaseCommand {

	protected function configure()
	{
		$this
			->setName('core:config')
			->setDescription('Manage able core configuration.')
			->addArgument('name', InputArgument::OPTIONAL,
				'The name of the argument to get. If no value is provided, all values are output.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		$path = $input->getArgument('name');
		$value = $this->config->get($path);
		if ($value === null) {
			$this->error('The config value ' . $path . ' does not exist.', true);
		}
		$this->log((($path) ? $path : 'Config') . ': ' . print_r($value, 1));
	}

}
