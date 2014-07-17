<?php

namespace Able\Helpers;

use Able\CommandSets\BaseCommand;
use FlorianWolters\Component\Util\Singleton\SingletonTrait;

class Logger {

	use SingletonTrait;

	/**
	 * @var BaseCommand
	 */
	protected $command = null;

	protected function __construct(BaseCommand $command)
	{
		$this->command = $command;
	}

	public function log($message, $color = 'white', $level = BaseCommand::DEBUG_NORMAL)
	{
		$this->command->log($message, $color, $level);
	}

	public function error($message, $fatal = false)
	{
		$this->command->error($message, $fatal);
	}

	public function confirm($question, $defaultValue = true)
	{
		return $this->command->confirm($question, $defaultValue);
	}

	public function prompt($question, $required = false, $defaultValue = '', $hidden = false)
	{
		return $this->command->prompt($question, $required, $defaultValue, $hidden);
	}

} 
