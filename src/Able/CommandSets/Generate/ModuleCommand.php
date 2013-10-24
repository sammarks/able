<?php

namespace Able\CommandSets\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleCommand extends EmptyModuleCommand
{
	protected function configure()
	{
		$this
			->setName('generate:startermodule')
			->setDescription('Generates a new starter module');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output, 'module');
	}
}
