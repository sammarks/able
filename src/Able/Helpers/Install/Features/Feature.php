<?php

namespace Able\Helpers\Install\Features;

use Able\Helpers\Install\Component;

abstract class Feature extends Component {

	public function getWeight()
	{
		return 0;
	}

	public function getFolder()
	{
		$reflect = new \ReflectionClass($this);
		return $reflect->getShortName();
	}

}
