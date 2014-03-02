<?php
/**
 * mycitizen.net - Social networking for civil society
 *
 * installation and update script
 * last update: 28 February 2014
 *
 * @author Christoph Amthor, http://mycitizen.org
 * @copyright  Copyright (c) 2014 Burma Center Prague (http://www.burma-center.org)
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 *
 */

####
// error_reporting(0);


// folders that need to be created, relative to installation root, with prepended slash
$create_folders = array(
		'/nette_sources/temp' => '0755',
		'/nette_sources/log' => '0755',
		'/web/images/cache' => '0755',
		'/web/images/uploads' => '0755',
		'/web/images/cache/user' => '0755',
		'/web/images/cache/group' => '0755',
		'/web/images/cache/resource' => '0755'
	);

// files that need to be renamed (starting with dot and might therefore get lost if provided with final name)
// not including sample files that will be used only for first-time installations
$rename_files = array(
		'/dist.htaccess'	=> '/.htaccess',
		'/web/dist.htaccess' => '/web/.htaccess',
		'/web/API/dist.htaccess' => '/web/API/.htaccess',
		'/web/files/dist.htaccess' => '/web/files/.htaccess',
		'/web/images/dist.htaccess' => '/web/images/.htaccess'
	);

// update scripts for database - 'all' for first-time installations, otherwise incrementally, starting from current version until last in array
// each script sets database version stored in table system to a new value
$database_sql = array(
		'all' => '/doc/database.sql',
		'none' => '/doc/update-unversioned.sql', // old betas
		'0.3.1' => '/doc/update-0.3.1.sql'
	);

// default values in dist.config.ini that can be replaced; regular expressions
$placeholders = array(
		'variable.PROJECT_NAME' => '"mycitizen\.net"',
		'database.host' => 'localhost',
		'variable.SECURED' => '0|1|2',
		'variable.EXTERNAL_JS_CSS' => '0|1',
		'variable.CHECK_STOP_FORUM_SPAM' => '0|1'
	);

session_start();

if (isset($_GET["step"])) {
	$step = $_GET["step"];
	$settings = $_SESSION['installation_settings'];
} else {
	$settings = array();
	$settings['base_path'] = dirname(__FILE__);
    
	$request_uri = explode("/",$_SERVER['REQUEST_URI']);
    $settings['variable.RewriteBase'] = "/".$request_uri[1];
    	
//	$ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? true:false;
//    $sp = strtolower($_SERVER['SERVER_PROTOCOL']);
//    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $_SERVER['SERVER_PORT'];
    $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
    $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
    $settings['variable.URI'] = $host . $port;


    // Time Zone
    $settings['php.date.timezone'] = date_default_timezone_get();
}

if (isset($_POST) && count($_POST)) {
	foreach ($_POST as $key => $value) {
		$key = str_replace("variable_","variable.",$key);
		$key = str_replace("database_","database.",$key);
		$key = str_replace("php_date_","php.date.",$key);
		if ($value == '' && $key != 'RewriteBase') {
		var_dump($value);
			$problem_key = $key;
			$step = 'form_error';
			break;
		}
		$settings[$key] = $value;
	}
}
if (!empty($settings['admin_email'])) $settings['debug.logEmail'] = $settings['admin_email'];
$_SESSION['installation_settings'] = $settings;

?>
<!DOCTYPE HTML>
<html>
<head>
	<title>Installation script</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex,nofollow" />
	<link rel="stylesheet" media="screen" href="./web/css/mycitizen.min.css" />
	<link href='./web/css/OpenSans/OpenSans.css' rel='stylesheet' type='text/css'>
	<style>
		.bad {color:red;}
		.good {color:green;}
		.error {color:red;}
	</style>
</head>
<body>
<div id="layout">
	<div id="topheader">
		<div class="logo">
			<h1><img src="./web/images/logo.png" alt="logo" width="120" /></h1>
		</div>
		<div s style="padding:20px 50px 10px 250px;">
			<h2>Mycitizen.net Installation Script</h2>
		</div>
	</div>
