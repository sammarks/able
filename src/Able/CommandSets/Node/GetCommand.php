<?php

namespace Able\CommandSets\Node;

use Able\Helpers\GlobalKnowledge\GlobalKnowledge;
use Able\Helpers\ScopeManager;
use LinkORB\Component\Etcd\Exception\KeyNotFoundException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends GlobalKnowledgeCommand {

	protected function configure()
	{
		$this
			->setName('node:get')
			->setDescription('Gets the value of the specified key.')
			->addArgument('key', InputArgument::REQUIRED, 'The name of the key to get the value for.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$this->log(GlobalKnowledge::getInstance()->get($input->getArgument('key')));
		} catch (KeyNotFoundException $ex) {
			$this->error('The key could not be found.', true);
		}
	}

	public function getScope()
	{
		return ScopeManager::SCOPE_NODE;
	}

} 
