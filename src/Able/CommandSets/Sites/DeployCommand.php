<?php

namespace Able\CommandSets\Sites;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Able\CommandSets\BaseCommand;

class DeployCommand extends BaseCommand
{

	protected function configure()
	{

		$this
			->setName('site:deploy')
			->setDescription('Deploy a Website')
			->addArgument('directory', InputArgument::REQUIRED, 'The directory that corresponds to the root of the site repository.');

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);


	}

}
