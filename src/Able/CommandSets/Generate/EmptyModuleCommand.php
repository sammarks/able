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

	protected function configure()
	{
		$this
			->setName('generate:module')
			->setDescription('Generates a new module')
			->addArgument('machine-name',
				InputArgument::OPTIONAL,
				'The machine name of the module to create. Defaults to the profile name.',
				null)
			->addArgument('name',
				InputArgument::OPTIONAL,
				'The name of the module to create.',
				null)
			->addArgument('description',
				InputArgument::OPTIONAL,
				'The description of the module.',
				null)
			->addArgument('package',
				InputArgument::OPTIONAL,
				'The package of the module.',
				null)
			->addOption('profile',
				'p',
				InputOption::VALUE_REQUIRED,
				"The profile to use.",
				"")
			->addOption('directory',
				'd',
				InputOption::VALUE_REQUIRED,
				"The directory to create the scaffold in. Defaults to inside the current profile.",
				"[profile]");
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
		$module_path = $this->createModuleDirectory($path, $input);

		// Save the result to the filesystem.
		$scaffold->write($module_path);

		$this->log('Complete!', 'green');
	}

	protected function createModuleDirectory($path, InputInterface $input)
	{
		// Cancel if they specified a directory.
		if ($input->getOption('directory'))
			return realpath($input->getOption('directory'));

		// Create the modules directory if it doesn't exist.
		if (!is_dir($path . DIRECTORY_SEPARATOR . 'modules')) {
			if (!mkdir($path . DIRECTORY_SEPARATOR . 'modules')) {
				throw new \Exception("There was an error creating the module directory in '{$path}'");
			} else {
				return $path . DIRECTORY_SEPARATOR . 'modules';
			}
		} else {
			return $path . DIRECTORY_SEPARATOR . 'modules';
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
		if ($dir != '[profile]' && $dir) {
			$dir = realpath($dir);
			if (!is_dir($dir)) {
				throw new \Exception("The directory '{$dir}' does not exist.");
			} else {
				return $dir;
			}
		}

		// Try to find the profile.
		$drupal_dir = $this->findDrupalDirectory();
		$profile = $this->findProfile($input);
		if (!$profile || $profile == '[multiple]') {

			while(true) {
				// Prompt the user for the profile.
				$profile = $this->prompt('What is the name of the profile you would like to use?', true);

				if (is_dir($drupal_dir . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . $profile)) {
					break;
				} else {
					$this->error('That profile does not exist.');
				}
			}

		}

		return $drupal_dir . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . $profile;
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
		if ($machine_name == null) {

			// Generate a name based on the profile.
			$profile = $this->findProfile($input);
			if (!$profile || $profile == '[multiple]') {
				$profile = $this->prompt('Able failed to find the profile installed. What is the name of your Drupal '
					. 'profile?', true);
			}
			$generated_name = $profile . '_core';

			// Ask the user to confirm the name.
			$machine_name = $this->promptWithReplacement("How does the module machine name '{$generated_name}' sound?",
				$generated_name,
				true);

		}
		while (!$this->verifyMachineName($machine_name)) {
			$this->error('That machine name is invalid. Machine names can only contain alphanumeric characters and ' .
				'underscores.');
			$machine_name = $this->prompt('What machine name do you want for your module?', true);
		}

		// Get the module human name.
		$this->log('Getting the human name for the module.', 'white', self::DEBUG_VERBOSE);
		$name = $input->getArgument('name');
		if ($name == null) {
			$name = $this->prompt('What would you like the human name for your module to be?', true);
		}

		// Get the module description.
		$this->log('Getting the description for the module.', 'white', self::DEBUG_VERBOSE);
		$description = $input->getArgument('description');
		if ($description == null) {
			$description = $this->prompt('What would you like the description for your module to be?', true);
		}

		// Get the module package.
		$this->log('Getting the package for the module.', 'white', self::DEBUG_VERBOSE);
		$package = $input->getArgument('package');
		if ($package == null) {
			$package = $this->prompt('What would you like the package for your module to be (typically, this is the ' .
				'name of the site you\'re working on)?', true);
		}

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
		if (strpos($machine_name, array(' ', '-', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '<', '>', '?', '.',
			',', '[', ']', '{', '}', '=', '+', '`', '~'))) return false;
		return true;
	}

	protected function findProfile(InputInterface $input)
	{
		// Check to see if the profile was provided.
		if ($input->getOption('profile')) {
			$profile = realpath($input->getOption('profile'));
			if (is_dir($profile)) {
				$segments = explode(DIRECTORY_SEPARATOR, $profile);
				return $segments[count($segments) - 1];
			} else {
				$this->error('The profile specified does not exist. Continuing to auto-find profile.');
			}
		}

		$drupalDir = $this->findDrupalDirectory();
		if (!$drupalDir || !is_dir($drupalDir)) {
			return '';
		}

		// Make sure the profiles directory exists.
		$folders = scandir($drupalDir);
		$profilesDir = '';
		foreach ($folders as $folder) {

			// Make sure it's a folder.
			$path = $drupalDir . DIRECTORY_SEPARATOR . $folder;
			if (!is_dir($path)) continue;

			if ($folder == 'profiles') {
				$profilesDir = $path;
				break;
			}

		}
		if (!$profilesDir || !is_dir($profilesDir)) {
			return '';
		}

		// Try and find the Drupal profiles and get the odd man out.
		$profiles = scandir($profilesDir);
		$oddMenOut = array();
		foreach ($profiles as $profile) {

			// Make sure we have a directory.
			$path = $profilesDir . DIRECTORY_SEPARATOR . $profile;
			if (!is_dir($path)) continue;

			if ($profile != 'minimal' && $profile  != 'standard' && $profile != 'testing')
				$oddMenOut[] = $profile;

		}

		// If we have multiple odd men out, return '[multiple]'
		if (count($oddMenOut) > 1)
			return '[multiple]';

		if (count($oddMenOut) <= 0)
			return '';

		return $oddMenOut[0];
	}
}
