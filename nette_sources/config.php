<?php

##### File needed by API only

define('WWW_DIR', realpath(dirname(__FILE__)));
// next constant for location of nette_sources and lib directories
# define('SHARED_DIR', realpath(dirname(__FILE__)) . '/../../mcn-shared-code');

// multi-site
# define('APP_DIR', SHARED_DIR . '/nette_sources');
# define('LIBS_DIR', SHARED_DIR . '/lib');
// single-site
define('APP_DIR', WWW_DIR . '/../nette_sources');
define('LIBS_DIR', WWW_DIR . '/../lib');

define('BOOTSTRAP_DIR', WWW_DIR . '/../nette_sources');
define('TEMP_DIR', WWW_DIR . '/../nette_sources/temp');
define('LOCALE_DIR', WWW_DIR . '/../locale');


require_once LIBS_DIR . '/Nette/loader.php';
// Step 2: Configure environment
// 2a) enable NDebug for better exception and error visualisation
// NDebug::enable();

// 2b) load configuration from config.ini file
NEnvironment::loadConfig(BOOTSTRAP_DIR . '/config.ini');

ini_set('session.name', NEnvironment::getVariable('SESSION_NAME'));
dibi::connect(array(
    'driver'   => NEnvironment::getConfig('database')->driver,
    'host'     => NEnvironment::getConfig('database')->host,
    'username' => NEnvironment::getConfig('database')->username,
    'password' => NEnvironment::getConfig('database')->password,
    'database' => NEnvironment::getConfig('database')->database,
    'charset'  => 'utf8',
));

require_once(LIBS_DIR.'/TranslationHelper/TranslationHelper.php');

// Step 3: Configure application
// 3a) get and setup a front controller
//$application = NEnvironment::getApplication();
// 3b) establish database connection
//$application->onStartup[] = 'Albums::initialize';

// Step 4: Setup application router                  
//$router = $application->getRouter();
session_set_save_handler(array('SessionDatabaseHandler', 'open'), array('SessionDatabaseHandler', 'close'),
            array('SessionDatabaseHandler', 'read'), array('SessionDatabaseHandler', 'write'),
            array('SessionDatabaseHandler', 'destroy'), array('SessionDatabaseHandler', 'clean'));
$session = NEnvironment::getSession();
$session->setExpiration('+ 4 days');

?>
