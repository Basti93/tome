<?php

define('LARAVEL_START', microtime(true));

// Suppress deprecation notices from vendor code (PHP 8.5 + PDO::MYSQL_ATTR_SSL_CA)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require __DIR__.'/../vendor/autoload.php';
