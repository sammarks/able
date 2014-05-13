<?php

/**
 * Configuration
 * -------------
 *
 * This configuration file contains all the server-wide settings and is
 * not meant to be changed on a per-site basis.
 */

return array(

	/**
	 * Site Configuration
	 * ------------------
	 *
	 * This configuration section contains the default values for
	 * any website.
	 */
	'site' => array(

		/**
		 * The title of the website. Must be human readable.
		 */
		'title' => 'Test Site',

		/**
		 * The environment to use for the website. Can either be 'Production', 'Staging', or 'Development'.
		 * Must correspond with a feature name.
		 */
		'environment' => 'Development',

		/**
		 * The fully-qualified domain name for the website.
		 */
		'fqdn' => 'www.testsite.com',

		/**
		 * The webroot folder for the website (not including webroot_folder).
		 */
		'webroot' => 'testsite.com',

		/**
		 * The folder to contain webroots. This is a separate configuration option because it
		 * is rarely changed.
		 */
		'webroot_folder' => '/var/www',

		/**
		 * The configuration managers go here, along with additional configuration options for each.
		 */
		'configuration' => array(
			'VHost' => array(),
			'FPMPool' => array(
				'max_children' => 20,
			),
			'FPM' => array(),
			'PHP' => array(),
		),

		'features' => array(
			'Drupal7' => array(

				/**
				 * modules
				 * -------
				 *
				 * This array has two sub-items: disable and enable. When creating a site, the modules
				 * listed in the 'disable' array are disabled. Modules listed in the 'enable' array
				 * are enabled.
				 */
				'modules' => array(
					'disable' => array(
						'overlay',
						'toolbar',
						'update',
					),
					'enable' => array(
						'module_filter',
						'admin_menu',
						'adminimal_admin_menu',
						'ctools',
						'libraries',
						'smtp',
						'ckeditor',
						'less',
						'conditional_styles',
						'field_collection',
						'token',
						'entity',
						'pathauto',
						'features',
						'defaultcontent',
						'context',
						'ac_global',
						'xautoload',
					),
				),

				/**
				 * themes
				 * ------
				 *
				 * The themes to enable when installing the site. Can be any available theme and
				 * can be either for the administration side or frontend.
				 */
				'themes' => array(
					'administration' => 'adminimal',
					'frontend' => 'bartik',
				),

				/**
				 * profile
				 * -------
				 *
				 * The Drupal profile to install. Can be any profile supported by Drupal and Drush.
				 */
				'profile' => 'standard',

				/**
				 * default_credentials
				 * -------------------
				 *
				 * The default credentials to use when installing the site.
				 */
				'default_credentials' => array(
					'email' => 'admin@example.org',
					'username' => 'admin',
					'password' => 'admin',
				),
			)
		),

	),

	/**
	 * Server Configuration
	 * --------------------
	 *
	 * This configuration section outlines where specific items are
	 * located on the server.
	 */
	'server' => array(

		/**
		 * web_server
		 * ----------
		 *
		 * The web server currently enabled on the server. Currently, nginx is the only
		 * supported web server.
		 *
		 *    nginx - The nginx webserver is installed on this server.
		 */
		'web_server' => 'nginx',

		'configuration' => array(
			'VHost' => '/etc/nginx/sites-available/default',
			'FPMPool' => '/etc/php5/fpm/pool.d/www.conf',
			'FPM' => '/etc/php5/fpm/php-fpm.conf',
			'PHP' => '/etc/php5/fpm/php.ini',
		)

	),

);
