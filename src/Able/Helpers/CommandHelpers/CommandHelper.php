<?php

namespace Able\Helpers\CommandHelpers;

use FlorianWolters\Component\Util\Singleton\SingletonTrait;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandHelper {

	use SingletonTrait;

	/**
	 * The output interface for the current command.
	 * @var OutputInterface
	 */
	protected $output = null;

	/**
	 * The input interface for the current command.
	 * @var InputInterface
	 */
	protected $input = null;

	/**
	 * The question helper from the existing command.
	 * @var QuestionHelper
	 */
	protected $question_helper = null;

	public function setOutput(OutputInterface $output)
	{
		$this->output = $output;
	}

	public function setInput(InputInterface $input)
	{
		$this->input = $input;
	}

	public function setQuestionHelper(QuestionHelper $question_helper)
	{
		$this->question_helper = $question_helper;
	}

	/**
	 * Verify Output
	 *
	 * @throws \Exception If the output has not been set, an exception is thrown.
	 */
	protected function verifyOutput()
	{
		if (!$this->output)
			throw new \Exception('The logger has not yet been initialized (output).');
	}

	/**
	 * Verify Input
	 *
	 * @throws \Exception If the input has not been set, an exception is thrown.
	 */
	protected function verifyInput()
	{
		if (!$this->input)
			throw new \Exception('The logger has not yet been initialized (input).');
	}

} 
