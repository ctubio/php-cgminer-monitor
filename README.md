php-cgminer-monitor - PHP interface to CGMiner
=====================================
* php-cgminer-monitor enables you to access your CGMiner from anywhere
trough a simple web interface.

Installing
---------------

### Setting up CGMiner
First of all you need to configure CGMiner to allow the computer where you will be running
php-cgminer-monitor from to connect to the CGMiner API.
For help to do this see the [API-README] (https://github.com/ckolivas/cgminer/blob/master/API-README) for CGMiner.

### Setting up php-cgminer-monitor
Download and extract the php-cgminer-monitor archive to a folder accessible for your webserver.
Download Composer into the folder with `composer.json`.

`curl -sS https://getcomposer.org/installer | php`

See https://getcomposer.org/download/ for more information.

Run `php composer.phar` to install the required libraries for php-cgminer-monitor.
Edit `config.php` and add your miners.
Point your browser to http://yourwebserver/path/to/php-cgminer-monitor - enjoy!

System requirements
-------------------
* PHP >= 5.3.7

Screenshot
----------
For example here is the first tab that is the main interface to control all miners together (please, make use of the secondary tabs to control each miner independently):
![solarized palette](https://github.com/ctubio/php-cgminer-monitor/raw/master/screenshot.png)

(yUUUp! a block was found there :kissing_heart:)

License
-------
php-cgminer-monitor is open-sourced software licensed under the MIT License.
