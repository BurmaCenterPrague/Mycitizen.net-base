<?php
/**
 *	Extracts localizable strings from PHP and PHTML code
 *	customized for mycitizen.net to include _t() and _d()
 *
 */

define('BASE_DIR', dirname(__FILE__));
define('APP_DIR', BASE_DIR . '/nette_sources');

require BASE_DIR . '/nettegettext/NetteGettextExtractor.php';

$ge = new NetteGettextExtractor(); // provede základní nastavení pro šablony apod.
$ge->setupForms()->setupDataGrid(); // provede nastavení pro formuláře a DataGrid
$ge->scan(APP_DIR); // prohledá všechny aplikační soubory
$ge->save(BASE_DIR . '/locale/messages.po'); // vytvoří Gettextový soubor editovatelný např v Poeditu
?>