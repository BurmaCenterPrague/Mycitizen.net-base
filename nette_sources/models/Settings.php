<?php
/**
 * mycitizen.net - Open source social networking for civil society
 *
 * @version 0.2 beta
 *
 * @author http://mycitizen.org
 *
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */
 

class Settings {
	public static $labels = array();
	
	public static function getAllVariables() {
		$result = dibi::fetchAll("SELECT * FROM `settings`");
		$data = array();
		foreach($result as $row) {
			$data[$row['variable_name']] = $row['variable_value'];
			self::$labels[$row['variable_name']] = $row['variable_display_label'];
		}
		return $data;
	}
	public static function getVariable($variable_name) {
      $result = dibi::fetchSingle("SELECT `variable_value` FROM `settings` WHERE `variable_name` = %s",$variable_name);
      return $result;
   }
	public static function setVariable($variable_name,$variable_value) {
		dibi::query("UPDATE `settings` SET `variable_value` = %s WHERE `variable_name` = %s",$variable_value,$variable_name);
	}
}
?>
