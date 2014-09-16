<?php
require_once dirname(__FILE__).'/../../nette_sources/config.php';

spl_autoload_register(); // don't load our classes unless we use them

$mode = 'debug'; // 'debug' or 'production'
$server = new RestServer($mode);
$server->cacheDir = TEMP_DIR;
error_reporting(E_ALL);

// $server->refreshCache(); // uncomment momentarily to clear the cache if classes change in production mode

$server->addClass('API_Base','/API/Base');

$server->handle();
