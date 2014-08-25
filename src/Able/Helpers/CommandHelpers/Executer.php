<?php

namespace Able\Helpers\CommandHelpers;

use Symfony\Component\Console\Output\OutputInterface;

class Executer extends CommandHelper {

	/**
	 * Execute
	 *
	 * Executes the specified command.
	 *
	 * @param string $command           The command to execute.
	 * @param bool   $exit_on_fail      Whether or not to halt the execution on failure status.
	 * @param bool   $force_passthrough Force the output to appear on the screen as the command is running.
	 * @param bool   $force_exec        Force the output to be returned.
	 *
	 * @return array|bool The first element is an array of the output and the second element
	 *                    is a boolean indicating whether or not the command succeeded.
	 * @throws \Exception
	 */
	public function exec($command, $exit_on_fail = true, $force_passthrough = false, $force_exec = false)
	{
		$this->verifyOutput();
		$this->verifyInput();

		$output_array = array();
		$return_var = null;

		if (($this->output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE || $force_passthrough) && $force_exec == false) {
			passthru($command, $return_var);
		} else {
			exec($command . ' 2>&1', $output_array, $return_var);
		}

		if ($return_var == 1) {
			if ($exit_on_fail) {
				Logger::getInstance()->error('Error running: ' . $command);
				Logger::getInstance()->error('There was an error with the last command. The program will now exit.', true);
			} else {
				return false;
			}
		}

		return $output_array;
	}

} 
