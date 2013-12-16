<?php

define('PROJECT_VERSION', '0.2.1 beta');
session_set_cookie_params(1209600);

require_once dirname(__FILE__) . '/../lib/Nette/loader.php';
// load configuration from config.ini file
NEnvironment::loadConfig();

// enable NDebug
// NDebug::enable(NEnvironment::getConfig('debug')->IPs, LOG_DIRECTORY, NEnvironment::getConfig('debug')->logEmail);


if (NEnvironment::getConfig('debug')->showErrors) {
	NDebug::enable(NDebug::DEVELOPMENT);
//	NDebug::enableProfiler();
	NEnvironment::getApplication()->catchExceptions = false;
 } else {
 	error_reporting(0);
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

$application = NEnvironment::getApplication();

$router = $application->getRouter();

/**
*	See class SessionDatabaseHandler in SessionDatabaseHandler.php
*/
session_set_save_handler(array('SessionDatabaseHandler', 'open'), array('SessionDatabaseHandler', 'close'),
            array('SessionDatabaseHandler', 'read'), array('SessionDatabaseHandler', 'write'),
            array('SessionDatabaseHandler', 'destroy'), array('SessionDatabaseHandler', 'clean'));
$session = NEnvironment::getSession();
$session->setExpiration(NEnvironment::getConfig('variable')->sessionExpiration);

if (NEnvironment::getConfig('variable')->secured == 1) {
	$flag = NRoute::SECURED;
	$flag_all = NULL;
} elseif (NEnvironment::getConfig('variable')->secured == 2) {
	$flag = NRoute::SECURED;
	$flag_all = NRoute::SECURED;
} else {
	$flag = NULL;
	$flag_all = NULL;
}

$router[] = new NRoute('index.php', array(
	'presenter' => 'Homepage',
	'action' => 'default',
), NRoute::ONE_WAY);

$router[] = new NRoute('signin/', array(
    'presenter' => 'User',
    'action' => 'login',
    ),$flag);

$router[] = new NRoute('signup/', array(
    'presenter' => 'User',
    'action' => 'register',
    ),$flag);

$router[] = new NRoute('<presenter>/<action>/', array(
    'presenter' => 'Homepage',
    'action' => 'default',
    ),$flag_all);

$router[] = new NSimpleRouter(array(
	'presenter' => 'Homepage',
	'action' => 'default',
	),$flag);

$application->run();
