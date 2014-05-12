<?php

namespace Able\Helpers\Install\Installers;

use Able\Helpers\Install\VHostConfigManager;
use Able\Helpers\Install\Component;

abstract class Installer extends Component {

	public abstract function install();
	protected function preBuildVHostConfiguration(VHostConfigManager $config) {}

}
