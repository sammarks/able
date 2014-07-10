<?php

namespace Able\CommandSets\Node;

use Able\Helpers\GlobalKnowledge\GlobalKnowledge;
use Able\Helpers\ScopeManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitGlobalKnowledgeCommand extends GlobalKnowledgeCommand {

	protected function configure()
	{
		$this
			->setName('node:init-global-knowledge')
			->setDescription('Initializes the global knowledge for the node cluster.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		$knowledge = GlobalKnowledge::getInstance($this->config->get('server/global_knowledge'));

		// Create default directories.
		$this->mkdir($knowledge, '/containers');
		$this->mkdir($knowledge, '/sites');

		// Create default configuration options.
		$this->key($knowledge, '/config/aws/access_key', 'changeme');
		$this->key($knowledge, '/config/aws/access_token', 'changeme');

		$this->log('Complete!');
	}

	public function getScope()
	{
		return ScopeManager::SCOPE_NODE;
	}
}
