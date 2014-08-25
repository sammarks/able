<?php

namespace Able\CommandSets;

use Able\Helpers\CommandHelpers\Dialog;
use Able\Helpers\CommandHelpers\Executer;
use Able\Helpers\CommandHelpers\Logger;
use Able\Helpers\ScopeManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{

	/**
	 * @var InputInterface
	 */
	protected $input;

	/**
	 * @var OutputInterface
	 */
	protected $output;

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Set the input and output for sharing.
		$this->input = $input;
		$this->output = $output;

		// Initialize the command helpers.
		$question_helper = $this->getHelper('question');
		Logger::getInstance()->initialize($output, $input, $question_helper);
		Dialog::getInstance()->initialize($output, $input, $question_helper);
		Executer::getInstance()->initialize($output, $input, $question_helper);

		// Update the scope to reflect the current command.
		ScopeManager::getInstance()->setScope($this->getScope());

		// Check if the user has root privileges.
		if ($this->requiresRoot() && posix_getuid() != 0) {
			Logger::getInstance()->error('This script must be run as root.', true);
		}
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
