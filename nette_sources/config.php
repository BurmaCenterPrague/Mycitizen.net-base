<?php

##### File needed by API only

define('WWW_DIR', realpath(dirname(__FILE__)));
// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/../nette_sources');
// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/../lib');
define('TEMP_DIR', WWW_DIR . '/../nette_sources/temp');
define('EMAIL_DIR', TEMP_DIR . '/../templates/Emails');

require_once dirname(__FILE__) . '/../lib/Nette/loader.php';
// Step 2: Configure environment
// 2a) enable NDebug for better exception and error visualisation
NDebug::enable();

// 2b) load configuration from config.ini file
NEnvironment::loadConfig();

ini_set('session.name', NEnvironment::getVariable('SESSION_NAME'));
dibi::connect(array(
    'driver'   => NEnvironment::getConfig('database')->driver,
    'host'     => NEnvironment::getConfig('database')->host,
    'username' => NEnvironment::getConfig('database')->username,
    'password' => NEnvironment::getConfig('database')->password,
    'database' => NEnvironment::getConfig('database')->database,
    'charset'  => 'utf8',
));

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
