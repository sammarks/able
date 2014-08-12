<?php

namespace Able\Helpers\Cluster\Operations;

use Able\Helpers\Cluster\Providers\ProviderFactory;
use Able\Helpers\Logger;

class CreateOperation extends Operation {

	/**
	 * The etcd discovery url.
	 * @var string
	 */
	protected $discovery_url = '';

	public function create()
	{
		/** @var Logger $logger */
		$logger = Logger::getInstance();
		$logger->log('CREATE cluster ' . $this->config->name);

		// Prepare the etcd discovery url.
		$this->prepareEtcd();

		// Create each of the nodes...
		foreach ($this->config->get('nodes') as $node_identifier => $node) {
			$node['full-identifier'] = $this->config->name . '-' . $node_identifier;
			$node['cluster'] = $this->config->name;
			$this->createNode($node_identifier, $node);
		}
	}

	protected function createNode($identifier, array $configuration = array())
	{
		// Fill in the defaults for the configuration.
		$defaults = $this->config->get('defaults');
		$configuration = array_replace_recursive($defaults, $configuration);

		// Get the provider.
		$provider_name = $configuration['provider'];
		if (!array_key_exists($provider_name, $configuration)) {
			throw new \Exception('There isn\'t any configuration for the ' . $provider_name . ' provider.');
		}

		/** @var ProviderFactory $provider_factory */
		$provider_factory = ProviderFactory::getInstance();
		$provider = $provider_factory->provider($provider_name, $identifier, $configuration);

		// Generate the default metadata for the node.
		$metadata = $provider->getMetadata();
		$metadata = array_replace_recursive($metadata, $configuration['metadata']);

		// Prepare the metadata string and add it to the cloud-config.
		$metadata_string = $this->encodeMetadata($metadata);
		$configuration['cloud-config']['coreos']['fleet']['metadata'] = $metadata_string;
		$configuration['cloud-config']['coreos']['etcd']['discovery'] = $this->discovery_url;

		// Call the provider create function.
		$provider->setNodeSettings($configuration); // Refresh the configuration.
		$provider->createNode();
	}

	protected function encodeMetadata(array $metadata)
	{
		$strings = array();
		foreach ($metadata as $key => $value) {
			$strings[] = $key . '=' . $value;
		}

		return implode(',', $strings);
	}

	protected function prepareEtcd()
	{
		$url = $this->config->get('etcd-url');
		if ($url == 'generate') {
			if ($contents = file_get_contents('https://discovery.etcd.io/new')) {
				$this->discovery_url = $contents;
			} else {
				throw new \Exception('There was an error generating a discovery URL for the cluster.');
			}
		} else {
			$this->discovery_url = $url;
		}
	}

} 