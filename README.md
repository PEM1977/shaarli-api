Shaarli REST API
======

## Installation
* Create mysql database shaarli-api
* Copy `config.php.dist` into `config.php` and setup your own settings.
* Run `composer install` (https://getcomposer.org/download/)
* Run: php cron.php

### Requirements
* PHP 5.4.4
* MySQL
* PDO
* Apache RewriteEngine

### Install via SSH exemple (debian)
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
composer install
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

### Options
* &format=json
* &pretty=true
