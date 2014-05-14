<?php

namespace Able\Helpers\Install\ConfigurationManagers;

use Able\Helpers\Install\Features\Feature;

abstract class FileConfigurationManager extends ConfigurationManager {

	protected $base_file = null;
	protected $replacements = array();
	protected $base_replacements = array();
	protected $base_replacements_keys = array();

	public function postInitialize()
	{
		parent::postInitialize();
		$this->base_file = $this->getBaseFile();

		// Make sure the base file exists.
		if (!is_file($this->base_file)) {
			throw new FileConfigurationManagerException('The base file ' . $this->base_file . ' does not exist.');
		}

		// Setup the base replacements array.
		$base_replacements_list = $this->getBaseReplacementList();
		foreach ($base_replacements_list as $replacement) {
			$base_replacement_key = 'ablecore:base:' . str_replace('.', ':', $replacement);
			$this->base_replacements_keys[$replacement] = $base_replacement_key;
			$this->base_replacements[$base_replacement_key] = array();
		}

		// Build the replacements.
		$this->buildReplacements();
	}

	protected abstract function getBaseFile();
	protected abstract function getBaseReplacementList();

	protected function prepareFeatureImplementation(&$contents) {}

	protected function handleFeature(Feature $feature)
	{
		$feature_folder = $this->getFeatureConfigurationFolder($feature);
		if (!$feature_folder) return;

		// Handle the various implementations of the feature.
		$weight = $feature->getWeight($this);
		foreach ($this->base_replacements_keys as $type) {
			$this->handleImplementation($feature_folder, $type, $weight);
		}
	}

	protected function handleImplementation($base, $type, $weight)
	{
		if (!is_file($base . $type)) return;
		if ($contents = file_get_contents($base . $type)) {
			$this->prepareFeatureImplementation($contents);
			$this->performReplacements($contents, $this->replacements);
			$replacements_key = $this->base_replacements_keys[$type];
			$this->base_replacements[$replacements_key][] = array(
				'weight' => $weight,
				'contents' => $contents,
			);
		}
	}

	protected function performReplacements(&$contents, array $replacements = array())
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
		$contents = str_replace($search, $replace, $contents);
	}

	protected function buildReplacements()
	{
		// Get the webroot and FQDN.
		$this->replacements['ablecore:webroot'] = $this->settings['webroot'];
		$this->replacements['ablecore:sitefullname'] = $this->settings['fqdn'];

		// Generate the parts of the FQDN.
		$fqdn_segments = explode('.', $this->settings['fqdn']);

		// Strip the first segment.
		$this->replacements['ablecore:siteaddress'] = implode('.', array_slice($fqdn_segments, 1));
		$this->replacements['ablecore:sitename'] = implode('.', array_slice($fqdn_segments, 1, count($fqdn_segments) - 2));
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

	public function build()
	{
		if ($contents = file_get_contents($this->base_file)) {

			// Handle all the features.
			foreach ($this->features as $feature) {
				$this->handleFeature($feature);
			}

			// Reorder the base replacements...
			$this->orderBaseReplacements();

			// Perform the replacements on the file.
			$this->performReplacements($contents, $this->replacements);
			$this->performReplacements($contents, $this->base_replacements);

			// Finally, return the contents.
			return $contents;

		} else {
			throw new FileConfigurationManagerException('Could not get contents of ' . $this->base_file);
		}
	}

}

class FileConfigurationManagerException extends \Exception {}
