<?php

namespace Able\Helpers\GlobalKnowledge\Providers;

use Able\Helpers\ComponentFactory;

class ProviderFactory extends ComponentFactory {

	function getComponentClass()
	{
		return 'Able\\Helpers\\GlobalKnowledge\\Providers\\Provider';
	}

	function getComponentClassSuffix()
	{
		return 'Provider';
	}

	function getInternalPrefix()
	{
		return 'Able\\Helpers\\GlobalKnowledge\\Providers\\';
	}

}
