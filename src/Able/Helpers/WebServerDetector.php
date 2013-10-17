<?php

namespace Able\Helpers;

class WebServerDetector
{

	/**
	 * Detects which type of web server is currently
	 * being used.
	 *
	 * @param  object $command The current command object.
	 *
	 * @return string          The name of the web server in use.
	 */
	public static function detect($command)
	{

		$directory_listing = $command->exec("ls -a /etc/ | tr '\\n' '\\n'", true, false, true);

		$web_server = '';
		if ($command->config['server']['web_server'] == 'autoresolve') {
			$apache_found = false;
			$nginx_found = false;
			foreach ($directory_listing as $folder) {
				if ($folder == 'apache2') {
					$apache_found = true;
					continue;
				}
				if ($folder == 'nginx') {
					$nginx_found = true;
					continue;
				}
			}

			$suggested_server = '';
			if ($nginx_found && !$apache_found) {
				$suggested_server = 'nginx';
			} elseif (!$nginx_found && $apache_found) {
				$suggested_server = 'apache2';
			} elseif ($apache_found && $nginx_found) {
				while ($suggested_server != 'nginx' && $suggested_server != 'apache2') {
					$response = $command->prompt("Both apache and nginx were detected. Which one would you like to use? [nginx/apache2]",
						true);
					switch ($response) {
						case 'nginx':
							$suggested_server = 'nginx';
							break;
						case 'apache2':
							$suggested_server = 'apache2';
							break;
						default:
							$command->log("{$response} is an invalid option.");
					}
				}
			}
			$web_server = $suggested_server;
		} elseif ($command->config['server']['web_server'] == 'apache') {
			$web_server = 'apache2';
		} elseif ($command->config['server']['web_server'] == 'nginx') {
			$web_server = 'nginx';
		} else {
			$server = $command->config['server']['web_server'];
			$command->error("There is an error in your configuration. The web server: {$server} is not acceptable. Accepted values are: apache2, nginx and autoresolve",
				true);
		}

		return $web_server;

	}

}
