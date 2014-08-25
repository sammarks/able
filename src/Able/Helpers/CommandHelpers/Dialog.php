<?php

namespace Able\Helpers\CommandHelpers;

use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Dialog extends CommandHelper {

	/**
	 * Confirm
	 *
	 * Asks the user a simple yes or no question.
	 *
	 * @param string $question The question to ask the user.
	 * @param bool   $default  The default value.
	 *
	 * @return bool The user's response.
	 */
	public function confirm($question, $default = true)
	{
		$this->verifyOutput();
		$this->verifyInput();

		$question = new ConfirmationQuestion($question, $default);

		return $this->question_helper->ask($this->input, $this->output, $question);
	}

	/**
	 * Prompt
	 *
	 * Asks the user a question.
	 *
	 * @param string $question The question to ask.
	 * @param bool   $required Whether or not a response is required.
	 * @param string $default  The default value when the user chooses not to respond.
	 * @param bool   $hidden   Whether or not to hide the response.
	 *
	 * @return mixed
	 */
	public function prompt($question, $required = false, $default = '', $hidden = false)
	{
		$this->verifyOutput();
		$this->verifyInput();

		if ($required && $this->input->getOption('no-interaction')) {
			Logger::getInstance()->error('Interaction is disabled, but interaction is required.', true);
			return false;
		}

		$question = new Question($question, $default);
		$question->setHidden($hidden);
		$response = $this->question_helper->ask($this->input, $this->output, $question);

		if (!$response && $required) {
			Logger::getInstance()->error('You must supply a value.');
			return $this->prompt($question, $required, $default);
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

} 
