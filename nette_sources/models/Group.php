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
 

class Group extends BaseModel {
	
	private $group_data;
	private $numeric_id;


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function create($group_id = null) {
			return new Group($group_id);
	}


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function __construct($group_id) {
		if(!empty($group_id)) {
//			$result = dibi::fetchAll("SELECT * FROM `group` WHERE `group_id` = %i",$group_id);
			$result = dibi::fetchAll("SELECT `group_id`, `group_author`, `group_name`, `group_description`, `group_language`, `group_visibility_level`, `group_access_level`, `group_status`, `group_viewed`, `group_position_x`, `group_position_y`, `group_last_activity`, `group_portrait`, `group_largeicon`, `group_icon` FROM `group` WHERE `group_id` = %i",$group_id); // , `group_hash`
			if(sizeof($result) > 2) {
				return false;
				throw new Exception("More than one group with the same id found.");
			} 
			if(sizeof($result) < 1) {
				return false;
				throw new Exception("Specified group not found.");
			}
			$data_array = $result[0]->toArray();
			$this->numeric_id = $data_array['group_id'];

			unset($data_array['group_id']);
			$this->group_data = $data_array;
		}
		return true;
	}


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function setGroupData($data) {
		foreach($data as $key=>$value) {
     		$this->group_data[$key] = $value;
    	}
    	
    	// Group hash (key) is not part of data so that it is not exposed through API. Additional security measure to showing group data only to people with sufficient permissions.
		if (isset($data['group_hash'])) {
			$this->group_data['group_hash'] = $data['group_hash'];
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getGroupLanguage($group_id)
	{
		$result = dibi::fetchSingle("SELECT `group_language` FROM `group` WHERE `group_id` = %i ", $group_id);
		if (!empty($result)) {
			return $result;
		}
		return 0;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function getGroupData() {
		$data = $this->group_data;
		
		if(!empty($data)) {
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
			if(!empty($this->group_data)) {
				unset($this->group_data['avatar']);
				if(empty($this->numeric_id)) {
					dibi::query('INSERT INTO `group`', $this->group_data);
					$this->numeric_id = dibi::insertId();
				} else {
					dibi::query('UPDATE `group` SET ', $this->group_data , 'WHERE `group_id` = %i', $this->numeric_id);
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
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function delete($group_id) {
		dibi::query("DELETE FROM `group` WHERE `group_id` = %i",$group_id);
	}


	/**
	 *	Returns the group id
	 *	@param void
	 *	@return int
	 */
	public function getGroupId() {
		return $this->numeric_id;
	}


	/**
	 *	Returns the visibility level: 1: world, 2: deplyoment, 3: connections
	 *	@param void
	 *	@return int
	 */
	public function getVisibilityLevel() {
      return $this->group_data['group_visibility_level'];
   }

	/**
	 *	@todo Returns the access level
	 *	@param
	 *	@return
	 */
	public function getAccessLevel() {
		return $this->group_data['group_access_level'];
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function insertTag($tag_id) {
		$registered_tags = $this->getTags();
		if(!isset($registered_tags[$tag_id])) {
			dibi::query('INSERT INTO `group_tag` (`tag_id`,`group_id`) VALUES (%i,%i)',$tag_id,$this->numeric_id);
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
         dibi::query('DELETE FROM `group_tag` WHERE `tag_id` = %i AND `group_id` = %i',$tag_id,$this->numeric_id);
      }
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getTags() {
		$result = dibi::fetchAll("SELECT gt.`tag_id`,t.`tag_name` FROM `group_tag` gt LEFT JOIN `tag` t ON (t.`tag_id` = gt.`tag_id`) WHERE `group_id` = %i ORDER BY t.`tag_name` ASC",$this->numeric_id);
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
	public static function getName($group_id)
	{
		$result = dibi::fetchSingle("SELECT `group_name` FROM `group` WHERE `group_id` = %i", $group_id);
		if (empty($result)) {
			return false;
		}
		return $result;
	}
	
	/**
	*		Groups tags according to their parent and sorts them by parent, then child
	*/

/**
 *	@todo ### Description
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
	public function getAllUsers($filter) {
    	$limit = null;
    	$count = null;
    	$filter_o = array();
		$filter_o['group_id'] = $this->numeric_id; 
      if(isset($filter['limit'])) {
         $limit = $filter['limit'];
         unset($filter['limit']);
      }
      if(isset($filter['count'])) {
         $count = $filter['count'];
         unset($filter['count']);
      }
      if(isset($filter['access_level']) && $filter['access_level'] != 'null') {
         $filter_o['group_user_access_level'] = $filter['access_level'];
      }
      if(isset($filter['enabled']) && $filter['enabled'] != 'null') {
         $filter_o['group_user_status'] = $filter['enabled'];
      }
      if(isset($filter['name']) && $filter['name'] != "") {
         $filter_o[] = array('%or',array(array('`user_login` LIKE %~like~',$filter['name']),array('`user_name` LIKE %~like~',$filter['name']),array('`user_surname` LIKE %~like~',$filter['name'])));
      }
      if(!is_null($limit) && !is_null($count)) {
         $result = dibi::fetchAll("SELECT u.`user_id`,u.`user_email`,u.`user_login`,u.`user_name`,u.`user_surname`,gu.* FROM `group_user` gu LEFT JOIN `user` u ON (gu.`user_id` = u.`user_id`) WHERE %and LIMIT %i,%i",$filter_o,$limit,$count);
      } else {
         $result = dibi::fetchAll("SELECT u.`user_id`,u.`user_email`,u.`user_login`,u.`user_name`,u.`user_surname`,gu.* FROM `group_user` gu LEFT JOIN `user` u ON (gu.`user_id` = u.`user_id`) WHERE %and",$filter_o);
      }
      $users = array();
      foreach($result as $row) {
         $users[] = $row->toArray();
      }
      return $users;
   }

	/**
	 *	Returns the access level of a particular user at that group. (1: member, 2: group moderator, 3: group adiminstrator/owner)
	 *	@param int $user_id
	 *	@return int
	 */
	public function getUserAccessLevel($user_id) {
		$result = dibi::fetchAll("SELECT `group_user_access_level` FROM `group_user` WHERE `group_id` = %i AND `user_id` = %i",$this->numeric_id,$user_id);
		if(!empty($result[0])) {
			return $result[0]->group_user_access_level;
		}
		return 0;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function userIsRegistered($user_id) {
		$result = dibi::fetchAll("SELECT `user_id` FROM `group_user` WHERE `group_id` = %i AND `user_id` = %i",$this->numeric_id,$user_id);
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
				$data['group_id'] = $this->numeric_id;
				$data['user_id'] = $user_id;
            dibi::query('INSERT INTO `group_user`', $data);
			} else {
            dibi::query('UPDATE `group_user` SET ', $data , 'WHERE `group_id` = %i AND `user_id` = %i', $this->numeric_id,$user_id);
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
            			dibi::query('DELETE FROM `group_user` WHERE `group_id` = %i AND `user_id` = %i', $this->numeric_id,$user_id);
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
	public function incrementVisitor() {
       $result = dibi::query("UPDATE `group` SET `group_viewed` = group_viewed+1 WHERE `group_id` = %i",$this->numeric_id);
   }


	/**
	 *	Returns the type of this item
	 *	@param
	 *	@return
	 */
	public static function getType() {
      return 2;
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getTopGroups($count = 10) {
		$user = NEnvironment::getUser()->getIdentity();
      if(!empty($user)) {
         $visibility = "g.`group_visibility_level` IN ('1','2')";
      } else {
         $visibility = "g.`group_visibility_level` IN ('1')";
      }    
      $users = dibi::fetchAll("SELECT `group_id`,`group_name`,(SELECT COUNT(gu.`user_id`) FROM `group_user` gu WHERE gu.`group_id` = g.`group_id` ) as members FROM `group` g WHERE g.`group_status` = '1' AND ".$visibility." ORDER BY members DESC  LIMIT 0,%i",$count);
      $result = array();
      foreach($users as $row) {
         $data = $row->toArray();
			$result[$data['group_id']]['group_name']=$data['group_name'];
         $result[$data['group_id']]['members']=$data['members'];
      }
      return $result;

   }


	/**
	 *	Retrieve the owner
	 *	@param
	 *	@return
	 */
	public function getOwner() {
		$result = dibi::fetchSingle("SELECT `group_author` FROM `group` WHERE `group_id` = %i",$this->numeric_id);

		$owner = User::create($result);
		if(!empty($owner)) {
			return $owner;
		}
		return null;
	}


	/**
	 *	Assign a new owner to a group
	 *	@param int $user_id id of new owner
	 *	@return
	 */
	public function setOwner($user_id) {
		if(Auth::ADMINISTRATOR == Auth::isAuthorized(2,$this->numeric_id)) {
			return dibi::query("UPDATE `group` SET `group_author`  = %i WHERE `group_id` = %i", $user_id, $this->numeric_id);
		}
		return null;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function hasPosition() {
      $result = dibi::fetchSingle("SELECT `group_id` FROM `group` WHERE `group_id` = %i AND `group_position_x` IS NOT NULL AND `group_position_y` IS NOT NULL",$this->numeric_id);
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
	public function isActive()
	{
		$result = dibi::fetchSingle("SELECT `group_status` FROM `group` WHERE `group_id` = %i", $this->numeric_id);
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
    	if(Auth::ADMINISTRATOR == Auth::isAuthorized(2,$this->numeric_id)) {
    		dibi::query("UPDATE `group` SET `group_status` = '0' WHERE `group_id` = %i",$this->numeric_id);
			dibi::query("UPDATE `resource_user_group` SET `resource_user_group_status` = '0' WHERE `member_type` = '2' AND `member_id` = %i", $this->numeric_id);
			dibi::query("UPDATE `group_user` SET `group_user_status` = '0' WHERE `group_id` = %i", $this->numeric_id);
    	}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function getLastActivity() {
      $result = dibi::fetchSingle("SELECT `group_last_activity` FROM `group` WHERE `group_id` = %i",$this->numeric_id);
      return $result;
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
   public function setLastActivity() {
      dibi::query("UPDATE `group` SET `group_last_activity` = NOW() WHERE `group_id` = %i",$this->numeric_id);
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function getSubscribedResources() {
		$group_id = $this->numeric_id;
        $result = dibi::fetchAll("SELECT * FROM `resource_user_group` LEFT JOIN `resource` ON `resource_user_group`.`resource_id` = `resource`.`resource_id` WHERE `resource`.`resource_type` IN (2,3,4,5,6) AND `resource`.`resource_status` = '1' AND `resource_user_group`.`member_id` = %i AND `resource_user_group`.`member_type` = 2",$group_id);
      return $result;
   }


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getAvatar()
	{
		$portrait = dibi::fetchSingle("SELECT `group_portrait` FROM `group` WHERE `group_id` = %i", $this->numeric_id);
		return $portrait;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function removeAvatar()
	{
		dibi::query("UPDATE `group` SET `group_portrait` = NULL WHERE `group_id` = %i", $this->numeric_id);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function groupHasIcon()
	{
		$result = dibi::fetchSingle("SELECT `group_icon` FROM `group` WHERE `group_id` = %i", $this->numeric_id);
		if (!empty($result)) {
			return true;
		}
		return false;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function getIcon()
	{
		$portrait = dibi::fetchSingle("SELECT `group_icon` FROM `group` WHERE `group_id` = %i", $this->numeric_id);
		return $portrait;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getLargeIcon()
	{
		$portrait = dibi::fetchSingle("SELECT `group_largeicon` FROM `group` WHERE `group_id` = %i", $this->numeric_id);
		return $portrait;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getGroupHash()
	{
		$hash = dibi::fetchSingle("SELECT `group_hash` FROM `group` WHERE `group_id` = %i", $this->numeric_id);
		return $hash;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function removeIcons()
	{
		dibi::query("UPDATE `group` SET `group_icon` = NULL WHERE `group_id` = %i", $this->numeric_id);
		dibi::query("UPDATE `group` SET `group_largeicon` = NULL WHERE `group_id` = %i", $this->numeric_id);
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function isMember($user_id) {
		
		$group_id = $this->numeric_id;
		
        $result = dibi::fetchSingle("SELECT * FROM `group_user` WHERE `user_id` = %i AND `group_id` = %i", $user_id, $group_id);
        
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
	public function setMember($user_id) {
	
		$group_id = $this->numeric_id;
		try {
        dibi::begin();
		dibi::query("INSERT INTO `group_user` SET `user_id` = %i, `group_id` = %i, `group_user_status` = 1, `group_user_access_level` = 1", $user_id, $group_id);
		} catch (Exception $e) {
        	dibi::rollback();
			throw $e;
		}
		dibi::commit();
		
	}


	/**
	 *	Calls renderImg() from Image class. (as static class)
	 *	@param int $group_id
	 *	@param string $size
	 *	@param string $title
	 *	@return string
	 */
	public static function getImage($group_id, $size, $title=null) {
		$image = Image::createimage($group_id, 2);
		if ($image !== false) {
			return $image->renderImg($size, $title);
		} else {
			return Image::default_img($size, $title);
		}

	}


	public function addActivityToChat($object_ids, $object_type, $reason) {

		if (empty($object_ids)) return false;
		
		$session = NEnvironment::getSession()->getNamespace("GLOBAL");
		$prev_language = $session->language;
		_t_set(Language::getFlag($this->group_data['group_language']));

		$resource_name = array(
			1=>'1',
			2=>'event',
			3=>'org',
			4=>'doc',
			6=>'website',
			7=>'7',
			8=>'8',
			9=>'friendship',
			'media_soundcloud'=>'audio',
			'media_youtube'=>'video',
			'media_vimeo'=>'video',
			'media_bambuser'=>'live-video'
			);
		$resource_type_labels = array(
			1=>_t('message'),
			2=>_t('event'),
			3=>_t('organization'),
			4=>_t('document'),
			6=>_t('link to external resource'),
			7=>'7',
			8=>'8',
			9=>'friendship',
			'media_soundcloud'=>_t('sound on Soundcloud'),
			'media_youtube'=>_t('video on YouTube'),
			'media_vimeo'=>_t('video on Vimeo'),
			'media_bambuser'=>_t('live-video on Bambuser')
			);
			
		if (!is_array($object_ids)) {
			if ($object_type == 1) {
				$item_info = '<a href="'.NEnvironment::getVariable("URI").'/user/?user_id='.$object_ids.'">'.User::getImage($object_ids, 'icon', User::getFullname($object_ids)).' </a>';
			} elseif ($object_type == 3) {
				$resource_type = Resource::getResourceType($object_ids);
				$resource_type_icon = $resource_type == 5 ? $resource_name[Resource::getMediaType($object_ids)] : $resource_name[$resource_type];
				$resource_type_label = $resource_type == 5 ? $resource_type_labels[Resource::getMediaType($object_ids)] : $resource_type_labels[$resource_type];
				$item_info = '<a href="'.NEnvironment::getVariable("URI").'/resource/?resource_id='.$object_ids.'"><b class="icon-'.$resource_type_icon.'" title="'.$resource_type_label.'" style="margin-top:-5px;"></b>'.Resource::getName($object_ids)."</a>";
			}
			switch ($reason) {
				case 'subscribe': $message_text = _t("The group has subscribed to the resource %s.", $item_info); break;
				case 'join': $message_text = _t("Please welcome our new member: %s has joined this group.", $item_info); break;
				case 'leave': $message_text = _t("%s has left the group. Good-bye!", $item_info); break;
				default: return false; break;
			}
		} else {
			foreach ($object_ids as $object_id) {
				switch ($reason) {
					case 'unsubscribe': $text = _t("The group has unsubscribed from the following resources:"); break;
					default: return false; break;
				}

				$message_text = "<p>".$text."</p>";
				if ($object_type == 1) {
					$message_text .= "\n".'<p><a href="'.NEnvironment::getVariable("URI").'/user/?user_id='.$object_id.'">'.User::getImage($object_id, 'icon', User::getFullname($object_id)).' </a></p>';
				} elseif ($object_type == 3) {
					$resource_type = Resource::getResourceType($object_id);
					$resource_type_icon = $resource_type == 5 ? $resource_name[Resource::getMediaType($object_id)] : $resource_name[$resource_type];
					$resource_type_label = $resource_type == 5 ? $resource_type_labels[Resource::getMediaType($object_id)] : $resource_type_labels[$resource_type];
					$message_text .= "\n".'<p><a href="'.NEnvironment::getVariable("URI").'/resource/?resource_id='.$object_id.'"><b class="icon-'.$resource_type_icon.'" title="'.$resource_type_label.'"></b>'.Resource::getName($object_id)."</a></p>";
				}
			}
		}
		
		
//		$message_text .= "\n</ul>";
		
		$resource                = Resource::create();
		$data                    = array();
		$data['resource_author'] = 0;
		$data['resource_type']   = 8;
		$data['resource_data']   = json_encode(array(
			'message_text' => $message_text
		));
		$resource->setResourceData($data);
		$resource->save();

		$resource->updateGroup($this->numeric_id, array(
			'resource_user_group_access_level' => 1
		));

		### clear cache for $group_id
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		$cache->clean(array(NCache::TAGS => array("group_id/".$this->numeric_id, "name/chatwidget")));
		$cache->clean(array(NCache::TAGS => array("group_id/".$this->numeric_id, "name/chatlistergroup")));

		// reset language
		_t_set($prev_language);
		
		return true;
	}

}
