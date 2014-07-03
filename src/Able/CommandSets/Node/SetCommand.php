<?php

namespace Able\CommandSets\Node;

use Able\Helpers\GlobalKnowledge\GlobalKnowledge;
use Able\Helpers\ScopeManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends GlobalKnowledgeCommand {

	protected function configure()
	{
		$this
			->setName('node:set')
			->setDescription('Sets or delete the specified key.')
			->addArgument('key', InputArgument::REQUIRED, 'The name of the key to set. Must start with /')
			->addArgument('value', InputArgument::OPTIONAL, 'The value to set for the key. If nothing is passed, the key is deleted.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		$knowledge = GlobalKnowledge::getInstance();

		$key = $input->getArgument('key');
		$value = $input->getArgument('value');

		if (!$value) {
			$this->delete($knowledge, $key);
		} else {
			$this->set($knowledge, $key, $value);
		}
	}

	public function getScope()
	{
		return ScopeManager::SCOPE_NODE;
	}
}
