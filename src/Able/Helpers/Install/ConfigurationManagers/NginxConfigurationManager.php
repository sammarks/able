<?php

namespace Able\Helpers\Install\ConfigurationManagers;

class NginxConfigurationManager extends FileConfigurationManager {

	protected function getBaseFile()
	{
		return '/etc/nginx/nginx.conf';
	}

	protected function getBaseReplacementList()
	{
		return array(
			'global.before',
			'global.after',
		);
	}

	public function postInitialize()
	{
		parent::postInitialize();

		// Append the [ablecore:base:global:before] and [ablecore:base:global:after] to the file.
		if ($contents = file_get_contents($this->base_file)) {

			$contents = "[ablecore:base:global:before]\n\n" . $contents;
			$contents .= "\n\n[ablecore:base:global:after]";

			if (!file_put_contents($this->base_file, $contents)) {
				throw new NginxConfigurationManagerException('There was an error saving the default nginx configuration.');
			}

			print $contents;

		} else {
			throw new NginxConfigurationManagerException('Could not open the default nginx configuration for modification.');
		}
	}

}

class NginxConfigurationManagerException extends \Exception {}
