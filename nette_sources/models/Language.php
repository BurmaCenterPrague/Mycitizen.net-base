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
 

class Language extends BaseModel {
	public static function getArray() {
      $result = dibi::fetchAll("SELECT * FROM `language`");
      $languages = array();
      foreach($result as $row) {
			$data = $row->toArray();
         $languages[$data['language_id']] = $data['language_name'];
      }
      return $languages;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getFlag($language_id) {
		return dibi::fetchSingle("SELECT `language_flag` FROM `language` WHERE `language_id` = %i",$language_id);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getId($language_flag) {
		return dibi::fetchSingle("SELECT `language_id` FROM `language` WHERE `language_flag` = %s",$language_flag);
	}


	/**
	 *	Returns all language IDs that are in use
	 *	@param void
	 *	@return array
	 */
	public static function getIds() {
		$result = dibi::fetchAll("SELECT `language_id` FROM `language`");
		$ids = array();
      	if (count($result)) foreach($result as $row) {
			$data = $row->toArray();
			$ids[] = $data['language_id'];
      }
      return $ids;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getLanguageCode($language_id) {
		return dibi::fetchSingle("SELECT `language_code` FROM `language` WHERE `language_id` = %i",$language_id);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getLanguageName($language_id) {
		return dibi::fetchSingle("SELECT `language_name` FROM `language` WHERE `language_id` = %i",$language_id);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getAllCodes() {
      $result = dibi::fetchAll("SELECT * FROM `language`");
      $languagess = array();
      foreach($result as $row) {
			$data = $row->toArray();
         $languages[] = $data['language_flag'];
      }
    	return $languages;
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function addCode($flag, $code, $name) {
		$data = array('language_flag'=>$flag, 'language_code'=>$code, 'language_name'=>$name);
		$result = dibi::query("INSERT INTO `language`", $data);
		return $result;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function removeCode($code) {
		$result = dibi::query("DELETE FROM `language` WHERE `language_flag` = %s", $code);
		return $result;
	}

}
