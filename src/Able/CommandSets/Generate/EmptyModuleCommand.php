<?php

namespace Able\CommandSets\Generate;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Able\Commandsets\BaseCommand;
use Able\Helpers\ScaffoldManager;

class EmptyModuleCommand extends BaseCommand
{
	protected $moduleReplacements = array();
	protected $profilePath = '';
	protected $profileName = '';
	protected $moduleMachineName = '';

	protected function configure()
	{
		$this
			->setName('generate:module')
			->setDescription('Generates a new module')
			->addArgument('machine-name',
				InputArgument::REQUIRED,
				'The machine name of the module to create.',
				null)
			->addArgument('name',
				InputArgument::OPTIONAL,
				'The name of the module to create. Defaults to the machine name of the module.',
				null)
			->addArgument('description',
				InputArgument::OPTIONAL,
				'The description of the module.',
				null)
			->addArgument('package',
				InputArgument::OPTIONAL,
				'The package of the module.',
				null)
			->addOption('directory',
				'd',
				InputOption::VALUE_REQUIRED,
				"The directory to create the scaffold in. Defaults to inside the current directory.",
				null);
	}

	protected function execute(InputInterface $input, OutputInterface $output, $scaffold = 'empty-module')
	{
		parent::execute($input, $output);

		// Prepare the module replacements.
		$this->log('Preparing');
		$this->prepareModuleReplacements($input);

		// Load the existing scaffold.
		$this->log('Loading the Scaffold', 'white', self::DEBUG_VERBOSE);
		$scaffold = new ScaffoldManager($scaffold);

		// Perform the replacements.
		$this->log('Performing Replacements', 'white', self::DEBUG_VERBOSE);
		$scaffold->performReplacements($this->moduleReplacements);

		// Get the path.
		$this->log('Writing to the Filesystem');
		$path = $this->getPath($input);
		$module_path = $this->createModuleDirectory($path);

		// Save the result to the filesystem.
		$scaffold->write($module_path);

		$this->log('Complete!', 'green');
	}

	protected function createModuleDirectory($path)
	{
		$module_path = $path . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $this->moduleMachineName;

		// Create the modules directory if it doesn't exist.
		if (!is_dir($module_path)) {
			if (!mkdir($module_path, 0777, true)) {
				throw new \Exception("There was an error creating the module directory in '{$module_path}'");
			} else {
				return $module_path;
			}
		} else {
			return $module_path;
		}
	}

	/**
	 * Gets the path for where the scaffold should be placed based on the
	 * current input.
	 *
	 * @param InputInterface $input The input.
	 *
	 * @return mixed|string The path.
	 * @throws \Exception
	 */
	protected function getPath(InputInterface $input)
	{
		// Check to see if the option exists.
		$dir = $input->getOption('directory');
		if ($dir) {
			$dir = realpath($dir);
			if (!is_dir($dir)) {
				throw new \Exception("The directory '{$dir}' does not exist.");
			} else {
				return $dir;
			}
		}

		return getcwd();
	}

	/**
	 * Prepare Module Replacements
	 *
	 * Prepares the replacements array for the module, gathering all required information
	 * in order to create the scaffold.
	 *
	 * @param InputInterface  $input  The input interface.
	 */
	protected function prepareModuleReplacements(InputInterface $input)
	{
		$this->log('Getting Information');

		$this->moduleReplacements = array();

		// Get the module machine name.
		$this->log('Getting the module machine name.', 'white', self::DEBUG_VERBOSE);
		$machine_name = $input->getArgument('machine-name');

		while (!$this->verifyMachineName($machine_name)) {
			$this->error('That machine name is invalid. Machine names can only contain alphanumeric characters and ' .
				'underscores.');
			$machine_name = $this->prompt('What machine name do you want for your module?', true);
		}

		// Get the module human name.
		$this->log('Getting the human name for the module.', 'white', self::DEBUG_VERBOSE);
		$name = $input->getArgument('name');
		if ($name == null) {
			if ($input->getOption('no-interaction')) {
				$name = $machine_name;
			} else {
				$name = $this->prompt('What would you like the human name for your module to be?', true);
			}
		}

		// Get the module description.
		$this->log('Getting the description for the module.', 'white', self::DEBUG_VERBOSE);
		$description = $input->getArgument('description');
		if ($description == null) {
			$description = $this->prompt('What would you like the description for your module to be?', false);
		}

		// Get the module package.
		$this->log('Getting the package for the module.', 'white', self::DEBUG_VERBOSE);
		$package = $input->getArgument('package');
		if ($package == null) {
			$package = $this->prompt('What would you like the package for your module to be (typically, this is the ' .
				'name of the site you\'re working on)?', false);
		}

		$this->moduleMachineName = $machine_name;

		$this->moduleReplacements = array(
			'files' => array(
				'module.module' => "{$machine_name}.module",
				'module.info' => "{$machine_name}.info",
			),
			'contents' => array(
				'[MODULE.NAME]' => $name,
				'[MODULE.DESCRIPTION]' => $description,
				'[MODULE.PACKAGE]' => $package,
				'MODULE_' => "{$machine_name}_",
			),
		);
	}

	/**
	 * Verify machine name.
	 *
	 * Makes sure the specified machine name is valid.
	 *
	 * @param string $machine_name The machine name to check.
	 *
	 * @return bool Whether or not it is valid.
	 */
	protected function verifyMachineName($machine_name)
	{
		$invalid = array(' ', '-', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '<', '>', '?', '.',
			',', '[', ']', '{', '}', '=', '+', '`', '~');
		foreach ($invalid as $item) {
			if (strpos($machine_name, $item) !== false) return false;
		}
		return true;
	}
}
