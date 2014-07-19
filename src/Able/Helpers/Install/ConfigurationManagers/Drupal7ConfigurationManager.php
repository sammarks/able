<?php

namespace Able\Helpers\Install\ConfigurationManagers;

class Drupal7ConfigurationManager extends FileConfigurationManager {

	public function postInitialize()
	{
		parent::postInitialize();

		// Append the [settings:top] and [settings:bottom] tags to the settings.php file.
		if ($contents = file_get_contents($this->base_file)) {

			// Cleanup the contents.
			$this->cleanFileContents($contents);

			// Remove the <?php
			if (strpos($contents, '<?php') === 0) {
				$contents = substr($contents, 0, 5);
			}

			// Prepend <?php [settings:top] to the beginning of the file.
			$contents = "<?php\n\n[ablecore:base:settings:top]\n\n" . $contents;

			// Append [settings:bottom] to the end of the file.
			$contents .= "\n\n[ablecore:base:settings:bottom]\n";

			// Save the contents of the file.
			if (!file_put_contents($this->base_file, $contents)) {
				throw new Drupal7ConfigurationManagerException('There was an error saving the settings.php file.');
			}

		} else {
			throw new Drupal7ConfigurationManagerException('Could not open settings.php for modification.');
		}
	}

	protected function cleanFileContents(&$contents)
	{
		$contents = trim($contents, " \n\r\t");
	}

	protected function getBaseFile()
	{
		return $this->settings['webroot_folder'] . '/' . $this->settings['webroot'] . '/sites/default/settings.php';
	}

	protected function getFileLocation()
	{
		return $this->getBaseFile();
	}

	protected function getBaseReplacementList()
	{
		return array(
			'settings.top',
			'settings.bottom',
		);
	}

}

class Drupal7ConfigurationManagerException extends \Exception {}
