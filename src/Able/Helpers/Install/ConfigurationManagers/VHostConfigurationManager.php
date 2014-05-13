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

	protected function getFileRoot()
	{
		return SCRIPTS_ROOT . '/lib/vhost/';
	}

} 

class VHostConfigurationManagerException extends \Exception {}
