<?php

namespace Able\Helpers\Install\ConfigurationManagers;

class VHostConfigurationManager extends FileConfigurationManager {

	protected function getBaseReplacementList()
	{
		return array(
			'global.before',
			'global.after',
			'server.before',
			'server.after',
		);
	}

	protected function getBaseFile()
	{
		return SCRIPTS_ROOT . '/lib/vhost/base';
	}

} 

class VHostConfigurationManagerException extends \Exception {}
