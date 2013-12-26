<?php
/**
 * mycitizen.net - Open source social networking for civil society
 *
 * @version 0.3 beta
 *
 * @author http://mycitizen.org
 * @copyright  Copyright (c) 2013 Burma Center Prague (http://www.burma-center.org)
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */
 

class Tag extends BaseModel
{
	protected $numeric_id;
	protected $tag_data;
	
	public static function create($tag_id = null)
	{
		return new Tag($tag_id);
	}
	
	public function __construct($tag_id)
	{
		if (!empty($tag_id)) {
			$result = dibi::fetchAll("SELECT * FROM `tag` WHERE `tag_id` = %i", $tag_id);
			if (sizeof($result) > 2) {
				throw new Exception("More than one tag with the same id found.");
			}
			if (sizeof($result) < 1) {
				return false; //throw new Exception("Specified tag not found.");
			}
			$data_array       = $result[0]->toArray();
			$this->numeric_id = $data_array['tag_id'];
			
			unset($data_array['tag_id']);
			$this->tag_data = $data_array;
		}
		return true;
	}
	
	public function setTagData($data)
	{
		foreach ($data as $key => $value) {
			$this->tag_data[$key] = $value;
		}
	}
	
	public function getTagData()
	{
		$data = $this->tag_data;
		return $data;
	}
	
	public function getParentTag()
	{
		if ($this->tag_data['tag_parent_id'] != 0) {
			$parent = Tag::create($this->tag_data['tag_parent_id']);
			return $parent;
		}
		return null;
	}
	
	public function save()
	{
		try {
			dibi::begin();
			if (!empty($this->tag_data)) {
				if (empty($this->numeric_id)) {
					dibi::query('INSERT INTO `tag`', $this->tag_data);
					$this->numeric_id = dibi::insertId();
				} else {
					dibi::query('UPDATE `tag` SET ', $this->tag_data, 'WHERE `tag_id` = %i', $this->numeric_id);
				}
			}
		}
		catch (Exception $e) {
			dibi::rollback();
			throw $e;
		}
		dibi::commit();
		return true;
	}
	
	public function getTagId()
	{
		return $this->numeric_id;
	}
	
	public function getName()
	{
		return $this->tag_data['tag_name'];
	}
	
	public function getPath()
	{
		$parent = $this->getParentTag();
		$path   = array();
		$path[] = $this->getName();
		while (!is_null($parent)) {
			$path[] = $parent->getName();
			$parent = $parent->getParentTag();
		}
		return array_reverse($path);
	}
	
	public function getIdWithPath()
	{
		$parent = $this->getParentTag();
		$path   = array();
		$path[] = array(
			'name' => $this->getName(),
			'id' => $this->getTagId()
			);
		while (!is_null($parent)) {
			$path[] = array(
				'name' => $parent->getName(),
				'id' => $parent->getTagId()
				);
			$parent = $parent->getParentTag();
		}
		return array_reverse($path);
	}
	
	public static function getTreeArray()
	{
		$result = dibi::fetchAll("SELECT * FROM `tag` ORDER BY `tag_id`,`tag_parent_id`");
		$tags   = array();
		foreach ($result as $row) {
			$data                                   = $row->toArray();
			$tags[$data['tag_id']]['tag_id']        = $data['tag_id'];
			$tags[$data['tag_id']]['tag_parent_id'] = $data['tag_parent_id'];
			$tags[$data['tag_id']]['tag_name']      = $data['tag_name'];
		}
		
		$tag_array = array();
		$sort_1    = array();
		$sort_2    = array();

		foreach ($tags as $key => $tag) {
			$level  = 0;
			$parent = $tag['tag_parent_id'];
			$tag_id = $tag['tag_id'];

			while ($parent != null && $parent != $tag_id) {
				$level++;
				$parent = $tags[$parent]['tag_parent_id'];
				if (!empty($parent)) $tag_id = $tags[$parent]['tag_id'];
			}
			if ($parent == null) {
				$sort_1[$key] = $tag['tag_id'];
			} else {
				$sort_1[$key] = $tag['tag_parent_id'];
			}
			$sort_2[$key] = $level;
			$index        = 0;
			foreach ($tag_array as $t) {
				if ($t['tag_id'] == $tag['tag_parent_id']) {
					break;
				} else if ($t['tag_parent_id'] == $tag['tag_id']) {
					$index--;
					break;
				}
				$index++;
			}
			$index++;
			array_splice($tag_array, $index, 0, array(
				array(
					'tag_id' => $tag['tag_id'],
					'level' => $level,
					'tag_name' => $tag['tag_name'],
					'tag_parent_id' => $tag['tag_parent_id']
				)
			));
		}
		
		return $tag_array;
	}
	
	
	public static function getParentTree($tag_id)
	{
		$tree      = self::getTreeArray();
		$tags      = array();
		$start     = false;
		$top_level = 0;
		foreach ($tree as $tag_row) {
			if ($start && $tag_row['level'] > $top_level) {
				$tags[] = $tag_row['tag_id'];
			} elseif ($start && $tag_row['level'] <= $top_level) {
				$start = false;
				break;
			}
			if ($tag_row['tag_id'] == $tag_id) {
				$start     = true;
				$top_level = $tag_row['level'];
			}
		}
		return $tags;
	}
	
	public static function delete($tag_id)
	{
		dibi::query("DELETE FROM `tag` WHERE `tag_id` = %i", $tag_id);
	}
	
	public static function remove($tag_id)
	{
		$tag = Tag::create($tag_id);
		if ($tag) {
			$data      = $tag->getTagData();
			$parent_id = $data['tag_parent_id'];
			dibi::query("DELETE FROM `tag` WHERE `tag_id` = %i", $tag_id);
			dibi::query("UPDATE `tag` SET `tag_parent_id` = %i WHERE `tag_parent_id` = %i", $parent_id, $tag_id);
			
			dibi::query("DELETE FROM `user_tag` WHERE `tag_id` = %i", $tag_id);
			dibi::query("DELETE FROM `group_tag` WHERE `tag_id` = %i", $tag_id);
			dibi::query("DELETE FROM `resource_tag` WHERE `tag_id` = %i", $tag_id);
		}
	}
	
}
