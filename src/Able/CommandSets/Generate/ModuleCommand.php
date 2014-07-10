<?php

namespace Able\CommandSets\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleCommand extends EmptyModuleCommand
{
	protected function configure()
	{
		parent::configure();

		$this
			->setName('generate:startermodule')
			->setDescription('Generates a new starter module');
	}

	protected function execute(InputInterface $input, OutputInterface $output, $scaffold = 'module')
	{
		parent::execute($input, $output, 'module');
	}

	protected function prepareModuleReplacements(InputInterface $input)
	{
		parent::prepareModuleReplacements($input);

		$this->moduleReplacements['files'][] = 'helpers/helper.inc';
		$this->moduleReplacements['files'][] = 'hooks/module.inc';
		$this->moduleReplacements['files'][] = 'preprocessors/block-test-block.php';
	}
}
