<?php
require_once dirname(__FILE__).'/../../nette_sources/config.php';

spl_autoload_register(); // don't load our classes unless we use them

$mode = 'debug'; // 'debug' or 'production'
$server = new RestServer($mode);

// $server->refreshCache(); // uncomment momentarily to clear the cache if classes change in production mode

$server->addClass('API_Base','/API/Base');
//$server->addClass('API_Camera','/API/Camera');
//$server->addClass('ProductsController', '/products'); // adds this as a base to all the URLs in this class

$server->handle();
?>
