<?php

namespace Able\Helpers;

class ScopeManager {

	const SCOPE_CONTAINER = 1;
	const SCOPE_NODE = 2;
	const SCOPE_NONE = 3;

	/**
	 * @var ScopeManager
	 */
	static $instance = null;

	/**
	 * The current scope.
	 * @var int
	 */
	protected $scope = self::SCOPE_NONE;

	/**
	 * Get Instance
	 *
	 * Gets the current instance of the scope manager.
	 *
	 * @return ScopeManager
	 */
	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get Scope
	 *
	 * Gets the current scope Able is running within.
	 *
	 * @return int
	 */
	public function getScope()
	{
		return $this->scope;
	}

	/**
	 * Set Scope
	 *
	 * Sets the current scope Able is running within.
	 *
	 * @param int $scope The scope to set Able to.
	 *
	 * @return int
	 */
	public function setScope($scope = self::SCOPE_NONE)
	{
		$this->scope = $scope;
		return $scope;
	}

} 
