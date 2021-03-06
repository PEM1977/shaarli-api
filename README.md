Shaarli REST API
======

## Installation
* Create mysql database shaarli-api
* cd shaarli-api
* Copy `config.php.dist` into `config.php` and setup your own settings.
* # Run `composer install` (https://getcomposer.org/download/)
* php -r "readfile('https://getcomposer.org/installer');" | php
* php composer.phar install
* Run: php cron.php
  
## Requirements
* PHP 5.4.4
* MySQL or Sqlite
* PDO
* Apache RewriteEngine
* PHP PECL fileinfo for favicons fetching (optional)

## Update your installation
* Update your installation via Git (`git update origin master`) or the [archive file](archive/master.zip).
* Check if there was any changes in [config file](blob/master/config.php.dist), and add settings if necessary.
* Update external libraries with [Composer](https://getcomposer.org/download/). Run: `composer update`.
* Run cron the finalize the update: `php cron.php`.

## Install via SSH exemple (debian)
```
cd /var/www
# Clone repo
git clone https://github.com/mknexen/shaarli-api.git
# Create mysql database
mysqladmin create shaarli-api -p
cd shaarli-api
# Copy `config.php.dist` into `config.php` and setup your own settings.
cp config.php.dist config.php
nano config.php
# Run composer install
php -r "readfile('https://getcomposer.org/installer');" | php
php composer.phar install
# Run cron
php cron.php
```

## API Usage
* /feeds La liste des shaarlis
* /latest Les derniers billets
* /top Les liens les plus partagés
* /search Rechercher dans les billets
* /discussion Rechercher une discussion
* /syncfeeds Synchroniser la liste des shaarlis
* /countlinks Retourne le nombre de shaares enregistrés

## Options
* &format=json
* &pretty=true

## Samples
* Obtenir la liste des flux actifs: http://nexen.mkdir.fr/shaarli-api/feeds?pretty=1
* Obtenir la liste complète des flux: http://nexen.mkdir.fr/shaarli-api/feeds?full=1&pretty=1
* Obtenir les derniers billets http://nexen.mkdir.fr/shaarli-api/latest?pretty=1
* Obtenir le top des liens partagés depuis 48h: http://nexen.mkdir.fr/shaarli-api/top?interval=48h&pretty=1
* Faire une recherche sur php: http://nexen.mkdir.fr/shaarli-api/search?q=php&pretty=1
* Rechercher une discution sur un lien: http://nexen.mkdir.fr/shaarli-api/discussion?url=https://nexen.mkdir.fr/shaarli-river/index.php&pretty=1

## Cron usage

Simple feeds refresh (feeds will be sync if the database is fresh new):

    php cron.php
    
Feeds refresh and seek for new feeds:

    php cron.php --sync
    
Can also be used as a daemon. No output (todo log?). New feeds will be fetch regularly:

    php cron.php --daemon
    
## PEM1977
  * Changed SQL Tables name to RiverEntries and RiverFeeds, since other application (like Wallabag) uses the same names.
  * Table names can be defined in config.php : RIVER_FEEDS_TABLE & RIVER_ENTRIES_TABLE
  * Added a parameter to set the number of displayed entries : ENTRIES_DISPLAYED
  * You may need to edit .htaccess to modify the folder name of your api 
    - RewriteBase /[your api folder name]/
    - RewriteRule ^(.*)$ /[your api folder name]/index.php/$1 [L]
