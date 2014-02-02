<?php
// absolute filesystem path to the web root
define('WWW_DIR', realpath(dirname(__FILE__)));
// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/../nette_sources');
// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/../lib');
define('TEMP_DIR', WWW_DIR . '/../nette_sources/temp');

// define('EMAIL_DIR', TEMP_DIR . '/../templates/Emails');

// load bootstrap file
require APP_DIR . '/bootstrap.php';
