<?php
/**
 * mycitizen.net - Social networking for civil society
 *
 *	Configuration and setup for the mycitizen.net API.
 *	Replaces the functionality of config.ini and bootstrap.php
 *
 *
 * @author http://mycitizen.org
 * @copyright  Copyright (c) 2013, 2014 Burma Center Prague (http://www.burma-center.org)
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */

define('LOG_DIR', realpath(dirname(__FILE__).'/log'));

// Set LOG_REQUESTS to true for debugging of all requests sent to the API. The log file can become large.
define('LOG_REQUESTS', true);

define('WWW_DIR', realpath(dirname(__FILE__).'/../web'));
// next constant for location of nette_sources and lib directories
# define('SHARED_DIR', realpath(dirname(__FILE__)) . '/../../mcn-shared-code');

// multi-site
# define('APP_DIR', SHARED_DIR . '/nette_sources');
# define('LIBS_DIR', SHARED_DIR . '/lib');
# define('LOCALE_DIR', SHARED_DIR . '/locale');
// single-site
define('APP_DIR', WWW_DIR . '/../nette_sources');
define('LIBS_DIR', WWW_DIR . '/../lib');
define('LOCALE_DIR', WWW_DIR . '/../locale');

define('BOOTSTRAP_DIR', WWW_DIR . '/../nette_sources');
define('TEMP_DIR', WWW_DIR . '/../nette_sources/temp');


define('BOOTSTRAP_DIR', WWW_DIR . '/../nette_sources');

require_once LIBS_DIR . '/Nette/loader.php';

NEnvironment::loadConfig(BOOTSTRAP_DIR . '/config.ini');

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

session_set_save_handler(array('SessionDatabaseHandler', 'open'), array('SessionDatabaseHandler', 'close'),
            array('SessionDatabaseHandler', 'read'), array('SessionDatabaseHandler', 'write'),
            array('SessionDatabaseHandler', 'destroy'), array('SessionDatabaseHandler', 'clean'));
$session = NEnvironment::getSession();
$session->setExpiration('+ 4 days');
