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
	 * This configuration section contains information about the setup
	 * and management of websites on the current server.
	 */
	'site' => array(

		/**
		 * webroot_pattern
		 * ---------------
		 *
		 * This is the pattern for determining where the webroot of a website
		 * is going to be. [sitename] will be replaced with the actual website
		 * name.
		 *
		 * For example, if webroot_pattern is set to /var/www/[sitename], and the
		 * name of the site was www.sitename.com, the webroot of that site would be
		 * /var/www/www.sitename.com.
		 */
		'webroot_pattern' => '/var/www/[sitename]/',

		/**
		 * root_domain
		 * -----------
		 *
		 * This is the root domain for all new site instances. Able creates new sites so
		 * that they are recognized at [sitename].[...].[root_domain]. For example, if
		 * the root domain was 'ableengine.com', site URLs would be like the following:
		 *
		 * [sitename].[...].ableengine.com
		 */
		'root_domain' => 'ableengine.com',

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

	),

	'backup' => array(

		'temporary_storage' => array(
			'database' => '/tmp/backup/database',
			'files' => '/tmp/backup/files',
			'patterns' => array(
				'database' => 'backup-%db-%m-%d-%y-%h-%i',
				'files' => 'backup-%path-%m-%d-%y-%h-%i',
			),
			'timestamps' => '/tmp/backup/timestamps',
		),
		'aws' => array(
			'key' => 'changeme',
			'secret' => 'changeme',
			'region' => 'changeme',
			'buckets' => array(
				'database' => 'changeme',
				'files' => 'changeme',
			),
			'patterns' => array(
				'database' => '%server/%y/%m/%d/%file',
				'files' => '%server/%y/%m/%file',
			),
		),
		'files' => array(
			'patterns' => array(
				'include' => array(
					'/var/www/*',
					'/etc/nginx',
				),
				'exclude' => array(
					'glob' => array(),
					'regex' => array(),
				),
			),
		),
		'database' => array(
			'patterns' => array(
				'exclude' => array(
					'mysql',
					'performance_schema',
					'information_schema',
				),
			),
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
		 * The web server currently enabled on the server. There are currently
		 * three values allowed for this section:
		 *
		 *    autoresolve - Have the script try to determine which one is
		 *      already installed. If it can't determine which one is
		 *      installed, it will ask the user for clarification.
		 *    nginx - The nginx webserver is installed on this server.
		 *    apache - The apache webserver is installed on this server.
		 */
		'web_server' => 'autoresolve',

		/**
		 * available_base
		 * --------------
		 *
		 * The base folder for site configurations (enabled or disabled)
		 * to go. [server] will be replaced with the detected webserver,
		 * be it apache or nginx.
		 */
		'available_base' => '/etc/[server]/sites-available',

		/**
		 * enabled_base
		 * ------------
		 *
		 * The base folder for enabled site configurations. Configurations
		 * in this folder will be symbolically linked back to their
		 * configurations in the available_base folder mentioned above.
		 */
		'enabled_base' => '/etc/[server]/sites-enabled',

	),

);
