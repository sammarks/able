<?php

namespace Able\CommandSets\Node;

use Able\CommandSets\BaseCommand;
use Able\Helpers\GlobalKnowledge\GlobalKnowledge;

abstract class GlobalKnowledgeCommand extends BaseCommand {

	protected function delete(GlobalKnowledge $knowledge, $key)
	{
		$this->log('DELETE ' . $key, 'red');
		$knowledge->rm($key);
	}

	protected function set(GlobalKnowledge $knowledge, $key, $value)
	{
		$this->log('SET ' . $key . ' => ' . $value, 'green');
		$knowledge->set($key, $value);
	}

	protected function mkdir(GlobalKnowledge $knowledge, $directory)
	{
		$this->log('MKDIR ' . $directory, 'green');
		$knowledge->mkdir('/containers');
	}

} 
