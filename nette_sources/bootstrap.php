<?php
// about this version
define('PROJECT_VERSION', '0.10');
define('PROJECT_DATE', '20140526');

session_set_cookie_params(1209600);

// require Nette Framework
require_once LIBS_DIR . '/Nette/loader.php';

// load configuration from config.ini file
NEnvironment::loadConfig(BOOTSTRAP_DIR . '/config.ini');

// displaying or catching exceptions
$application = NEnvironment::getApplication();
$IPs = explode(",", NEnvironment::getConfig('debug')->IPs);

if (NEnvironment::getConfig('debug')->showErrors) {
	NDebug::enable($IPs);
	NDebug::enableProfiler();
	$application->catchExceptions = false;
} else {
	$application->catchExceptions = true;
	$application->errorPresenter = 'Error';
	NDebug::enable(NDebug::PRODUCTION, '%logDir%/php_error.log', NEnvironment::getConfig('debug')->logEmail);
	NEnvironment::setMode('production', TRUE);
}

// settings for the database
ini_set('session.name', NEnvironment::getVariable('SESSION_NAME'));
dibi::connect(array(
    'driver'   => NEnvironment::getConfig('database')->driver,
    'host'     => NEnvironment::getConfig('database')->host,
    'username' => NEnvironment::getConfig('database')->username,
    'password' => NEnvironment::getConfig('database')->password,
    'database' => NEnvironment::getConfig('database')->database,
    'charset'  => 'utf8',
));


$router = $application->getRouter();

//	see class SessionDatabaseHandler in SessionDatabaseHandler.php
session_set_save_handler(array('SessionDatabaseHandler', 'open'), array('SessionDatabaseHandler', 'close'),
            array('SessionDatabaseHandler', 'read'), array('SessionDatabaseHandler', 'write'),
            array('SessionDatabaseHandler', 'destroy'), array('SessionDatabaseHandler', 'clean'));
$session = NEnvironment::getSession();
$session->setExpiration(NEnvironment::getConfig('variable')->sessionExpiration);

// enforce secure connections according to config.ini
if (NEnvironment::getConfig('variable')->SECURED == 1) {
	$flag = NRoute::SECURED;
	$flag_all = NULL;
} elseif (NEnvironment::getConfig('variable')->SECURED == 2) {
	$flag = NRoute::SECURED;
	$flag_all = NRoute::SECURED;
} else {
	$flag = NULL;
	$flag_all = NULL;
}

$rewrite_base = NEnvironment::getConfig('variable')->RewriteBase;
if (substr($rewrite_base, 0, 1) == '/') $rewrite_base = substr($rewrite_base, 1);
if ($rewrite_base != '') $rewrite_base .= '/';

$router[] = new NRoute($rewrite_base . 'index.php', array(
	'presenter' => 'Homepage',
	'action' => 'default',
), NRoute::ONE_WAY);

$router[] = new NRoute($rewrite_base . 'signin/', array(
    'presenter' => 'User',
    'action' => 'login',
    ),$flag);

$router[] = new NRoute($rewrite_base . 'signup/', array(
    'presenter' => 'User',
    'action' => 'register',
    ),$flag);

$router[] = new NRoute($rewrite_base . 'browse/', array(
    'presenter' => 'Widget',
    'action' => 'browse',
    ),$flag_all);

$router[] = new NRoute($rewrite_base . 'mobilecaptcha/', array(
    'presenter' => 'Widget',
    'action' => 'mobilecaptcha',
    ),$flag_all);

$router[] = new NRoute($rewrite_base . 'chat/', array(
    'presenter' => 'Widget',
    'action' => 'messagepopup',
    ),$flag_all);

$router[] = new NRoute($rewrite_base . '<presenter>/<action>/', array(
    'presenter' => 'Homepage',
    'action' => 'default',
    ),$flag_all);

$router[] = new NSimpleRouter(array(
	'presenter' => 'Homepage',
	'action' => 'default',
	),$flag);

// require functions for Gettext substitute
require_once(LIBS_DIR.'/TranslationHelper/TranslationHelper.php');

// all done, we can run the application
$application->run();