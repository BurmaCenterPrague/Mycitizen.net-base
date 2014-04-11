<?php
// absolute filesystem path to the web root
define('WWW_DIR', realpath(dirname(__FILE__)));
//define('SHARED_DIR', realpath(dirname(__FILE__)) . '/../../mcn-shared-code');

// multi-site
# define('APP_DIR', SHARED_DIR . '/nette_sources');
# define('LIBS_DIR', SHARED_DIR . '/lib');
// single-site
define('APP_DIR', WWW_DIR . '/../nette_sources');
define('LIBS_DIR', WWW_DIR . '/../lib');

define('BOOTSTRAP_DIR', WWW_DIR . '/../nette_sources');
define('TEMP_DIR', BOOTSTRAP_DIR . '/temp');
define('LOCALE_DIR', WWW_DIR . '/../locale');

// load bootstrap file
require BOOTSTRAP_DIR . '/bootstrap.php';
