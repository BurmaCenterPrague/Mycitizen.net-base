<?php
// about this version
define('PROJECT_VERSION', '0.4.1');
define('PROJECT_DATE', '20140311');

session_set_cookie_params(1209600);

require_once dirname(__FILE__) . '/../lib/Nette/loader.php';
// load configuration from config.ini file
NEnvironment::loadConfig();

$application = NEnvironment::getApplication();

if (NEnvironment::getConfig('debug')->showErrors) {
	NDebug::enable(NDebug::DEVELOPMENT);
//	NDebug::enableProfiler();
	$application->catchExceptions = false;
} else {
	$application->catchExceptions = true;
	$application->errorPresenter = 'Error';
}

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

/**
*	See class SessionDatabaseHandler in SessionDatabaseHandler.php
*/
session_set_save_handler(array('SessionDatabaseHandler', 'open'), array('SessionDatabaseHandler', 'close'),
            array('SessionDatabaseHandler', 'read'), array('SessionDatabaseHandler', 'write'),
            array('SessionDatabaseHandler', 'destroy'), array('SessionDatabaseHandler', 'clean'));
$session = NEnvironment::getSession();
$session->setExpiration(NEnvironment::getConfig('variable')->sessionExpiration);

// enforce secure connections
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

$router[] = new NRoute($rewrite_base . '<presenter>/<action>/', array(
    'presenter' => 'Homepage',
    'action' => 'default',
    ),$flag_all);

$router[] = new NSimpleRouter(array(
	'presenter' => 'Homepage',
	'action' => 'default',
	),$flag);


require_once(LIBS_DIR.'/TranslationHelper/TranslationHelper.php');

$application->run();