<div class="content" style="margin-top:30px;">

	<div style="background-color:#FFF; min-height:100px; border-radius:25px; box-shadow: 0px 0px 16px; padding:20px;">
<?php

switch ($step) {
case "check":

	$problem_found = false;
	$check[] = array(
		'label' => 'PHP version',
		'value' => PHP_VERSION,
		'ok' => version_compare(PHP_VERSION, '5.2.0', '>=')
		);
/*
	$MySQL_version = execute_query("SELECT VERSION() as mysql_version");
	$check[] = array(
		'label' => 'MySQL version',
		'value' => $MySQL_version,
		'ok' => version_compare($MySQL_version, '5.2.0', '>=')
		);
*/
	$check[] = array(
		'label' => 'Maximum execution time',
		'value' => ini_get('max_execution_time') . 's',
		'ok' => ini_get('max_execution_time')>60
		);
	$memory_limit = ini_get('memory_limit');
	$check[] = array(
		'label' => 'Memory limit',
		'value' => $memory_limit,
		'ok' => setting_to_bytes($memory_limit ) >= 0x4000000
		);
	$check[] = array(
		'label' => 'GD Library',
		'value' => function_exists("ImageCreateTrueColor") ? "yes" : "no",
		'ok' => function_exists("ImageCreateTrueColor")
		);

	echo "<h1>System Check</h1>";
	echo "<table><tr><td colspan='2'></td>";	
	foreach ($check as $item) {
		$class = $item['ok'] ? 'good' : 'bad';
		if (!$item['ok']) $problem_found = true;
		echo '</tr><tr><td style="width:300px;"><h4>'.$item['label'].':</h4></td><td class="'.$class.'"><h4>'.$item['value'].'</h4></td>';
	}
	echo "</tr></table>";

	
	switch ($problem_found) {
		case false: echo '<h3>All looks good. Please continue.</h3>'; break;
		case true: echo '<h3>Have have found some problems. Please consider solving them before you continue.</h3>'; break;
	}
	
	$link_backward = array('label' => "Start" , 'url' => "install.php");
	$link_forward = array('label' => "Setup" , 'url' => "install.php?step=setup");
break;

case "setup":
	// set defaults
	if (empty($settings['variable.CRON_TOKEN'])) $settings['variable.CRON_TOKEN'] = md5(time());
	if (empty($settings['database.host'])) $settings['database.host'] = 'localhost';
	if (empty($settings['variable.SECURED'])) $settings['variable.SECURED'] = 2;
	if (empty($settings['variable.CHECK_STOP_FORUM_SPAM'])) $settings['variable.CHECK_STOP_FORUM_SPAM'] = 1;
	if (empty($settings['variable.EXTERNAL_JS_CSS'])) $settings['variable.EXTERNAL_JS_CSS'] = 1;
	
	// prepare select elements
	$secured_option[$settings['variable.SECURED']] = 'selected';
	$sfs_option[$settings['variable.CHECK_STOP_FORUM_SPAM']] = 'selected';
	$jscss_option[$settings['variable.EXTERNAL_JS_CSS']] = 'selected';
	
	echo "<h1>Setup</h1>";
	echo "<h2>Please double-check and complete the following settings.</h2>";
	// display form to enter required information (database, admin), verify URL
	echo '<form method="post" action="install.php?step=install">';
	echo '<table style="width:100%;">';
	echo '<tr><td colspan="2"><h3>Deployment</h3></td></tr>';
	echo '<tr><td>Project name:</td><td><input type="text" name="variable.PROJECT_NAME" value="'.$settings['variable.PROJECT_NAME'].'" style="width:200px;"/></td><td></td></tr>';
	echo '<tr><td>Project description:</td><td><input type="text" name="variable.PROJECT_DESCRIPTION" value="'.$settings['variable.PROJECT_DESCRIPTION'].'" style="width:400px;"/></td><td></td></tr>';
	echo '<tr><td colspan="2"><h3>Server</h3></td></tr>';
	echo '<tr><td>Absolute base path:</td><td><input type="text" name="base_path" value="'.$settings['base_path'].'" style="width:400px;"/></td><td>e.g. <b>/www/mycitizen.net/</b></td></tr>';
	echo '<tr><td>URL of the deployment:</td><td><input type="text" name="variable.URI" value="'.$settings['variable.URI'].'" style="width:400px;"/></td><td>e.g. <b>www.mydeployment.com</b> or <b>localhost</b> - without http:// or https://</td></tr>';
	echo '<tr><td>Rewrite base:</td><td><input type="text" name="RewriteBase" value="'.$settings['variable.RewriteBase'].'" style="width:400px;"/></td><td>e.g. <b>/mcn</b> if installed at http://localhost/mcn; can be empty</td></tr>';
	echo '<tr><td>Time Zone:</td><td><input type="text" name="php.date.timezone" value="'.$settings['php.date.timezone'].'" style="width:100px;"/></td><td>e.g. <b>UTC</b>, see the <a href="http://cz2.php.net/timezones" target="_blank">list at php.net</a></td></tr>';
	
	echo '<tr><td colspan="2"><h3>MySQL</h3></td></tr>';
	echo '<tr><td>Database host:</td><td><input type="text" name="database.host" value="'.$settings['database.host'].'" style="width:300px;"/></td><td>e.g. <b>localhost</b></td></tr>';
	echo '<tr><td>Database name:</td><td><input type="text" name="database.database" value="'.$settings['database.database'].'" style="width:300px;"/></td><td></td></tr>';
	echo '<tr><td>Database user:</td><td><input type="text" name="database.username" value="'.$settings['database.username'].'" style="width:300px;"/></td><td></td></tr>';
	echo '<tr><td>Database password:</td><td><input type="text" name="database.password" value="'.$settings['database.password'].'" style="width:300px;"/></td><td></td></tr>';
	echo '<tr><td colspan="2"><h3>Administrator</h3></td></tr>';
	echo '<tr><td>Admin login:</td><td><input type="text" name="admin_login" value="'.$settings['admin_login'].'" style="width:200px;"/></td><td>Avoid names that can easily be guessed, like <b>admin</b> or <b>root</b>.</td></tr>';
	echo '<tr><td>Admin email:</td><td><input type="text" name="admin_email" value="'.$settings['admin_email'].'" style="width:200px;"/></td><td>This will also be used for error messages.</td></tr>';
	echo '<tr><td>Admin password:</td><td><input type="text" name="admin_password" value="'.$settings['admin_password'].'" style="width:200px;"/></td><td>This password should be really good: long and hard to guess.</td></tr>';
	echo '<tr><td colspan="2"><h3>Further Options</h3></td></tr>';
	echo '<tr><td>Link to TOCs, FUP or your "house rules":</td><td><input type="text" name="variable.TOC_URL" value="'.$settings['variable.TOC_URL'].'" style="width:350px;"/></td><td>e.g. <b>http://www.mydeployment.com/rules/</b></td></tr>';
	echo '<tr><td>Link to your privacy policy:</td><td><input type="text" name="variable.PP_URL" value="'.$settings['variable.PP_URL'].'" style="width:350px;"/></td><td>e.g. <b>http://www.mydeployment.com/privacy/</b></td></tr>';
	echo '<tr><td>Link where users can find help:</td><td><input type="text" name="variable.SUPPORT_URL" value="'.$settings['variable.SUPPORT_URL'].'" style="width:350px;"/></td><td>e.g. <b>http://www.mydeployment.com/support/</b></td></tr>';
	echo '<tr><td>Token to process CRON jobs:</td><td><input type="text" name="variable.CRON_TOKEN" value="'.$settings['variable.CRON_TOKEN'].'" style="width:200px;"/></td><td><a href="https://mycitizen.net/manual:cron" target="_blank">info</a></td></tr>';
	echo '<tr><td>Secure your installation with SSL (https):</td><td><select name="variable.SECURED"><option value="0" '.$secured_option[0].'>no</option><option value="1" '.$secured_option[1].'>only where password is sent</option><option value="2" '.$secured_option[2].'>all pages</option></select></td><td></td></tr>';
	echo '<tr><td>Block signups of known spammers:</td><td><select name="variable.CHECK_STOP_FORUM_SPAM"><option value="1" '.$sfs_option[1].'>yes</option><option value="0" '.$sfs_option[0].'>no</option></select></td><td>checks against <a href="http://www.stopforumspam.com/" target="_blank">Stop Forum Spam</a></td></tr>';
	echo '<tr><td>Load JS and CSS externally:</td><td><select name="variable.EXTERNAL_JS_CSS"><option value="1" '.$jscss_option[1].'>yes</option><option value="0" '.$jscss_option[0].'>no</option></select></td><td>disable for intranet installations</td></tr>';
	echo '</table>';

	echo '<input type="submit" value="start installation" style="float:right;"/>';
	echo '</form>';
	$link_backward = array('label' => "System Check" , 'url' => "install.php?step=check");
	$link_forward = array();
break;

case "install":
	echo "<h1>Installation</h1>";
	// check database connection
	if (!check_database()) {
		echo '<span class="error">ERROR connecting to database. Please check your data.</span>';
		$link_backward = array('label' => "Check Data" , 'url' => "install.php?step=setup");
		$link_forward = array();
	} else {
		// create and populate database
		$sql = file_get_contents($settings['base_path'] . $database_sql['all']);
		$result = execute_query($sql);

		if ($result !== true) {
			echo '<span class="error">ERROR creating tables and rows. Try executing the query from your MySQL backend and check for error messages. Message: '.$result.'</span>';
			$link_backward = array('label' => "Check Data" , 'url' => "install.php?step=setup");
			$link_forward = array();
		} else {
			echo '<ul>';
			echo '<li>Creating missing folders</li>';
			echo create_folders($create_folders);

			echo '<li>Renaming (or moving) files</li>';
			echo rename_move_files($rename_files);
			
			if (!empty($settings['variable.RewriteBase'])) {
				if (!change_rewrite_base()) echo '<li><span class="error">ERROR changing RewriteBase</span></li>';
			}
			
			if (strpos('//', $settings['variable.URI']) === true) {
				echo '<span class="error">URI looks wrong.</span>';
			} else {
				$settings['variable.URI'] = '//' . $settings['variable.URI'].$settings['variable.RewriteBase'];
			}
			
			echo '<li>Creating config.ini</li>';
			$sample_config = file_get_contents($settings['base_path'] . '/nette_sources/dist.config.ini');
			
			foreach ($settings as $name => $value) {
				if (preg_match("/^(variable)|(database)|(php)|(debug)\./", $name) == 1) {
					$placeholder = (isset($placeholders[$name])) ? $placeholders[$name] : '""';
					$sample_config = insert_config($sample_config, $name, $value, $placeholder);
				}
			}

			$result = file_put_contents($settings['base_path'] . '/nette_sources/config.ini', $sample_config);
			if ($result === false) echo '<li><span class="error">ERROR saving config.ini</span></li>';

			// create admin account
			echo '<li>Creating admin account</li>';
			$result = create_admin();
			if ($result !== true) echo '<li><span class="error">ERROR creating admin account: '.$result.'</span></li>';
			echo '</ul>';
			echo '<h3>Finished!</h3>';
			echo '<h4>Copy the value of the cron token - you will need it to set up your cron: ' . $settings['variable.CRON_TOKEN'] . '</h4>';
			echo '<h4><a href="https://mycitizen.net/manual:start#system_administrator" target="_blank">Further installation and troubleshooting</a></h4>';
			echo '<h4>Get help in our <a href="http://forum.mycitizen.org/mycitizen-net-web/troubleshooting" target="_blank">discussion forum</a></h4>';
			echo '<h4><a href="'.$settings['variable.URI'].'/signin">Sign in</a></h4>';
			echo "<h4>Don't forget to remove the file install.php.</h4>";
			$link_backward = array('label' => "Start Over" , 'url' => "install.php");
			$link_forward = array();
		}
	}
break;

case "update":
	echo "<h1>Update</h1>";
	echo '<ul>';
	echo '<li>Reading information from config.ini</li>';
	// get information from config.ini
	$config = file_get_contents($settings['base_path'] . '/nette_sources/config.ini');
	
	$settings['database.database'] = extract_setting($config, 'database.database');
	$settings['database.host'] = extract_setting($config, 'database.host');
	$settings['database.username'] = extract_setting($config, 'database.username');
	$settings['database.password'] = extract_setting($config, 'database.password');

	echo '<li>Creating missing folders</li>';
	echo create_folders($create_folders);

	echo '<li>Renaming (or moving) files</li>';
	echo rename_move_files($rename_files);

	// run database update files and continuously recheck the version
	echo '<li>Updating database</li>';
	$current_version = get_current_db_version();
	while ($current_version !== false && isset($database_sql[$current_version])) {
		$sql = file_get_contents($settings['base_path'] . $database_sql[$current_version]);
		$result = execute_query($sql);
		if ($result === false) {
			echo '<span class="error">ERROR creating table and rows from file ' . $database_sql[$current_version] . '</span>';
			$link_backward = array('label' => "try again" , 'url' => "install.php?step=update");
			$link_forward = array();
			break;
		} else {
			$current_version = get_current_db_version();
			echo '<li>Updated database to version ' . $current_version . '</li>';
		}
	}
	echo '</ul>';
	echo '<h3>Finished!</h3>';
	echo '<h4><a href="https://mycitizen.net/manual:start#system_administrator" target="_blank">Further installation and troubleshooting</a></h4>';
	echo '<h4>Get help in our <a href="http://forum.mycitizen.org/mycitizen-net-web/troubleshooting" target="_blank">discussion forum</a></h4>';
	echo "Don't forget to remove the file install.php.";
	$link_backward = array('label' => "Start Over" , 'url' => "install.php");
	$link_forward = array();
break;

case "form_error":
	echo "<h1>Error</h1>";
	echo '<h4><span class="error">One or more fields seem to be empty (' . $problem_key . '). Please check your data.</span></h4>';
	$link_backward = array('label' => "Check Data" , 'url' => "install.php?step=setup");
	$link_forward = array();
break;

default:
	echo "<h1>Welcome</h1>";
	// check if config.ini is available and whether we need to install or update
	if (config_exists()) {
		$settings['required_action'] = 'update';
		echo "<h4>We found a config.ini file. Mycitizen.net seems to be already installed. We assume you want to update.</h4>";
		echo "<h4>If this is a running system, we advise that you enable the maintenance mode through the back end while you update your system. You can set a countdown to let your users know that they need to finish their activities.</h4>";
		$link_forward = array('label' => "Update" , 'url' => "install.php?step=update");
	} else {
		$settings['required_action'] = 'install';
		echo "<h4>This seems to be a new installation.</h4>";
		echo "<h4>In order to proceed, you will need a MySQL database with a user who has permissions to create tables.</h4>";
		echo "<h4>We will do a quick system check, ask you some required information and then prepare the system for its first run.</h4>";
		echo "<h4>Later you can:<ul><li>Choose your own logo</li><li>Customize the intro and footer</li><li>Set up languages</li><li>and more</li></ul></h4>";
		$link_forward = array('label' => "System Check" , 'url' => "install.php?step=check");
	}
	$link_backward = array();
break;

}

