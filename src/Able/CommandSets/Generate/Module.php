<?php

namespace Able\CommandSets\Generate;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Able\Commandsets\BaseCommand;

class ModuleCommand extends EmptyModuleCommand
{
	protected function configure()
	{
		$this
			->setName('generate:module')
			->setDescription('Generates a new module')
			->addArgument('machine-name',
				InputArgument::OPTIONAL,
				'The machine name of the module to create. Defaults to the profile name.',
				'[profile]');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		// TODO: Implement logic.
	}
}
