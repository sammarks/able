# Virtual Host Configurations

## Variables Available

All examples have to do with a site at `http://www.testsite.com`.

- `[ablecore:siteaddress]` - The address to the website. - `testsite.com`
- `[ablecore:sitename]` - The name of the site. - `testsite`
- `[ablecore:sitefullname]` - The full name of the site. - `www.testsite.com`
- `[ablecore:webroot]` - The webroot for the site (specified in the configuration).

The following variables are only available in the base file.

- `[ablecore:base:global:before]` - Content to add before global content.
- `[ablecore:base:global:after]` - Content to add after global content.
- `[ablecore:base:server:before]` - Content to add at the beginning of the main server block.
- `[ablecore:base:server:after]` - Content to add at the end of the main server block.
