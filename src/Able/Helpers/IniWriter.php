<?php

namespace Able\Helpers;

use FlorianWolters\Component\Util\Singleton\SingletonTrait;

class IniWriter {

	use SingletonTrait;

	protected $has_sections = true;
	protected $has_string_values = true;

	public function __construct($has_sections = true, $has_string_values = true)
	{
		$this->has_sections = $has_sections;
		$this->has_string_values = $has_string_values;
	}

	// From: http://stackoverflow.com/questions/1268378/create-ini-file-write-values-in-php
	public function write($assoc_arr)
	{
		$content = "";
		if ($this->has_sections) {
			foreach ($assoc_arr as $key => $elem) {
				if (!is_array($elem)) {
					/** @var \Able\Helpers\Logger $logger */
					$logger = Logger::getInstance();
					$logger->error('The element: ' . $elem . ' is invalid and has been skipped.');
					continue;
				}
				$content .= "[" . $key . "]\n";
				foreach ($elem as $key2 => $elem2) {
					if (is_array($elem2)) {
						for ($i = 0; $i < count($elem2); $i++) {
							$content .= $key2 . "[] = " . $this->iniValue($elem2[$i]) . "\n";
						}
					} else if ($elem2 === "") $content .= $key2 . " = \n";
					else $content .= $key2 . " = " . $this->iniValue($elem2) . "\n";
				}
			}
		} else {
			foreach ($assoc_arr as $key => $elem) {
				if (is_array($elem)) {
					for ($i = 0; $i < count($elem); $i++) {
						$content .= $key . "[] = " . $this->iniValue($elem[$i]) . "\n";
					}
				} else if ($elem === "") $content .= $key . " = \n";
				else $content .= $key . " = " . $this->iniValue($elem) . "\n";
			}
		}

		return $content;
	}

	protected function iniValue($value)
	{
		if ($this->has_string_values) {
			if (is_numeric($value)) {
				return $value;
			} else {
				return "\"{$value}\"";
			}
		} else return $value;
	}

} 
