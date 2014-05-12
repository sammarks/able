# Site Installation Workflow

When a able site:install is called, the following actions are performed:

- The settings file is parsed and some internal keys are added (repository-root).
- The main installer command looks at the site type and gets the appropriate SiteInstaller class to continue the installation process.
- The install() function of the SiteInstaller is called. The rest of the steps in this document are handled in the initial configuration of that function.
- The VirtualHost configuration is created for nginx and saved to that part of the filesystem.
	- Any features are resolved (for example, "php" or "pretty-urls").
	- Additional nginx configuration for the specified environment is added ("production" or "development").
	- Additional configuration from the site configuration file is loaded and added to the nginx configuration.
		- nginx.global.after
		- nginx.global.before
		- nginx.server.after
		- nginx.server.before
