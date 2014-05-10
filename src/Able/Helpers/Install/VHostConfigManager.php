<?php

namespace Able\Helpers\Install;

use Able\CommandSets\BaseCommand;

class VHostConfigManager {

	protected $vhost_root = '/';
	protected $vhost_config_file = '/etc/nginx/sites-available/default';
	protected $features = array();
	protected $environment = 'development';
	protected $command = null;
	protected $settings = array();

	protected $replacements = array();
	protected $base_replacements = array();

	public function __construct(BaseCommand $command, array $settings = array())
	{
		$this->vhost_root = SCRIPTS_ROOT . '/lib/vhost/';
		$this->command = $command;
		$this->settings = $settings;
		if (array_key_exists('environment', $settings))
			$this->environment = $settings['environment'];

		// Setup the base replacements.
		$this->base_replacements = array(
			'ablecore:base:global:before' => array(),
			'ablecore:base:global:after' => array(),
			'ablecore:base:server:before' => array(),
			'ablecore:base:server:after' => array(),
		);
	}

	public function addFeature(Feature $feature)
	{
		$this->features[] = $feature;
		$this->handleFeature($feature);
	}

	protected function handleFeature(Feature $feature)
	{
		$feature_folder = $feature->getFolderName();

		$base = $this->vhost_root . 'features/' . $feature_folder . '/';
		if (!is_dir($base)) return;

		// Handle the various implementations of the feature.
		$this->handleAllImplementations($base, $feature->getWeight());
	}

	protected function handleAllImplementations($base, $weight)
	{
		$this->handleImplementation($base, 'global.before', $weight);
		$this->handleImplementation($base, 'global.after', $weight);
		$this->handleImplementation($base, 'server.before', $weight);
		$this->handleImplementation($base, 'server.after', $weight);
	}

	protected function handleImplementation($base, $type, $weight)
	{
		if (!is_file($base . $type)) return;
		$contents = file_get_contents($base . $type);
		if ($contents) {
			$this->performReplacements($contents, $this->replacements);
			$replacements_key = 'ablecore:base:' . str_replace('.', ':', $type);
			$this->base_replacements[$replacements_key][] = array(
				'weight' => $weight,
				'contents' => $contents,
			);
		}
	}

	protected function getImplementation($base, $type, Feature $feature)
	{
		if (!is_file($base . $type)) return false;
		$contents = file_get_contents($base . $type);
		if ($contents) {
			$this->performReplacements($contents, $this->replacements);
			return array(
				'weight' => $feature->getWeight(),
				'contents' => $contents,
			);
		}
		return false;
	}

	protected function build()
	{
		// Load the base file...
		$base_file_path = $this->vhost_root . 'base';
		if (!is_file($base_file_path)) {
			throw new VHostConfigManagerException('The base config file does not exist at ' . $base_file_path);
		}
		if ($contents = file_get_contents($base_file_path)) {

			// Perform the replacements for the environment.
			$environment_base = $this->vhost_root . 'environments/' . $this->environment . '/';
			if (is_dir($environment_base)) {
				// Make sure the environment always comes first.
				$this->handleAllImplementations($environment_base, -100);
			}

			// Add the replacments from the configuration directory.
			if (array_key_exists('repository-root', $this->settings)) {
				$config_dir = $this->settings['repository-root'] . 'config/';
				if (is_dir($config_dir)) {
					// Make sure the repository configuration comes last.
					$this->handleAllImplementations($config_dir, 100);
				} else {
					throw new VHostConfigManagerException('The config root: ' . $config_dir . ' does not exist.');
				}
			}

			// Reorder the base replacements...
			$this->orderBaseReplacements();

			// Perform the regular replacements on the file...
			$this->performReplacements($contents, $this->replacements);
			$this->performReplacements($contents, $this->base_replacements);

			// Now write the file to the correct place...
			if (!file_put_contents($this->vhost_config_file, $contents)) {
				throw new VHostConfigManagerException('Could not save the contents of ' . $this->vhost_config_file);
			}

		} else {
			throw new VHostConfigManagerException('Could not get contents of ' . $base_file_path);
		}
	}

	protected function orderBaseReplacements()
	{
		foreach ($this->base_replacements as $type => $replacements) {
			usort($replacements, function($a, $b) {
				return $a['weight'] - $b['weight'];
			});
			$new_replacements = array();
			foreach ($replacements as $single) {
				$new_replacements[] = $single;
			}
			$this->base_replacements[$type] = $new_replacements;
		}
	}

	protected function performReplacements(&$file_contents, array $replacements)
	{
		$search = array();
		$replace = array();
		foreach ($replacements as $key => $replacement) {
			if (is_array($replacement)) {
				$replacement = implode(PHP_EOL, $replacement);
			}
			$search[] = '[' . trim($key, '[]') . ']';
			$replace[] = $replacement;
		}
		$file_contents = str_replace($search, $replace, $file_contents);
	}

	protected function buildReplacements()
	{
		// If we've already built the replacements, return.
		if (count($this->replacements) > 0) return;

		// Get the webroot and FQDN.
		$this->replacements['ablecore:webroot'] = $this->settings['webroot'];
		$this->replacements['ablecore:sitefullname'] = $this->settings['fqdn'];

		// Generate the parts of the FQDN.
		$fqdn_segments = explode('.', $this->settings['fqdn']);

		// Strip the first segment.
		$this->replacements['ablecore:siteaddress'] = implode('.', array_slice($fqdn_segments, 1));
		$this->replacements['ablecore:sitename'] = implode('.', array_slice($fqdn_segments, 1, count($fqdn_segments) - 2));
	}

} 

class VHostConfigManagerException extends \Exception {}
