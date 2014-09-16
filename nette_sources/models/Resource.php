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
 

class Resource extends BaseModel {
	
	private $resource_data;
	private $numeric_id;
	private $parent_numeric_id = null;


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function create($resource_id = null) {
		return new Resource($resource_id);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function __construct($resource_id) {
		if(!empty($resource_id)) {
			$result = dibi::fetchAll("SELECT `resource_id`, `resource_parent_id`, `resource_author`, `resource_type`, `resource_name`, `resource_description`, `resource_data`, `resource_visibility_level`, `resource_language`, `resource_status`, `resource_viewed`, `resource_position_x`, `resource_position_y`, `resource_creation_date`, `resource_trash`, `resource_last_activity` FROM `resource` WHERE `resource_id` = %i", $resource_id);
			if(sizeof($result) > 2) {
				return false;
				throw new Exception("More than one resource with the same id found.");
			}
			if(sizeof($result) < 1) {
				return false;
				throw new Exception("Specified resource not found.");
			}
			$data_array = $result[0]->toArray();
			$this->numeric_id = $data_array['resource_id'];
			$this->parent_numeric_id = $data_array['resource_parent_id'];
			unset($data_array['resource_id']);
			unset($data_array['resource_parent_id']);
			$this->resource_data = $data_array;
		}
		return true;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function setResourceData($data) {
		if(isset($data['resource_parent_id'])) {
			unset($data['resource_parent_id']);
		}
		foreach($data as $key=>$value) {
     		$this->resource_data[$key] = $value;
    	}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function getResourceData() {
		
		$data = $this->resource_data;
		if(isset($data['resource_data'])) {
			$resource_sub_data = json_decode($this->resource_data['resource_data'],true);

			unset($data['resource_data']);
			$data = array_merge($data,$resource_sub_data);
			if($data['resource_type'] == null) {
				$data['resource_type'] = 0;
			}
/*			if($data['resource_owner'] == null) {
				$data['resource_owner'] = 0;
			}
*/
			$tags = $this->getTags();
			$data['tags'] = array();	
			foreach($tags as $tagO) {
				$tag_data = $tagO->getTagData();
				$tag_data['id'] = $tagO->getTagId();
				$data['tags'][] = $tag_data;
			}

		}
		return $data;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function save() {
		try {
			dibi::begin();
			if(!empty($this->resource_data)) {
				if(empty($this->numeric_id)) {
					$data = $this->resource_data;
					if(!is_null($this->parent_numeric_id)) {
						$data['resource_parent_id'] = $this->parent_numeric_id;
					}
					dibi::query('INSERT INTO `resource`', $data);
					$this->numeric_id = dibi::insertId();
				} else {
					$data = $this->resource_data;
               if(!is_null($this->parent_numeric_id)) {
                  $data['resource_parent_id'] = $this->parent_numeric_id;
               }
					dibi::query('UPDATE `resource` SET ', $data , 'WHERE `resource_id` = %i', $this->numeric_id);
				}
			}
		} catch (Exception $e) {
     		dibi::rollback();
     		throw $e;
    	}
		dibi::commit();
		return true;
	}


	/**
	 *	Checks whether the same has just been posted to prevent multiple message on slow networks
	 *	@param
	 *	@return boolean true for doublette found
	*/
	public function check_doublette($data, $object_id, $object_type) {
		$result = dibi::fetchAll("SELECT `resource_id` FROM `resource` WHERE %and AND `resource_creation_date` > NOW() - INTERVAL 5 MINUTE", $data);
		if (!empty($result)) {
			foreach ($result AS $row) {
				$output = $row->toArray();
				$result2 = dibi::fetchSingle("SELECT * FROM `resource_user_group` WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = %i", $output['resource_id'], $object_id, $object_type);
				if (!empty($result)) return true;
			}
		}
		return false;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function setParent($parent_id) {
		$this->parent_numeric_id = $parent_id;
	}


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function getParentId() {
		return $this->parent_numeric_id;
	}


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function getParent() {
		if(!is_null($this->parent_numeric_id)) {
			$resource = Resource::create($this->parent_numeric_id);
			return $resource;
		}
		return null;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function delete($resource_id) {
		dibi::query("DELETE FROM `resource` WHERE `resource_id` = %i",$resource_id);
	}


	/**
	 *	Returns the id of this object
	 *	@param void
	 *	@return int
	 */
	public function getResourceId() {
		return $this->numeric_id;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getVisibilityLevel() {
      return $this->resource_data['resource_visibility_level'];
   }

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function insertTag($tag_id) {
		$registered_tags = $this->getTags();
		if(!isset($registered_tags[$tag_id])) {
			dibi::query('INSERT INTO `resource_tag` (`tag_id`,`resource_id`) VALUES (%i,%i)',$tag_id,$this->numeric_id);
		}
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function removeTag($tag_id) {
		$registered_tags = $this->getTags();
      if(isset($registered_tags[$tag_id])) {
         dibi::query('DELETE FROM `resource_tag` WHERE `tag_id` = %i AND `resource_id` = %i',$tag_id,$this->numeric_id);
      }
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getTags() {
//		$result = dibi::fetchAll("SELECT gt.`tag_id`,t.`tag_name` FROM `resource_tag` gt LEFT JOIN `tag` t ON (t.`tag_id` = gt.`tag_id`) WHERE `resource_id` = %i",$this->numeric_id);
		$result = dibi::fetchAll("SELECT gt.`tag_id`,t.`tag_name` FROM `resource_tag` gt, `tag` t WHERE t.`tag_id` = gt.`tag_id` AND `resource_id` = %i ORDER BY t.`tag_name` ASC",$this->numeric_id);
		$array = array();
		foreach($result as $row) {
			$data = $row->toArray();
			$array[$data['tag_id']] = Tag::create($data['tag_id']);//$data['tag_name'];
		}		
		return $array;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getName($resource_id)
	{
		$result = dibi::fetchSingle("SELECT `resource_name` FROM `resource` WHERE `resource_id` = %i", $resource_id);
		if (empty($result)) {
			return false;
		}
		return $result;
	}
	
	
	/**
	 *	Groups tags according to their parent and sorts them by parent, then child.
	 *	@param
	 *	@return
	 */
	public function groupSortTags($tags) {
	
		uasort($tags, function($a,$b){
		
			$path_a = $a->getPath();
			$path_b = $b->getPath();
		
			if (isset($path_a[1])) $child_a=true; else $child_a=false;
			if (isset($path_b[1])) $child_b=true; else $child_b=false;
		
			if (!$child_a && !$child_b) {			
				return strnatcasecmp($path_a[0], $path_b[0]); // both have no child -> compare parents
			}
		
			$cmp = strnatcasecmp($path_a[0], $path_b[0]);
		
			if ($cmp != 0) {
				return $cmp; // different parents
				
			} else {
			
				if ($child_a && $child_b) {
					return strnatcasecmp($path_a[1], $path_b[1]); // both have a child -> compare children
				}
			
				if ($child_a && !$child_b) {
					return 1; // empty child always before any child
				}

				if (!$child_a && $child_b) {
					return -1; // empty child always before any child
				}
		
			}
	
			return 0; // should not happen
		});
		
		return $tags;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getAllMembers($filter) {
      $limit = null;
      $count = null;
      $filter_o = array();
		$filter_o['resource_id'] = $this->numeric_id; 
      if(isset($filter['limit'])) {
         $limit = $filter['limit'];
         unset($filter['limit']);
      }
      if(isset($filter['count'])) {
         $count = $filter['count'];
         unset($filter['count']);
      }
      if(isset($filter['access_level']) && $filter['access_level'] != 'null') {
         $filter_o['resource_user_group_access_level'] = $filter['access_level'];
      }
      if(isset($filter['enabled']) && $filter['enabled'] != 'null') {
         $filter_o['resource_user_group_status'] = $filter['enabled'];
      }
      if(isset($filter['name']) && $filter['name'] != "") {
         $filter_o[] = array('%or',array(array('`user_login` LIKE %~like~',$filter['name']),array('`user_name` LIKE %~like~',$filter['name']),array('`user_surname` LIKE %~like~',$filter['name'])));
      }
      if(!is_null($limit) && !is_null($count)) {
         $result = dibi::fetchAll("SELECT gu.* FROM `resource_user_group` gu WHERE %and LIMIT %i,%i",$filter_o,$limit,$count);
      } else {
         $result = dibi::fetchAll("SELECT gu.* FROM `resource_user_group` gu WHERE %and",$filter_o);
      }
      $users = array();
      foreach($result as $row) {
			$data = $row->toArray();
			if($data['member_type'] == 1) {
				$result_2 = dibi::fetchAll("SELECT `user_login` FROM `user` WHERE `user_id` = %i",$data['member_id']);
				if(!empty($result_2[0])) {
					$user = $result_2[0]->toArray();
         		$data['member_name'] = $user['user_login'];
				}
			} else {
				$result_2 = dibi::fetchAll("SELECT `group_name` FROM `group` WHERE `group_id` = %i",$data['member_id']);
            if(!empty($result_2[0])) {
            	$group = $result_2[0]->toArray();
            	$data['member_name'] = $group['group_name'];
            }

			}
         $members[] = $data;
      }
      return $members;
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getUserAccessLevel($user_id) {
		$result = dibi::fetchAll("SELECT `resource_user_group_access_level` FROM `resource_user_group` WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 1",$this->numeric_id,$user_id);
		if(!empty($result[0])) {

			return $result[0]->resource_user_group_access_level;
		}
		return 0;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getGroupAccessLevel($group_id) {
      $result = dibi::fetchAll("SELECT `resource_user_group_access_level` FROM `resource_user_group` WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 2",$this->numeric_id,$group_id);
      if(!empty($result[0])) {

         return $result[0]->resource_user_group_access_level;
      }
      return 0;
   }

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function userIsRegistered($user_id) {
		$result = dibi::fetchAll("SELECT `member_id` FROM `resource_user_group` WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 1",$this->numeric_id,$user_id);
		if(!empty($result)) {
			return true;
		} 
      return false;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function groupIsRegistered($group_id) {
      $result = dibi::fetchAll("SELECT `member_id` FROM `resource_user_group` WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 2",$this->numeric_id,$group_id);
      if(!empty($result)) {
         return true;
      }
      return false;
   }

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function updateUser($user_id,$data) {
		try {
         dibi::begin();
         if(!$this->userIsRegistered($user_id)) {
				$data['resource_id'] = $this->numeric_id;
				$data['member_id'] = $user_id;
				$data['member_type'] = 1;
            dibi::query('INSERT INTO `resource_user_group`', $data);
			} else {
            dibi::query('UPDATE `resource_user_group` SET ', $data , 'WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 1', $this->numeric_id,$user_id);
         }
      } catch (Exception $e) {
         dibi::rollback();
         throw $e;
      }
      dibi::commit();	
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function removeUser($user_id) {
      try {
         dibi::begin();
         if($this->userIsRegistered($user_id)) {
            dibi::query('DELETE FROM `resource_user_group` WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 1', $this->numeric_id,$user_id);
         }
      } catch (Exception $e) {
         dibi::rollback();
         throw $e;
      }
      dibi::commit();
   }

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function updateGroup($group_id,$data) {
      try {
         dibi::begin();
         if(!$this->groupIsRegistered($group_id)) {
            $data['resource_id'] = $this->numeric_id;
            $data['member_id'] = $group_id;
            $data['member_type'] = 2;
            dibi::query('INSERT INTO `resource_user_group`', $data);
         } else {
            dibi::query('UPDATE `resource_user_group` SET ', $data , 'WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 2', $this->numeric_id,$group_id);
         }
      } catch (Exception $e) {
         dibi::rollback();
         throw $e;
      }
      dibi::commit();   
   }


   /**
    *	@todo ### Description
    *	@param
    *	@return
    */
   public function removeGroup($group_id) {
      try {
         dibi::begin();
         if($this->groupIsRegistered($group_id)) {
            dibi::query('DELETE FROM `resource_user_group` WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 2', $this->numeric_id,$group_id);
         }
      } catch (Exception $e) {
         dibi::rollback();
         throw $e;
      }
      dibi::commit();
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getTypeArray() {
		$types = array(
			2 => _t('event'),
			3 => _t('organization'),
			4 => _t('text information'),
			5 => _t('video/audio'),
			6 => _t('other')
			);

/*
      $result = dibi::fetchAll("SELECT * FROM `resource_type` WHERE `resource_type_group` = 1");
      $languages = array();
      foreach($result as $row) {
         $data = $row->toArray();
         $languages[$data['resource_type_id']] = $data['resource_type_name'];
      }
      return $languages;
*/
		return $types;
	}


	/**
	 *	Retrieves the class to produce the icon for that resource type.
	 *	@param int $resource_id
	 *	@return boolean|string
	 */
	public static function getIconClass($resource_id) {
		$resource_name = array(
			1=>'message',
			2=>'event',
			3=>'org',
			4=>'doc',
			6=>'website',
			7=>'report',
			8=>'message',
			9=>'friendship',
			'media_soundcloud'=>'audio',
			'media_youtube'=>'video',
			'media_vimeo'=>'video',
			'media_bambuser'=>'live-video',
			10 => '',
			11 => ''
			);
		$resource = self::create($resource_id);
		if (!empty($resource)) {
			$data = $resource->getResourceData();
			$resource_type = $data['resource_type']==5 ? $resource_name[$data['media_type']] : $resource_name[$data['resource_type']];
			return "icon-".$resource_type;
		} else {
			return false;
		}
	}


	/**
	 *	Retrieves the label for the title tag according that resource type.
	 *	@param int $resource_id
	 *	@return boolean|string
	 */
	public static function getIconTitle($resource_id) {
				$resource_type_labels = array(
					1=>_t('message'),
					2=>_t('event'),
					3=>_t('organization'),
					4=>_t('document'),
					6=>_t('link to external resource'),
					7=>'7',
					8=>'8',
					9=>'friendship',
					10=>'friendship request',
					11=>'noticeboard message',
					'media_soundcloud'=>_t('sound on Soundcloud'),
					'media_youtube'=>_t('video on YouTube'),
					'media_vimeo'=>_t('video on Vimeo'),
					'media_bambuser'=>_t('live-video on Bambuser')
					);
		$resource = self::create($resource_id);
		if (!empty($resource)) {
			$data = $resource->getResourceData();
			$resource_label = $data['resource_type']==5 ? $resource_type_labels[$data['media_type']] : $resource_type_labels[$data['resource_type']];
			return $resource_label;
		} else {
			return false;
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function incrementVisitor() {
		 $result = dibi::query("UPDATE `resource` SET `resource_viewed` = resource_viewed+1 WHERE `resource_id` = %i",$this->numeric_id);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function setOpened($user_id,$resource_id = 0) {
		if (!$resource_id) $resource_id = $this->numeric_id;
		$result = dibi::query("UPDATE `resource_user_group` SET `resource_opened_by_user` = '1' WHERE `resource_id` = %i AND `member_type` = 1 AND `member_id` = %i",$resource_id,$user_id);
		$this->cleanCache('messagelisteruser');
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function setUnopened($user_id,$resource_id = 0) {
		if (!$resource_id) $resource_id = $this->numeric_id;
		$result = dibi::query("UPDATE `resource_user_group` SET `resource_opened_by_user` = '0' WHERE `resource_id` = %i AND `member_type` = 1 AND `member_id` = %i",$resource_id,$user_id);
		$this->cleanCache('messagelisteruser');
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function isOpened() {
		$user = NEnvironment::getUser()->getIdentity();
		if(!empty($user)) {
			$user_id = $user->getUserId();
      	$result = dibi::fetchSingle("SELECT `resource_opened_by_user` FROM `resource_user_group` WHERE `resource_id` = %i AND `member_type` = 1 AND `member_id` = %i",$this->numeric_id,$user_id);
		} else {
			return 1;
		}
		if($result == 0) {
			return 0;
		} else {
			return 1;
		}
		return $result;
   }


	/**
	 *	Returns the number of unread messages of the logged-in user.
	 *	@param void
	 *	@return int
	 */
	public static function getUnreadMessages() {
		$user = NEnvironment::getUser()->getIdentity();
		if(!empty($user)) {
			$user_id = $user->getUserId();
/*			$count = dibi::fetchSingle("SELECT COUNT(`resource`.`resource_id`) FROM `resource` LEFT JOIN `resource_user_group` ON `resource`.`resource_id` = `resource_user_group`.`resource_id` WHERE `resource_user_group`.`resource_opened_by_user` = 0 AND `resource`.`resource_type` IN (1,9,10) AND `resource`.`resource_author` <> %i AND `resource_user_group`.`member_type` = 1 AND `resource_user_group`.`member_id` = %i AND `resource`.`resource_status` <> 0",$user_id,$user_id);
			return $count; */
			return User::getUnreadMessages($user_id);
		} else {
			return 0;
		}
		
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getType() {
      return 3;
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getResourceType($resource_id) {
      return dibi::fetchSingle("SELECT `resource_type` FROM `resource` WHERE `resource_id` = %i", $resource_id);
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getMediaType($resource_id) {
		$resource = self::create($resource_id);
		if (empty($resource)) {
			return false;
		}
		$data = $resource->getResourceData();
    	return $data['media_type'];
	}

	
	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getResourceAuthor() {
		return $resource_data['resource_author'];		
	}


	/**
	 *	Retrieve the owner
	 *	@param
	 *	@return
	 */
	public function getOwner() {
      $result = dibi::fetchSingle("SELECT `resource_author` FROM `resource` WHERE `resource_id` = %i",$this->numeric_id);

      $owner = User::create($result);
      if(!empty($owner)) {
         return $owner;
      }
      return null;
	}


	/**
	 *	Assign a new owner to a resource
	 *	@param int $user_id id of new owner
	 *	@return
	 */
	public function setOwner($user_id) {
		if(Auth::ADMINISTRATOR == Auth::isAuthorized(3,$this->numeric_id)) {
			return dibi::query("UPDATE `resource` SET `resource_author`  = %i WHERE `resource_id` = %i", $user_id, $this->numeric_id);
		}
		return null;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function hasPosition() {
      $result = dibi::fetchSingle("SELECT `resource_id` FROM `resource` WHERE `resource_id` = %i AND `resource_position_x` IS NOT NULL AND `resource_position_y` IS NOT NULL",$this->numeric_id);
      if(!empty($result)) {
         return true;
      }
      return false;
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function moveToTrash($resource_id) {
		$user = NEnvironment::getUser()->getIdentity();
    	if(!empty($user)) {
			dibi::query("UPDATE `resource_user_group` SET `resource_trash` = '1' WHERE `resource_id` = %i AND `member_type` = '1' AND `member_id` = %i",$resource_id,$user->getUserId());
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function moveFromTrash($resource_id) {
		$user = NEnvironment::getUser()->getIdentity();
      if(!empty($user)) {
			dibi::query("UPDATE `resource_user_group` SET `resource_trash` = '0' WHERE `resource_id` = %i AND `member_type` = '1' AND `member_id` = %i",$resource_id,$user->getUserId());
		}
   }


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function inTrash() {
		$user = NEnvironment::getUser()->getIdentity();
      if(!empty($user)) {
			$result = dibi::fetchSingle("SELECT `resource_user_group_id` FROM `resource_user_group` WHERE `resource_id` = %i AND `resource_trash` = '1' AND `member_type` = '1' AND `member_id` = %i",$this->numeric_id,$user->getUserId());
		}
		if(!empty($result)) {
			return true;
		}
		return false;
	}


	/**
	 *	Empties the trash of the logged-in user.
	 *	@param void
	 *	@return void
	 */
	public static function emptyTrash() {
		$user = NEnvironment::getUser()->getIdentity();
		if(!empty($user)) {
			$trash = dibi::fetchAll("SELECT `resource_user_group_id`,`resource_id` FROM `resource_user_group` WHERE `resource_trash` = '1' AND `member_type` = '1' AND `member_id` = %i",$user->getUserId());
			if(!empty($trash)) {
				foreach($trash as $row) {
					$data = $row->toArray();
					dibi::query("DELETE FROM `resource_user_group` WHERE `resource_user_group_id` = %i",$data['resource_user_group_id']);
					$count = dibi::fetchSingle("SELECT COUNT(`resource_user_group_id`) FROM `resource_user_group` WHERE `resource_id` = %i",$data['resource_id']);
					if(empty($count)) {
						dibi::query("DELETE FROM `resource` WHERE `resource_id` = %i",$data['resource_id']);
					}
				}
			}
		}
	}


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function isActive()
	{
		$result = dibi::fetchSingle("SELECT `resource_status` FROM `resource` WHERE `resource_id` = %i", $this->numeric_id);
		if (!empty($result)) {
			if ($result == 1) {
				return true;
			}
		}
		return false;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function bann() {
    	if(Auth::MODERATOR <= Auth::isAuthorized(3,$this->numeric_id)) {
    		dibi::query("UPDATE `resource` SET `resource_status` = '0' WHERE `resource_id` = %i",$this->numeric_id);
			dibi::query("UPDATE `resource_user_group` SET `resource_user_group_status` = '0' WHERE `resource_id` = %i", $this->numeric_id);
    	}
	}


	/**
	 *	Immediately removes a message (chat, comment or noticeboard)
	 *	@param int $object_type where message appears 
	 *	@param int $object_id where message appears
	 *	@return bool
	 */
	public function remove_message($object_type = null, $object_id = null) {
		if (isset($object_type) && isset($object_id)) {
			$result = dibi::fetchSingle("SELECT `resource_user_group_id` FROM `resource_user_group` WHERE `resource_id` = %i AND `member_type` = %i AND `member_id` = %i", $this->numeric_id, $object_type, $object_id);
			if ($result) {
				dibi::query("UPDATE `resource` SET `resource_status` = '0' WHERE `resource_id` = %i", $this->numeric_id);
				dibi::query("UPDATE `resource_user_group` SET `resource_user_group_status` = 0 WHERE `resource_id` = %i AND `member_type` = %i AND `member_id` = %i", $this->numeric_id, $object_type, $object_id);
				return true;
			}
		} else {
			dibi::query("UPDATE `resource` SET `resource_status` = '0' WHERE `resource_id` = %i", $this->numeric_id);
			return true;
		}
		return false;
   }


	/**
	 *	Immediately removes a message (chat, comment or noticeboard)
	 *	@param int $resource_id of message 
	 *	@return bool
	 */
	public static function removeMessage($resource_id) {
		if (isset($resource_id)) {
			$result = dibi::query("UPDATE `resource_user_group` SET `resource_user_group_status` = 0 WHERE `resource_id` = %i", $resource_id);
			if ($result) {
				dibi::query("UPDATE `resource` SET `resource_status` = '0' WHERE `resource_id` = %i", $resource_id);		
				return true;
			}
		}
		return false;
   }



	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function getLastActivity() {
      $result = dibi::fetchSingle("SELECT `resource_last_activity` FROM `resource` WHERE `resource_id` = %i",$this->numeric_id);
      return $result;
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
   public function setLastActivity() {
      dibi::query("UPDATE `resource` SET `resource_last_activity` = NOW() WHERE `resource_id` = %i",$this->numeric_id);
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function getThumbnailUrl() {
	
		$url = '';
		$final_url = '';
		$data = $this->getResourceData();
		
		switch ($data['resource_type']) {
			case 2: if (isset($data['event_url']) && !empty($data['event_url'])) $url = $data['event_url']; break;
			case 3: if (isset($data['organization_url']) && !empty($data['organization_url'])) $url = $data['organization_url']; break;
			case 4: if (isset($data['text_information_url']) && !empty($data['text_information_url'])) $url = $data['text_information_url']; break;
			case 5: if (isset($data['media_link']) && !empty($data['media_link'])) {
						switch ($data['media_type']) {
							case 'media_soundcloud':  $final_url = 'http://soundcloud.com/'.$data['media_link']; break;
//							case 'media_youtube': $final_url = 'https://img.youtube.com/vi/'.$data['media_link'].'/1.jpg'; break;
							case 'media_youtube': $url = 'https://www.youtube.com/watch?v='.$data['media_link']; break;
							case 'media_vimeo': $tmp = unserialize(@file_get_contents("http://vimeo.com/api/v2/video/".$data['media_link'].".php"));$final_url = $tmp[0]['thumbnail_medium']; break;
							case 'media_bambuser': break;
							// 'http://static.bambuser.com/modules/b/ui/bambuser_ui/no-preview-140x115.png'
							// 'http://bambuser.com/v/'.$data['media_link']
						}
					}
			break;			case 6: if (isset($data['other_url']) && !empty($data['other_url'])) $url = $data['other_url']; break;
		}

		if (empty($final_url)) {
			$md5 = md5($url);
			if ($data['resource_type'] == 5) {
				$link = '/images/cache/resource/'.$this->numeric_id.'-screenshot-'.$md5.'.gif';
			} else {
				$link = '/images/cache/resource/'.$this->numeric_id.'-screenshot-'.$md5.'.jpg';
			}
			$filepath = WWW_DIR.$link;
			if (file_exists($filepath)) {
				$final_url = NEnvironment::getVariable("URI") . $link;
			}
		}
		return $final_url;
	
	}


	/**
	 *	creates screenshot in html
	 *	@param string $title
	 *	@param boolean $placeholder
	 *	@return string
	*/
	public function getScreenshot($title=null, $placeholder=false, $size=250) {
		$image = '';
		$url = $this->getThumbnailUrl();
		if (!empty($url)) {
			$size_div = $size+10;
			if (isset($title)) {
				$image = '<div class="screenshot" style="padding:5px;background:#fff;width:'.$size_div.'px;"><a href="'.$url.'" target="_blank" class="fancybox"><img id="screenshot" src="'.$url.'" style="width:'.$size.'px;border:solid 1px #ccc;" title="'.$title.'"/></a></div>';
			} else {
				$image = '<div class="screenshot" style="padding:5px;background:#fff;width:'.$size_div.'px;"><a href="'.$url.'" target="_blank" class="fancybox"><img id="screenshot" src="'.$url.'" style="width:'.$size.'px;border:solid 1px #ccc;"/></a></div>';
			}
		} elseif ($placeholder) {
			if (isset($title)) {
				$image = '<div class="screenshot" style="padding:5px;background:#fff;"><a href="'.$url.'" target="_blank" class="fancybox"><img id="screenshot" src="' . NEnvironment::getVariable("URI") . '/images/ajax-loader.gif" newsrc="'.$url.'" style="max-width:250px;" title="'.$title.'"/></a></div>';
			} else {
				$image = '<div class="screenshot" style="padding:5px;background:#fff;"><img id="screenshot" src="' . NEnvironment::getVariable("URI") . '/images/ajax-loader.gif" newsrc="'.$url.'" style="max-width:250px;"/></div>';
			}
		}

		return $image;
	}


	/**
	 *	Cleans cache of particular name space.
	 *
	 */
	private function cleanCache($name, $resource_id = null)
	{
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Lister.".$name);
		$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id")));
		$cache = new NCache($storage, "Lister.render.".$name);
		$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id")));
	}

}
