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

	/**
	 * The current input interface.
	 * @var InputInterface
	 */
	public $input = null;

	/**
	 * The current output interface.
	 * @var OutputInterface
	 */
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

	protected function overrideConfigOption($value, &$config)
	{
		if ($value) {
			$config = $value;
		}
		return $config;
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

	/**
	 * Log
	 *
	 * Logs a message to the output.
	 *
	 * @param string $message The message to log.
	 * @param string $color   The color of the message. Defaults to white.
	 * @param int    $level   The level of the message. Defaults to DEBUG_NORMAL, but can be
	 *                        DEBUG_VERBOSE.
	 *
	 * @throws \Exception
	 */
	public function log($message, $color = 'white', $level = self::DEBUG_NORMAL)
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

		if (!$this->input || !$this->output) {
			throw new \Exception('You must call parent::execute(...) before calling this function!');
		}

		if ($this->output->getVerbosity() != OutputInterface::VERBOSITY_QUIET) {
			$this->output->writeln("<fg=red>{$message}</fg=red>");
		}

		if ($fatal) {
			exit(1);
		}

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
	 * Confirm
	 *
	 * Asks the user a simple yes or no question.
	 *
	 * @param string $question     The question to ask the user.
	 * @param bool   $defaultValue The default value.
	 *
	 * @return bool The user's response.
	 */
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

	/**
	 * Prompt
	 *
	 * Asks the user a question.
	 *
	 * @param string $question     The question to ask.
	 * @param bool   $required     Whether or not a response is required.
	 * @param string $defaultValue The default value when the user chooses not to respond.
	 * @param bool   $hidden       Whether or not to hide the response.
	 *
	 * @return mixed
	 */
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

	/**
	 * Prompt With Replacement
	 *
	 * Prompts the user with a question and asks for a replacement value if they reject the
	 * question. For example:
	 *
	 * Does the username 'sam' sound okay? [Y/n]: n
	 * What would you like it to be then?: sammarks
	 *
	 * The return value would be 'sammarks'
	 *
	 * @param string $question The question to ask the user.
	 * @param string $value    The value to default to.
	 * @param bool   $required Whether or not the prompt is required. Is '' accepted?
	 * @param bool   $hidden   Whether or not to hide the text the user is typing (for passwords).
	 *
	 * @return string The replacement value, or the original.
	 */
	public function promptWithReplacement($question, $value, $required = false, $hidden = false)
	{
		if (!$this->confirm($question)) {
			return $this->prompt('What would you like it to be then?', $required, $value, $hidden);
		}
		return $value;
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

	public static function FileSizeConvert($bytes)
	{
		$bytes = floatval($bytes);
		$arBytes = array(
			0 => array(
				"UNIT" => "TB",
				"VALUE" => pow(1024, 4)
			),
			1 => array(
				"UNIT" => "GB",
				"VALUE" => pow(1024, 3)
			),
			2 => array(
				"UNIT" => "MB",
				"VALUE" => pow(1024, 2)
			),
			3 => array(
				"UNIT" => "KB",
				"VALUE" => 1024
			),
			4 => array(
				"UNIT" => "B",
				"VALUE" => 1
			),
		);

		foreach ($arBytes as $arItem) {
			if ($bytes >= $arItem["VALUE"]) {
				$result = $bytes / $arItem["VALUE"];
				$result = strval(round($result, 2)) . " " . $arItem["UNIT"];
				break;
			}
		}

		return $result;
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
