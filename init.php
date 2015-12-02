<?php
define('ROOTPATH', __DIR__.'/');
require_once ROOTPATH.'storage/config/app.php';
require_once ROOTPATH.'vendor/autoload.php';
require_once ROOTPATH.'vendor/shfeat/phpquery/phpQuery/phpQuery.php';

set_time_limit(0);
ini_set('display_errors', 'On');
error_reporting(E_ALL);
$GLOBALS['app_config'] = $app_config;