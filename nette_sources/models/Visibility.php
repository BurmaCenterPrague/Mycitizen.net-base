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
 

class Visibility extends BaseModel {
	public static function getArray() {
		$result = dibi::fetchAll("SELECT * FROM `visibility_level`");
		$visibility_levels = array();
		foreach($result as $row) {
			$data = $row->toArray();
			$visibility_levels[$data['visibility_level_id']] = $data['visibility_level_name'];
		}
		return $visibility_levels;
	}
}
