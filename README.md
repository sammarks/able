# Able

Able is a PHP CLI application aimed at automating the management of many Drupal instances.

**Important:** Currently, Able is only supported on Linux (preferably Ubuntu) installations as
it heavily relies on CLI applications and aptitude.

## Installation

To install the Able CLI, run the following command:

	wget -O - -o /dev/null http://ablecore.ae-lin-dev-1.ableengine.com/install.sh | bash

## Expected Server Setup

**Important:** If you're using nginx, the scripts expect an upstream called `php_connection` in a
`php-fpm.conf` file inside the nginx `conf.d` directory. Here's an example of what this file would
look like:

	upstream php_connection {
		# either this:
		server unix:/var/run/php5-fpm.sock;

		# or this:
		server 127.0.0.1:9000;
	}

The configuration will be added, but it will not function unless you set this up properly.
