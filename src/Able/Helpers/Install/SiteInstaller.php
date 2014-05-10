<?php

namespace Able\Helpers\Install;

use Able\CommandSets\BaseCommand;

interface Installer {

	public function initialize(BaseCommand $command, array $settings = array());
	public function install();

}

abstract class SiteInstaller implements Installer {

	/**
	 * The settings array for the current site.
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * The base command.
	 *
	 * @var BaseCommand|null
	 */
	protected $command = null;

	protected function preBuildVHostConfiguration(VHostConfigManager $config) {}

	public function initialize(BaseCommand $command, array $settings = array())
	{
		$this->settings = $settings;
		$this->command = $command;
	}

}
