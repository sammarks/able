<?php

namespace Able\Helpers;

use Able\CommandSets\BaseCommand;
use FlorianWolters\Component\Util\Singleton\SingletonTrait;
use Symfony\Component\Console\Helper\ProgressBar;

class Logger {

	use SingletonTrait;

	/**
	 * @var BaseCommand
	 */
	protected $command = null;

	/**
	 * @var int
	 */
	protected $last_overwrite = 0;

	/**
	 * @var bool
	 */
	protected $had_overwrite = false;

	protected function __construct(BaseCommand $command)
	{
		$this->command = $command;
	}

	public function log($message, $color = 'white', $level = BaseCommand::DEBUG_NORMAL)
	{
		$this->clearOverwrite($message);
		$this->command->log($message, $color, $level);
	}

	public function overwrite($message, $color = 'white', $level = BaseCommand::DEBUG_NORMAL)
	{
		$this->clearOverwrite($message);

		$this->command->log("\r" . $message . "\r", $color, $level, false);
		$this->last_overwrite = strlen($message);
		$this->had_overwrite = true;
	}

	protected function clearOverwrite(&$message)
	{
		$length = strlen($message);
		if ($this->had_overwrite) {
			$this->had_overwrite = false;
			$spaces = $this->last_overwrite - strlen($message);
			for ($i = 0; $i < $spaces; $i++) {
				$message .= ' ';
			}
		}
		$this->last_overwrite = $length;
	}

	public function error($message, $fatal = false)
	{
		$this->clearOverwrite($message);
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
