<?php
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
