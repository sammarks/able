#!/bin/bash
# Able Scripts Installer

# What does this script do?
#  - Makes sure the proper dependencies are installed.
#  - Installs the libraries.

echo " - Installing dependencies"

sudo apt-get update -qq
sudo apt-get install php5-cli php5-curl git-core curl drush unzip -y -qq

sudo drush dl drush-7.x-5.x-dev --destination='/usr/share' -y

# Install composer if it has not been already.
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

echo " - Downloading Able"

sudo git clone https://ableengine.git.beanstalkapp.com/ablecore-drupal.git /usr/local/able --quiet
cd /usr/local/able
sudo git checkout able

echo " - Setting up Able"

# Install Composer dependencies
cd /usr/local/able
sudo composer install --quiet

# Set permissions.
sudo chmod a+x /usr/local/able/able

# Add to PATH.
sudo su -c "echo 'PATH=\"/usr/local/able:$PATH\"' >> /etc/bash.profile"
echo "PATH=\"/usr/local/able:$PATH\"" >> ~/.bashrc
PATH="/usr/local/able:$PATH"
export PATH

echo " - Installed! If 'able' cannot be found, you might have to start a new session."
echo " - If this is a new server, the following packages are required:"
echo " - nginx, php5-fpm, php5-mcrypt, php5-mysql, mysql-client, mysql-server, php5-gd"