?>
	<div class="cleaner"></div>
</div>

<div class="navigation" style="padding:20px;">
<table style="width:100%">
<tr>
	<td style="width:50%">
<?php if (isset($link_backward['url'])): ?>
	<a href="<?php echo $link_backward['url'] ?>" class="button">Go back: <?php echo $link_backward['label'] ?></a>
<?php endif; ?>
	</td>
	<td style="width:50%">
<?php if (isset($link_forward['url'])): ?>
	<a href="<?php echo $link_forward['url'] ?>" class="button">Continue: <?php echo $link_forward['label'] ?></a>
<?php endif; ?>
	</td>
</tr>
</table>
</div>
</div>
</div>
</body>
</html>
<?php

$_SESSION['installation_settings'] = $settings;

// We finish here.
die();

##########################################################################################
######################################  Functions  #######################################
##########################################################################################

/**
 *	Checks if config.ini exists.
 * 
 *	@param void
 *	@return bool
 */
function config_exists() {
	global $settings;
	if (file_exists($settings['base_path'] . '/nette_sources/config.ini') === false) {
		// it seems to be a new installation
		return false;
	} else {
//		$content = file_get_contents($settings['base_path'] . '/nette_sources/config.ini');
		return true;
	}
}


/**
 *	Checks if the database is working (possible to open and replying to ping).
 * 
 *	@param void
 *	@return bool
 */
