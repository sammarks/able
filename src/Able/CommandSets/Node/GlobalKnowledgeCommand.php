<?php

namespace Able\CommandSets\Node;

use Able\CommandSets\BaseCommand;
use Able\Helpers\CommandHelpers\Logger;
use Able\Helpers\GlobalKnowledge\GlobalKnowledge;

abstract class GlobalKnowledgeCommand extends BaseCommand {

	protected function delete(GlobalKnowledge $knowledge, $key)
	{
		Logger::getInstance()->log('DELETE ' . $key, 'red');
		$knowledge->rm($key);
	}

	protected function set(GlobalKnowledge $knowledge, $key, $value)
	{
		Logger::getInstance()->log('SET ' . $key . ' => ' . $value, 'green');
		$knowledge->set($key, $value);
	}

	protected function mkdir(GlobalKnowledge $knowledge, $directory)
	{
		Logger::getInstance()->log('MKDIR ' . $directory, 'green');
		$knowledge->mkdir('/containers');
	}

} 
