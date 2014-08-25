<?php

namespace Able\Helpers\CommandHelpers;

use Able\CommandSets\BaseCommand;
use FlorianWolters\Component\Util\Singleton\SingletonTrait;
use Symfony\Component\Console\Output\OutputInterface;

class Logger extends CommandHelper {

	const DEBUG_VERBOSE = 1;
	const DEBUG_NORMAL = 0;

	/**
	 * @var int
	 */
	protected $last_overwrite = 0;

	/**
	 * @var bool
	 */
	protected $had_overwrite = false;

	/**
	 * Log
	 *
	 * Logs a message to the output.
	 *
	 * @param string $message The message to log.
	 * @param string $color   The color of the message. Defaults to white.
	 * @param int    $level   The level of the message. Defaults to DEBUG_NORMAL, but can be
	 *                        DEBUG_VERBOSE.
	 * @param bool   $newline Whether or not a newline should be appended.
	 *
	 * @throws \Exception
	 */
	public function log($message, $color = 'white', $level = self::DEBUG_NORMAL, $newline = true)
	{
		$this->verifyOutput();
		$this->clearOverwrite($message);

		if (($this->output->getVerbosity() != OutputInterface::VERBOSITY_QUIET && $level == self::DEBUG_NORMAL) ||
			($this->output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE && $level == self::DEBUG_VERBOSE)) {
			$this->output->write("<fg={$color}>{$message}</fg={$color}>", $newline);
			return;
		}
	}

	/**
	 * Overwrite
	 *
	 * Writes $message to the screen and prepares it to be overwritten by the next message.
	 *
	 * @param string $message The message to output.
	 * @param string $color   The color of the message.
	 * @param int    $level   The verbosity level of the message.
	 */
	public function overwrite($message, $color = 'white', $level = BaseCommand::DEBUG_NORMAL)
	{
		$this->clearOverwrite($message);

		$this->log("\r" . $message . "\r", $color, $level, false);
		$this->last_overwrite = strlen($message);
		$this->had_overwrite = true;
	}

	/**
	 * Clear Overwrite
	 *
	 * @param string $message The message to clear.
	 */
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

	/**
	 * Error
	 *
	 * Logs an error to the output.
	 *
	 * @param string $message The message to use as the error.
	 * @param bool   $fatal   Whether or not to halt script execution because of the error.
	 *
	 * @throws \Exception
	 */
	public function error($message, $fatal = false)
	{
		$this->verifyOutput();
		$this->clearOverwrite($message);

		if ($this->output->getVerbosity() != OutputInterface::VERBOSITY_QUIET) {
			$this->output->writeln("<fg=red>{$message}</fg=red>");
		}

		if ($fatal) {
			exit(1);
		}
	}

} 
