<?php
class Language extends BaseModel {
	public static function getArray() {
      $result = dibi::fetchAll("SELECT * FROM `language`");
      $languagess = array();
      foreach($result as $row) {
			$data = $row->toArray();
         $languages[$data['language_id']] = $data['language_name'];
      }
      return $languages;
   }

	public static function getFlag($language_id) {
		return dibi::fetchSingle("SELECT `language_flag` FROM `language` WHERE `language_id` = %i",$language_id);
	}

	public static function getAllCodes() {
      $result = dibi::fetchAll("SELECT * FROM `language`");
      $languagess = array();
      foreach($result as $row) {
			$data = $row->toArray();
         $languages[] = $data['language_flag'];
      }
    	return $languages;
   }

	public static function addCode($code, $name) {
		$data = array('language_flag'=>$code, 'language_name'=>$name);
		$result = dibi::query("INSERT INTO `language`", $data);
		return $result;
	}

	public static function removeCode($code) {
		$result = dibi::query("DELETE FROM `language` WHERE `language_flag` = %s", $code);
		return $result;
	}

}
