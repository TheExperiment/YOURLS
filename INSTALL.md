Installation
============

Requirements
------------
* [PHP](http://www.php.net/) version **5.3** or greater
* [MySQL](http://www.mysql.com/) version **5.0** or greater
* **`mod_rewrite`** enabled if [Apache](http://httpd.apache.org/) Server

Highly recommended:
* PHP [cURL](http://www.php.net/curl) extension installed if you plan on playing with the API

Fresh Install
-------------

1.  Download the latest [release](https://github.com/YOURLS/YOURLS/releases)
2.  Unzip the YOURLS archive
3.  Copy `user/config-sample.php` to `user/config.php`
4.  Open `user/config.php` with a raw text editor (like Notepad) and fill in the required settings
5.  Upload the unzipped files to your server, into `public_html` or `www` folder for example
6.  Create a new database (see [Configuration](#config) &ndash; you can also use an existing one)
7.  Point your browser to `http://yoursite.com/yourls-install.php`

Upgrade
-------
1.  **Backup the database!**
2.  Unzip the YOURLS archive
3.  Upload files to your server, overwriting your existing install
4.  Point your browser to your admin interface, eg: `http://yoursite.com/admin/`
