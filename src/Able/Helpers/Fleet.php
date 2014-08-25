<?php

namespace Able\Helpers;

use Able\CommandSets\BaseCommand;
use Able\Helpers\CommandHelpers\Executer;
use Able\Helpers\CommandHelpers\Logger;
use FlorianWolters\Component\Util\Singleton\SingletonTrait;

class Fleet {

	use SingletonTrait;

	/**
	 * The location to the fleet executable.
	 * @var string
	 */
	protected $fleet_executable = '';

	public function __construct()
	{
		$fleet_location = Executer::getInstance()->exec('which fleetctl', false, false, true);
		if (!$fleet_location) {
			throw new FleetException('Fleet could not be found. Please make sure it is installed.');
		}

		$this->fleet_executable = $fleet_location;
	}

	/**
	 * Set Tunnel
	 *
	 * Sets the FleetCTL tunnel. The tunnel is used to connect to one of the servers
	 * in the cluster.
	 *
	 * @param string $tunnel The SSH tunnel. This must include the port if it is not 22.
	 */
	public function setTunnel($tunnel)
	{
		putenv("FLEETCTL_TUNNEL=$tunnel");
	}

	/**
	 * Submit Unit
	 *
	 * Submits a unit to the cluster and then starts it.
	 *
	 * @param string $path The path to submit to the cluster.
	 */
	public function submitUnit($path)
	{
		// Get the filename for the unit.
		$filename = pathinfo($path, PATHINFO_FILENAME);

		Logger::getInstance()->log("Submitting unit {$path} to the cluster.");
		Executer::getInstance()->exec("{$this->fleet_executable} destroy '{$filename}'", true);
		Executer::getInstance()->exec("{$this->fleet_executable} submit '{$path}'", true);
		Executer::getInstance()->exec("{$this->fleet_executable} start '{$filename}'", true);
	}

}

class FleetException extends \Exception {}
