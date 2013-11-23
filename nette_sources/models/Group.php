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
 

class Group extends BaseModel {
	
	private $group_data;
	private $numeric_id;
	
	public static function create($group_id = null) {
			return new Group($group_id);
	}

	public function __construct($group_id) {
		if(!empty($group_id)) {
			$result = dibi::fetchAll("SELECT * FROM `group` WHERE `group_id` = %i",$group_id);
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

	public function setGroupData($data) {
		foreach($data as $key=>$value) {
     		$this->group_data[$key] = $value;
    	}
	}

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

	public function save() {
		try {
			dibi::begin();
			if(!empty($this->group_data)) {
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

	public static function delete($group_id) {
		dibi::query("DELETE FROM `group` WHERE `group_id` = %i",$group_id);
	}
	
	public function getGroupId() {
		return $this->numeric_id;
	}
	
	public function getVisibilityLevel() {
      return $this->group_data['group_visibility_level'];
   }
	
	public function getAccessLevel() {
		return $this->group_data['group_access_level'];
	}
	
	public function insertTag($tag_id) {
		$registered_tags = $this->getTags();
		if(!isset($registered_tags[$tag_id])) {
			dibi::query('INSERT INTO `group_tag` (`tag_id`,`group_id`) VALUES (%i,%i)',$tag_id,$this->numeric_id);
		}
	}
	
	public function removeTag($tag_id) {
		$registered_tags = $this->getTags();
      if(isset($registered_tags[$tag_id])) {
         dibi::query('DELETE FROM `group_tag` WHERE `tag_id` = %i AND `group_id` = %i',$tag_id,$this->numeric_id);
      }
	}
	
	public function getTags() {
		$result = dibi::fetchAll("SELECT gt.`tag_id`,t.`tag_name` FROM `group_tag` gt LEFT JOIN `tag` t ON (t.`tag_id` = gt.`tag_id`) WHERE `group_id` = %i ORDER BY t.`tag_name` ASC",$this->numeric_id);
		$array = array();
		foreach($result as $row) {
			$data = $row->toArray();
			$array[$data['tag_id']] = Tag::create($data['tag_id']);//$data['tag_name'];
		}		
		return $array;
	}
	
	
	public static function getName($group_id)
	{
		$result = dibi::fetchSingle("SELECT `group_name` FROM `group` WHERE `group_id` = %i", $group_id);
		if (empty($result)) {
			return "";
		}
		return $result;
	}
	
	/**
	*		Groups tags according to their parent and sorts them by parent, then child
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
         $result = dibi::fetchAll("SELECT u.`user_login`,u.`user_name`,u.`user_surname`,gu.* FROM `group_user` gu LEFT JOIN `user` u ON (gu.`user_id` = u.`user_id`) WHERE %and LIMIT %i,%i",$filter_o,$limit,$count);
      } else {
         $result = dibi::fetchAll("SELECT u.`user_login`,u.`user_name`,u.`user_surname`,gu.* FROM `group_user` gu LEFT JOIN `user` u ON (gu.`user_id` = u.`user_id`) WHERE %and",$filter_o);
      }
      $users = array();
      foreach($result as $row) {
         $users[] = $row->toArray();
      }
      return $users;
   }
   
	public function getUserAccessLevel($user_id) {
		$result = dibi::fetchAll("SELECT `group_user_access_level` FROM `group_user` WHERE `group_id` = %i AND `user_id` = %i",$this->numeric_id,$user_id);
		if(!empty($result[0])) {

		
			return $result[0]->group_user_access_level;
		}
		return 0;
	}
	
	public function userIsRegistered($user_id) {
		$result = dibi::fetchAll("SELECT `user_id` FROM `group_user` WHERE `group_id` = %i AND `user_id` = %i",$this->numeric_id,$user_id);
		if(!empty($result)) {
			return true;
		} 
      return false;
	}
	
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
   	
	public function incrementVisitor() {
       $result = dibi::query("UPDATE `group` SET `group_viewed` = group_viewed+1 WHERE `group_id` = %i",$this->numeric_id);
   }
   
	public static function getType() {
      return 2;
   }
   
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
   
	public function getOwner() {
		$result = dibi::fetchSingle("SELECT `group_author` FROM `group` WHERE `group_id` = %i",$this->numeric_id);

		$owner = User::create($result);
		if(!empty($owner)) {
			return $owner;
		}
		return null;
	}
	
	public function hasPosition() {
      $result = dibi::fetchSingle("SELECT `group_id` FROM `group` WHERE `group_id` = %i AND `group_position_x` IS NOT NULL AND `group_position_y` IS NOT NULL",$this->numeric_id);
      if(!empty($result)) {
         return true;
      }
      return false;
   }

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
	   
	public function bann() {
      if(Auth::ADMINISTRATOR == Auth::isAuthorized(2,$this->numeric_id)) {
         dibi::query("UPDATE `group` SET `group_status` = '0' WHERE `group_id` = %i",$this->numeric_id);
      }
   }

	public function getLastActivity() {
      $result = dibi::fetchSingle("SELECT `group_last_activity` FROM `group` WHERE `group_id` = %i",$this->numeric_id);
      return $result;
   }

   public function setLastActivity() {
      dibi::query("UPDATE `group` SET `group_last_activity` = NOW() WHERE `group_id` = %i",$this->numeric_id);
   }

	public function getSubscribedResources() {
		$group_id = $this->numeric_id;
        $result = dibi::fetchAll("SELECT * FROM `resource_user_group` LEFT JOIN `resource` ON `resource_user_group`.`resource_id` = `resource`.`resource_id` WHERE `resource`.`resource_type` IN (2,3,4,5,6) AND `resource`.`resource_status` = '1' AND `resource_user_group`.`member_id` = %i AND `resource_user_group`.`member_type` = 2",$group_id);
      return $result;
   }

	public function getAvatar()
	{
		$portrait = dibi::fetchSingle("SELECT `group_portrait` FROM `group` WHERE `group_id` = %i", $this->numeric_id);
		return $portrait;
	}
	
	public function removeAvatar()
	{
		dibi::query("UPDATE `group` SET `group_portrait` = NULL WHERE `group_id` = %i", $this->numeric_id);
	}

	public function groupHasIcon()
	{
		$result = dibi::fetchSingle("SELECT `group_icon` FROM `group` WHERE `group_id` = %i", $this->numeric_id);
		if (!empty($result)) {
			return true;
		}
		return false;
	}

	public function getIcon()
	{
		$portrait = dibi::fetchSingle("SELECT `group_icon` FROM `group` WHERE `group_id` = %i", $this->numeric_id);
		return $portrait;
	}
	
	public function getBigIcon()
	{
		$portrait = dibi::fetchSingle("SELECT `group_largeicon` FROM `group` WHERE `group_id` = %i", $this->numeric_id);
		return $portrait;
	}
	
	public function getGroupHash()
	{
		$hash = dibi::fetchSingle("SELECT `group_hash` FROM `group` WHERE `group_id` = %i", $this->numeric_id);
		return $hash;
	}
	
	public function removeIcons()
	{
		dibi::query("UPDATE `group` SET `group_icon` = NULL WHERE `group_id` = %i", $this->numeric_id);
		dibi::query("UPDATE `group` SET `group_largeicon` = NULL WHERE `group_id` = %i", $this->numeric_id);
	}
	
	public function isMember($user_id) {
		
		$group_id = $this->numeric_id;
		
        $result = dibi::fetchSingle("SELECT * FROM `group_user` WHERE `user_id` = %i AND `group_id` = %i", $user_id, $group_id);
        
		if(!empty($result)) {
			return true;
		}
		return false;
   }

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

	
	public static function getImage($group_id,$size,$title=null) {
	
		$width=20;
		if (isset($title)) $title_tag =' title="'.$title.'"'; else $title_tag='';

		// serving as image file
		$group = new Group($group_id,$size);
		switch ($size) {
			case 'img': $src = $group->getAvatar(); $width=160; break;
			case 'icon': $src = $group->getIcon(); $width=20; break;
			case 'large_icon': $src = $group->getBigIcon(); $width=40; break;
		}
		
		if (!empty($src) && (Auth::isAuthorized(2, $group_id)>0)) {
			$hash=md5($src);
			$link = '/images/cache/group/'.$group_id.'-'.$size.'-'.$hash.'.jpg';
			$image = '<img src="'.$link.'" width="'.$width.'"'.$title_tag.'/>';
		} else {
			$image = '<img src="/images/group-'.$size.'.png" width="'.$width.'"'.$title_tag.'/>';
		}
		return $image;

	}

	public function saveImage($id) {
	
		$object = Group::create($id);
		
		if (isset($object)) {
		
			$sizes = array('img', 'icon', 'large_icon');
		
			foreach ( $sizes as $size) {
		
				switch ($size) {
					case 'img': $src = $object->getAvatar(); break;
					case 'icon': $src = $object->getIcon(); break;
					case 'large_icon': $src = $object->getBigIcon(); break;
				}
	
				if (!empty($src)) {
					$hash=md5($src);
		
					$link = WWW_DIR.'/images/cache/group/'.$id.'-'.$size.'-'.$hash.'.jpg';
		
					if(!file_exists($link)) {
						$img_r = @imagecreatefromstring(base64_decode($src));
						if (!imagejpeg($img_r, $link)) {
							$this->flashMessage(_("Error writing image: ").$link, 'error');
						};
					}
				}

			}

		}
	}
}
