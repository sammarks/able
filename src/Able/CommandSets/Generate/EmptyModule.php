<?php

namespace Able\CommandSets\Generate;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Able\Commandsets\BaseCommand;

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
				null);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		// Prepare the module information.

		// TODO: Implement logic.
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
			$profile = $this->findProfile();
			if (!$profile) {
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

	protected function findProfile()
	{
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
