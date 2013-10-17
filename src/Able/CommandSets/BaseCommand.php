<?php

namespace Able\CommandSets;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{

	const DEBUG_VERBOSE = 1;
	const DEBUG_NORMAL = 0;

	public $input = null;
	public $output = null;
	public $dialog = null;
	public $config = null;

	public function __construct()
	{

		// Load the configuration necessary.
		$this->_load_config();

		parent::__construct();

	}

	/**
	 * Loads the configuration files from SCRIPTS_ROOT/config/config.php first,
	 * then from /etc/able/config.php. /etc/able/config.php takes precedence.
	 *
	 * @return void
	 */
	private function _load_config()
	{

		$this->config = array();
		$configLocations = array(SCRIPTS_ROOT . '/config/config.php', '/etc/able/config.php');
		foreach ($configLocations as $location) {
			if (file_exists($location)) {
				$this->config = array_replace_recursive($this->config, include($location));
			}
		}

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$this->input = $input;
		$this->output = $output;
		$this->dialog = $this->getHelperSet()->get('dialog');

		// Check if the user has root privileges.
		if (posix_getuid() != 0) {
			$this->error('This script must be run as root.', true);
		}

	}

	protected function log($message, $color = 'white', $level = self::DEBUG_NORMAL)
	{

		if (!$this->input || !$this->output) {
			throw new \Exception('You must call parent::execute(...) before calling this function!');
		}

		if ($this->output->getVerbosity() != OutputInterface::VERBOSITY_QUIET && $level == self::DEBUG_NORMAL) {
			$this->output->writeln("<fg={$color}>{$message}</fg={$color}>");
			return;
		}

		if ($this->output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE && $level == self::DEBUG_VERBOSE) {
			$this->output->writeln("<fg={$color}>{$message}</fg={$color}>");
			return;
		}

	}

	public function error($message, $fatal = false)
	{

		if (!$this->input || !$this->output) {
			throw new Exception('You must call parent::execute(...) before calling this function!');
		}

		if ($this->output->getVerbosity() != OutputInterface::VERBOSITY_QUIET) {
			$this->output->writeln("<fg=red>{$message}</fg=red>");
		}

		if ($fatal) {
			exit(1);
		}

	}

	public function exec($command, $exit_on_fail = true, $force_passthrough = false, $force_exec = false)
	{

		if (!$this->input || !$this->output) {
			throw new Exception('You must call parent::execute(...) before calling this function!');
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

	public function confirm($question, $defaultValue = true)
	{
		$yes = ($defaultValue) ? 'Y' : 'y';
		$no = ($defaultValue) ? 'n' : 'N';
		$yesno = "[{$yes}/{$no}]";
		if ($this->input->getOption('no-interaction')) {
			return $defaultValue;
		}
		return $this->dialog->askConfirmation($this->output, $question . ' ' . $yesno . ': ', $defaultValue);
	}

	public function prompt($question, $required = false, $defaultValue = '', $hidden = false)
	{
		if ($required && $this->input->getOption('no-interaction')) {
			$this->error('Interaction is disabled, but interaction is required.', true);
			return;
		}
		if ($hidden) {
			$response = $this->dialog->askHiddenResponse($this->output, $question . ' - ');
		} else {
			$response = $this->dialog->ask($this->output, $question . ' - ', $defaultValue);
		}
		if (!$response && $required) {
			$this->error('You must supply a value.');
			return $this->prompt($question, $required, $defaultValue);
		} else {
			return $response;
		}
	}

	public function is_drupal_dir($directory, $sensitivity = 5)
	{

		// Check to see if the current directory is a Drupal directory.
		$directory_listing = $this->exec("ls -a {$directory} | tr '\\n' '\\n'", true, false, true);
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

	public function strpos_array($haystack, $needles)
	{
		foreach ($needles as $needle) {
			if (strpos($haystack, $needle) !== false) {
				return true;
			}
		}
		return false;
	}

}
