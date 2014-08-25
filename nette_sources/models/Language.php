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
 
/*
  language_flag: string used in the name of the folder (distinct from code because there may be various localizations for different fonts used for the same code)
  language_code: ISO code according to http://en.wikipedia.org/wiki/ISO_639-3, http://en.wikipedia.org/wiki/List_of_ISO_639-3_codes
  language_name, Name of that language, to be displayed in menus, item infos etc.

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
	 *	Returns the folder name for an ID.
	 *	@param int $language_id
	 *	@return string
	 */
	public static function getFlag($language_id) {
		return dibi::fetchSingle("SELECT `language_flag` FROM `language` WHERE `language_id` = %i", $language_id);
	}


	/**
	 *	Returns the ID for a folder name.
	 *	@param string $language_flag
	 *	@return int
	 */
	public static function getId($language_flag) {
		return dibi::fetchSingle("SELECT `language_id` FROM `language` WHERE `language_flag` = %s", $language_flag);
	}


	/**
	 *	Returns the ID for an ISO code, or - if not found - the default 1.
	 *	Caution when having duplicate codes.
	 *	@param string $language_code 
	 *	@return int
	 */
	public static function getIdFromCode($language_code) {
		$result = dibi::fetchSingle("SELECT `language_id` FROM `language` WHERE `language_code` = %s", $language_code);
		
		if (!empty($result)) {
			return $result;
		} else {
			return 1;
		}
	}


	/**
	 *	Returns all language IDs that are in use.
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
	 *	Returns the ISO code for an ID.
	 *	@param int $langauge_id
	 *	@return string
	 */
	public static function getLanguageCode($language_id) {
		return dibi::fetchSingle("SELECT `language_code` FROM `language` WHERE `language_id` = %i",$language_id);
	}


	/**
	 *	Returns the display name for an ID.
	 *	@param int $language_id
	 *	@return string
	 */
	public static function getLanguageName($language_id) {
		return dibi::fetchSingle("SELECT `language_name` FROM `language` WHERE `language_id` = %i",$language_id);
	}


	/**
	 *	Returns all folder names.
	 *	@param void
	 *	@return array
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
	 *	Adds one language.
	 *	@param string $flag folder name
	 *	@param string $code ISO code
	 *	@param string $name display name
	 *	@return boolean
	 */
	public static function addCode($flag, $code, $name) {
		$data = array('language_flag'=>$flag, 'language_code'=>$code, 'language_name'=>$name);
		$result = dibi::query("INSERT INTO `language`", $data);
		return $result;
	}


	/**
	 *	Updates a language.
	 *	@param string $flag folder name
	 *	@param string $code ISO code
	 *	@param string $name display name
	 *	@return boolean
	 */
	public static function updateCode($flag, $code, $name) {
		$result = dibi::query("UPDATE `language` SET `language_code` = %s, `language_name` = %s WHERE `language_flag` = %s ", $code, $name, $flag);
		return $result;
	}


	/**
	 *	Removes the language for a given folder.
	 *	@param string $langauge_flag
	 *	@return boolean
	 */
	public static function removeFlag($langauge_flag) {
		$result = dibi::query("DELETE FROM `language` WHERE `language_flag` = %s", $langauge_flag);
		return $result;
	}

}
