<?php
/**
 * mycitizen.net - Social networking for civil society
 *
 *
 * @author http://mycitizen.org
 * @copyright  Copyright (c) 2013, 2014 Burma Center Prague (http://www.burma-center.org)
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */
 

class Settings {
//	public static $labels = array();


	/**
	 *	Returns all variables
	 *	@param void
	 *	@return array
	 */
	public static function getAllVariables() {
		$result = dibi::fetchAll("SELECT * FROM `settings`");
		$data = array();
		foreach($result as $row) {
			$data[$row['variable_name']] = $row['variable_value'];
//			self::$labels[$row['variable_name']] = $row['variable_display_label'];
		}
		return $data;
	}


	/**
	 *	Returns the label that will be displayed for a variable.
	 *	@param string $variable_name
	 *	@return string
	 */
	public static function getVariableLabel($variable_name) {
      $result = dibi::fetchSingle("SELECT `variable_display_label` FROM `settings` WHERE `variable_name` = %s",$variable_name);
      return $result;	
	}


	/**
	 *	Returns the value for a variable.
	 *	@param string $variable_name
	 *	@return string|boolean
	 */
	public static function getVariable($variable_name) {
      $result = dibi::fetchSingle("SELECT `variable_value` FROM `settings` WHERE `variable_name` = %s",$variable_name);
      return $result;
   }


	/**
	 *	Updates one variable, or writes new entry if non-existent.
	 *	@param string $variable_name
	 *	@param string $variable_value
	 *	@return boolean
	 */
	public static function setVariable($variable_name,$variable_value) {
		$result = dibi::query("UPDATE `settings` SET `variable_value` = %s WHERE `variable_name` = %s",$variable_value,$variable_name);
		return $result;
	}
}