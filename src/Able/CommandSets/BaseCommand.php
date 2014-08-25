<?php

namespace Able\CommandSets;

use Able\Helpers\CommandHelpers\Logger;
use Able\Helpers\ScopeManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$logger = Logger::getInstance();

		$this->input = $input;
		$this->output = $output;
		$this->dialog = $this->getHelperSet()->get('dialog');

		// Update the scope to reflect the current command.
		ScopeManager::getInstance()->setScope($this->getScope());

		// Check if the user has root privileges.
		if ($this->requiresRoot() && posix_getuid() != 0) {
			$this->error('This script must be run as root.', true);
		}

		// Initialize the logger.
		Logger::getInstance($this);
	}

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

		if (!$this->input || !$this->output) {
			throw new \Exception('You must call parent::execute(...) before calling this function!');
		}

		$output_array = array();
		$return_var = null;

		if (($this->output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE || $force_passthrough) && $force_exec == false) {
			passthru($command, $return_var);
		} else {
			exec($command . ' 2>&1', $output_array, $return_var);
		}

		if ($return_var == 1) {
			if ($exit_on_fail) {
				$this->error('Error running: ' . $command);
				$this->error('There was an error with the last command. The program will now exit.', true);
			} else {
				return false;
			}
		}

		return $output_array;

	}

	/**
	 * Is Drupal Directory
	 *
	 * Checks to see if the specified directory is a Drupal directory.
	 *
	 * @param string $directory   The directory to check.
	 * @param int    $sensitivity The sensitivity of the search. Defaults to 5.
	 *
	 * @return bool Whether or not the directory is a Drupal directory.
	 */
	public function isDrupalDir($directory, $sensitivity = 5)
	{
		if (!is_dir($directory)) return false;

		// Check to see if the current directory is a Drupal directory.
		$directory_listing = scandir($directory);
		$passes = 0;
		foreach ($directory_listing as $line) {
			if ($this->strpos_array($line,
					array(
						'modules',
						'themes',
						'includes',
						'authorize.php',
						'misc',
						'profiles',
						'CHANGELOG.txt',
						'scripts',
						'sites'
					)) == true
			) {
				$passes++;
			}
		}
		return ($passes > $sensitivity);
	}

	/**
	 * Find Drupal Directory
	 *
	 * Starting from the current directory, traverse up the file tree until we find a Drupal
	 * directory. If we fail to find one, return an empty string. If we find one, return the
	 * path to the Drupal directory.
	 *
	 * @return string The Drupal directory, or an empty string.
	 */
	public function findDrupalDirectory()
	{
		$directory = getcwd();
		while (!$this->isDrupalDir($directory)) {
			$segments = explode(DIRECTORY_SEPARATOR, $directory);
			if (is_array($segments) && count($segments) > 0) {
				array_pop($segments);
				$directory = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
			} else {
				return '';
			}
		}
		return $directory;
	}

	public function strpos_array($haystack, $needles)
	{
		foreach ($needles as $needle) {
			if (strpos($haystack, $needle) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get Scope
	 *
	 * Gets the scope for the current command.
	 *
	 * @return int
	 */
	public function getScope()
	{
		return ScopeManager::SCOPE_NONE;
	}

	/**
	 * Requires Root
	 *
	 * Determines whether the current command requres root access or not.
	 *
	 * @return bool
	 */
	protected function requiresRoot()
	{
		return false;
	}

}