function check_database() {
	global $settings;
	$mysqli = new mysqli($settings['database.host'], $settings['database.username'], $settings['database.password'], $settings['database.database']);
	
	if ($mysqli->connect_errno) return false;
	if (!$mysqli->ping()) return false;
//	("SHOW GRANTS FOR '%s'@'%s'", $settings['db_user'], $settings['db_host']);
	$mysqli->close();
	return true;
}


/**
 *	Executes a query.
 * 
 *	@param string $sql
 *	@return bool | string ?
 */
function execute_query($sql) {
	global $settings;

	// remove accidental BOMs
	if(substr($sql, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
    	$sql = substr($sql, 3);
    }
    
	$mysqli = new mysqli($settings['database.host'], $settings['database.username'], $settings['database.password'], $settings['database.database']);
	if (mysqli_connect_errno()) return false;
	$result = $mysqli->multi_query($sql);
	$mysqli->close();
	if ($result !== true) $result = $mysqli->error;
	return $result;	
}


/**
 *	Inserts the setting for a parameter to dist.config.ini.
 * 
 *	@param string $object content of dist.config.ini
 *	@param string $name e.g. variable.CRON_TOKEN
 *	@param string $value
 *	@param string $placeholder string that may be replacing the value in dist.config.ini (example or default)
 *	@return bool
 */
function insert_config($object, $name, $value, $placeholder = '') {
	$pattern = "/(\s*)(" . preg_quote($name) . ")\s*=\s*(" . $placeholder . ")?/";
	if (preg_match("/[;_-\s]/", $value) > 0) $value = '"' . $value . '"';
	return preg_replace($pattern, "$1$2 = " . $value . "", $object);
}


/**
 *	Creates files, if they don't exist.
 *	@param array $rename_files
 *	@return void
 */
function rename_move_files($rename_files) {
	global $settings;
	$result = '<ul>';
	foreach ($rename_files as $file_path_old => $file_path_new) {
		if (!file_exists($settings['base_path'] . $file_path_new)) {
			rename($settings['base_path'] . $file_path_old, $settings['base_path'] . $file_path_new);
			$result .= '<li><i>"' . $file_path_new . '"</i> created</li>';
		} else {
			$result .= '<li><i>"' . $file_path_new . '"</i> already existed</li>';
		}
	}
	$result .= '</ul>';
	return $result;
}


/**
 *	Creates folder, if they don't exist.
 *	@param array @create_folders
 *	@return void
 */
function create_folders($create_folders) {
	global $settings;
	$result = '<ul>';
	foreach ($create_folders as $folder_path => $folder_permissions) {
		if (!is_dir($settings['base_path'] . $folder_path)) {
			$mkdir_result = @mkdir($settings['base_path'] . $folder_path, $folder_permissions);
			if ($mkdir_result === true) {
				$result .= '<li><i>"' . $folder_path . '"</i> created</li>';
			} else {
				$error = error_get_last();
				$result .= '<li><i>"' . $folder_path . '"</i> cannot be created: ' . $error['message'] . '</li>';
			}
		} else {
			$result .= '<li><i>"' . $folder_path . '"</i> already existed</li>';
		}
	}
	$result .= '</ul>';
	return $result;
}


/**
 *	Changes 'RewriteBase /' in /web/.htaccess
 *	@param void
 *	@return bool
 */
function change_rewrite_base() {
	global $settings;
	$htaccess = file_get_contents($settings['base_path'] . '/web/.htaccess');
	$pattern = "#(\s*)RewriteBase\s+([\/\w]*)#";
	$htaccess_changed = preg_replace($pattern, "$1RewriteBase " . $settings['variable.RewriteBase'], $htaccess);
//	echo nl2br($htaccess_changed);die();
	return file_put_contents($settings['base_path'] . '/web/.htaccess', $htaccess_changed);
}


/**
 *	Extract the setting for a parameter from config.ini.
 * 
 *	@param string $object content of config.ini
 *	@param string $name e.g. variable.CRON_TOKEN
 */
function extract_setting($object, $name) {
	$pattern = "/\s*" . preg_quote($name) . "\s*=\s*([^\s;]+)/";
	preg_match($pattern, $object, $matches);
	if (substr($matches[1],0,1) == '"' && substr($matches[1],-1,1) == '"') $matches[1] = substr($matches[1],1,-1);
	return $matches[1];
}


/**
 *	Inserts the admin account into the database
 * 
 *	@param void
 *	@return bool
 */
function create_admin() {
	global $settings;
	sleep(3); // workaround for strange InnoDB Table doesn't exist error
	$mysqli = new mysqli($settings['database.host'], $settings['database.username'], $settings['database.password'], $settings['database.database']);
	if ($mysqli->connect_errno) return $mysqli->error;
	if ( $stmt = $mysqli->prepare("INSERT INTO `user` (`user_id`, `user_login`, `user_email`, `user_password`, `user_language`, `user_visibility_level`, `user_access_level`, `user_creation_rights`, `user_status`, `user_hash`, `user_registration_confirmed`, `user_first_login`, `user_send_notifications`) VALUES ( 1, ?, ?, ?, 1, 2, 3, 1, 1, ?, 1, 0, 24)") ) {
		$stmt->bind_param("ssss", $settings['admin_login'], $settings['admin_email'], generate_password($settings['admin_password']), generate_hash());
		$stmt->execute();
		$stmt->close();
	} else {
		return $mysqli->error;
	}
	$mysqli->close();
	return true;
}


/**
 *	Encrypts the password
 *	@param string $password
 *	@return string
 */
function generate_password($password) {
	global $settings;
	require_once($settings['base_path'] . '/lib/Phpass/PasswordHash.php');
	$hasher = new PasswordHash(8, false);
	return $hasher->HashPassword($password);
}


/**
 *	Generates a hash for password changes
 *	@param void
 *	@return string
 */
function generate_hash()
{
	$length = 8;
	$chars  = "abcdefghijkmnopqrstuvwxyz023456789";
	srand((double) microtime() * 1000000);
	$i    = 0;
	$pass = '';
	while ($i < $length) {
		$num  = rand() % 33;
		$tmp  = substr($chars, $num, 1);
		$pass = $pass . $tmp;
		$i++;
	}
	return $pass;
}


/**
 *	Changes metric prefixes to bytes
 *	@param string $setting
 *	@return NULL|number
 */
function setting_to_bytes($setting) {
    static $short = array('k' => 0x400,
                          'm' => 0x100000,
                          'g' => 0x40000000);

    $setting = (string)$setting;
    if (!($len = strlen($setting))) return NULL;
    $last    = strtolower($setting[$len - 1]);
    $numeric = 0 + $setting;
    $numeric *= isset($short[$last]) ? $short[$last] : 1;
    return $numeric;
}


/**
 * Gets current version of existing database.
 * @param void
 * @return string
 */
function get_current_db_version() {
	global $settings;
	$mysqli = new mysqli($settings['database.host'], $settings['database.username'], $settings['database.password'], $settings['database.database']);
	if (mysqli_connect_errno()) return false;
	if ($mysqli->query("DESCRIBE `system`") === false) return 'none';
	if ( $result = $mysqli->query("SELECT `value` FROM `system` WHERE `name` = 'database_version'") ) {
		$row = $result->fetch_row();
		$result->close();
	}
	$mysqli->close();
	return $row[0];
}