<?php

namespace Able\CommandSets\Node;

use Able\CommandSets\BaseCommand;
use Able\Helpers\GlobalKnowledge\GlobalKnowledge;
use Able\Helpers\ScopeManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitGlobalKnowledgeCommand extends BaseCommand {

	protected function configure()
	{
		$this
			->setName('node:init-global-knowledge')
			->setDescription('Initializes the global knowledge for the node cluster.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		$knowledge = GlobalKnowledge::getInstance($this->config['server']['global_knowledge']);

		// Create default directories.
		$this->mkdir($knowledge, '/containers');
		$this->mkdir($knowledge, '/sites');

		// Create default configuration options.
		$this->key($knowledge, '/config/aws/access_key', 'changeme');
		$this->key($knowledge, '/config/aws/access_token', 'changeme');

		$this->log('Complete!');
	}

	protected function key(GlobalKnowledge $knowledge, $key, $value)
	{
		$this->log('SET ' . $key . ' => ' . $value, 'green');
		$knowledge->set($key, $value);
	}

	protected function mkdir(GlobalKnowledge $knowledge, $directory)
	{
		$this->log('MKDIR ' . $directory, 'green');
		$knowledge->mkdir('/containers');
	}

	public function getScope()
	{
		return ScopeManager::SCOPE_NODE;
	}
}
