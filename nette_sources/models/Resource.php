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
 

class Resource extends BaseModel {
	
	private $resource_data;
	private $numeric_id;
	private $parent_numeric_id = null;
	
	public static function create($resource_id = null) {
		return new Resource($resource_id);
	}

	public function __construct($resource_id) {
		if(!empty($resource_id)) {
			$result = dibi::fetchAll("SELECT * FROM `resource` WHERE `resource_id` = %i",$resource_id);
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

	public function setResourceData($data) {
		if(isset($data['resource_parent_id'])) {
			unset($data['resource_parent_id']);
		}
		foreach($data as $key=>$value) {
     		$this->resource_data[$key] = $value;
    	}
	}

	public function getResourceData() {
		
		$data = $this->resource_data;
		if(isset($data['resource_data'])) {
			$resource_sub_data = json_decode($this->resource_data['resource_data'],true);

			unset($data['resource_data']);
			$data = array_merge($data,$resource_sub_data);
			if($data['resource_type'] == null) {
				$data['resource_type'] = 0;
			}
			if($data['resource_owner'] == null) {
				$data['resource_owner'] = 0;
			}
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
	
	public function setParent($parent_id) {
		$this->parent_numeric_id = $parent_id;
	}

	public function getParentId() {
		return $this->parent_numeric_id;
	}

	public function getParent() {
		if(!is_null($this->parent_numeric_id)) {
			$resource = Resource::create($this->parent_numeric_id);
			return $resource;
		}
		return null;
	}

	public static function delete($resource_id) {
		dibi::query("DELETE FROM `resource` WHERE `resource_id` = %i",$resource_id);
	}
	
	public function getResourceId() {
		return $this->numeric_id;
	}
	
	public function getVisibilityLevel() {
      return $this->resource_data['resource_visibility_level'];
   }
   
	public function insertTag($tag_id) {
		$registered_tags = $this->getTags();
		if(!isset($registered_tags[$tag_id])) {
			dibi::query('INSERT INTO `resource_tag` (`tag_id`,`resource_id`) VALUES (%i,%i)',$tag_id,$this->numeric_id);
		}
	}
	
	public function removeTag($tag_id) {
		$registered_tags = $this->getTags();
      if(isset($registered_tags[$tag_id])) {
         dibi::query('DELETE FROM `resource_tag` WHERE `tag_id` = %i AND `resource_id` = %i',$tag_id,$this->numeric_id);
      }
	}
	
	public function getTags() {
		$result = dibi::fetchAll("SELECT gt.`tag_id`,t.`tag_name` FROM `resource_tag` gt LEFT JOIN `tag` t ON (t.`tag_id` = gt.`tag_id`) WHERE `resource_id` = %i",$this->numeric_id);
		// ORDER BY (SELECT `resource_parent_id` FROM `resource` r LEFT JOIN `resource_tag` rt ON (r.`resource_id` = rt.`resource_id`) ORDER BY `resource`.`resource_id` ASC LIMIT 1), t.`tag_name` ASC
		$array = array();
		foreach($result as $row) {
			$data = $row->toArray();
			$array[$data['tag_id']] = Tag::create($data['tag_id']);//$data['tag_name'];
		}		
		return $array;
	}
	
	
	public static function getName($resource_id)
	{
		$result = dibi::fetchSingle("SELECT `resource_name` FROM `resource` WHERE `resource_id` = %i", $resource_id);
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
   
	public function getUserAccessLevel($user_id) {
		$result = dibi::fetchAll("SELECT `resource_user_group_access_level` FROM `resource_user_group` WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 1",$this->numeric_id,$user_id);
		if(!empty($result[0])) {

			return $result[0]->resource_user_group_access_level;
		}
		return 0;
	}
	
	public function getGroupAccessLevel($group_id) {
      $result = dibi::fetchAll("SELECT `resource_user_group_access_level` FROM `resource_user_group` WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 2",$this->numeric_id,$group_id);
      if(!empty($result[0])) {

         return $result[0]->resource_user_group_access_level;
      }
      return 0;
   }
	
	public function userIsRegistered($user_id) {
		$result = dibi::fetchAll("SELECT `member_id` FROM `resource_user_group` WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 1",$this->numeric_id,$user_id);
		if(!empty($result)) {
			return true;
		} 
      return false;
	}
	
	public function groupIsRegistered($group_id) {
      $result = dibi::fetchAll("SELECT `member_id` FROM `resource_user_group` WHERE `resource_id` = %i AND `member_id` = %i AND `member_type` = 2",$this->numeric_id,$group_id);
      if(!empty($result)) {
         return true;
      }
      return false;
   }
   
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
   
	public static function getTypeArray() {
      $result = dibi::fetchAll("SELECT * FROM `resource_type` WHERE `resource_type_group` = 1");
      $languages = array();
      foreach($result as $row) {
         $data = $row->toArray();
         $languages[$data['resource_type_id']] = $data['resource_type_name'];
      }
      return $languages;
   }

	public function incrementVisitor() {
		 $result = dibi::query("UPDATE `resource` SET `resource_viewed` = resource_viewed+1 WHERE `resource_id` = %i",$this->numeric_id);
	}

	public function setOpened($user_id,$resource_id = 0) {
		if (!$resource_id) $resource_id = $this->numeric_id;
		$result = dibi::query("UPDATE `resource_user_group` SET `resource_opened_by_user` = '1' WHERE `resource_id` = %i AND `member_type` = 1 AND `member_id` = %i",$resource_id,$user_id);
	}

	public function setUnopened($user_id,$resource_id = 0) {
		if (!$resource_id) $resource_id = $this->numeric_id;
		$result = dibi::query("UPDATE `resource_user_group` SET `resource_opened_by_user` = '0' WHERE `resource_id` = %i AND `member_type` = 1 AND `member_id` = %i",$resource_id,$user_id);
	}
	
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

	public static function getUnreadMessages() {
		$user = NEnvironment::getUser()->getIdentity();
		if(!empty($user)) {
			$user_id = $user->getUserId();
			$count = dibi::fetchSingle("SELECT COUNT(`resource`.`resource_id`) FROM `resource`  LEFT JOIN `resource_user_group` ON `resource`.`resource_id` = `resource_user_group`.`resource_id` WHERE `resource_user_group`.`resource_opened_by_user` = 0 AND (`resource`.`resource_type` = 1 OR `resource`.`resource_type` = 9) AND `resource`.`resource_author` <> %i AND `resource_user_group`.`member_type` = 1 AND `resource_user_group`.`member_id` = %i AND `resource`.`resource_status` <> 0",$user_id,$user_id);
			return $count;
		} else {
			return 0;
		}
		
	}

	public static function getType() {
      return 3;
   }
   
	public function getResourceAuthor() {
		return $resource_data['resource_author'];		
	}
	

	public function getOwner() {
      $result = dibi::fetchSingle("SELECT `resource_author` FROM `resource` WHERE `resource_id` = %i",$this->numeric_id);

      $owner = User::create($result);
      if(!empty($owner)) {
         return $owner;
      }
      return null;
	}
   
	public function hasPosition() {
      $result = dibi::fetchSingle("SELECT `resource_id` FROM `resource` WHERE `resource_id` = %i AND `resource_position_x` IS NOT NULL AND `resource_position_y` IS NOT NULL",$this->numeric_id);
      if(!empty($result)) {
         return true;
      }
      return false;
   }

	public static function moveToTrash($resource_id) {
		$user = NEnvironment::getUser()->getIdentity();
    	if(!empty($user)) {
			dibi::query("UPDATE `resource_user_group` SET `resource_trash` = '1' WHERE `resource_id` = %i AND `member_type` = '1' AND `member_id` = %i",$resource_id,$user->getUserId());
		}
	}

	public static function moveFromTrash($resource_id) {
		$user = NEnvironment::getUser()->getIdentity();
      if(!empty($user)) {
			dibi::query("UPDATE `resource_user_group` SET `resource_trash` = '0' WHERE `resource_id` = %i AND `member_type` = '1' AND `member_id` = %i",$resource_id,$user->getUserId());
		}
   }

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
	
	public function bann() {
      if(Auth::MODERATOR <= Auth::isAuthorized(3,$this->numeric_id)) {
         dibi::query("UPDATE `resource` SET `resource_status` = '0' WHERE `resource_id` = %i",$this->numeric_id);
      }
   }

	public function remove_message($group_id) {
      if(Auth::MODERATOR <= Auth::isAuthorized(2,$group_id) && $this->groupIsRegistered($group_id)) {
         dibi::query("UPDATE `resource` SET `resource_status` = '0' WHERE `resource_id` = %i",$this->numeric_id);
      }
   }

	public function getLastActivity() {
      $result = dibi::fetchSingle("SELECT `resource_last_activity` FROM `resource` WHERE `resource_id` = %i",$this->numeric_id);
      return $result;
   }

   public function setLastActivity() {
      dibi::query("UPDATE `resource` SET `resource_last_activity` = NOW() WHERE `resource_id` = %i",$this->numeric_id);
   }

	public function getThumbnailUrl() {
	
		$url = '';
		$data = $this->getResourceData();
		
		switch ($data['resource_type']) {
			case 2: if (isset($data['event_url']) && !empty($data['event_url'])) $url = $data['event_url']; break;
			case 3: if (isset($data['organization_url']) && !empty($data['organization_url'])) $url = $data['organization_url']; break;
			case 4: if (isset($data['text_information_url']) && !empty($data['text_information_url'])) $url = $data['text_information_url']; break;
			case 5: if (isset($data['media_link']) && !empty($data['media_link'])) {
						switch ($data['media_type']) {
							case 'media_soundcloud':  $url = 'http://soundcloud.com/'.$data['media_link']; break;
							case 'media_youtube': $url = 'https://img.youtube.com/vi/'.$data['media_link'].'/1.jpg'; break;
							case 'media_vimeo': $tmp = unserialize(@file_get_contents("http://vimeo.com/api/v2/video/".$data['media_link'].".php"));$url = $tmp[0]['thumbnail_medium']; break;
							case 'media_bambuser': break;
							// 'http://static.bambuser.com/modules/b/ui/bambuser_ui/no-preview-140x115.png'
							// 'http://bambuser.com/v/'.$data['media_link']
						}
					}
			break;			case 6: if (isset($data['other_url']) && !empty($data['other_url'])) $url = $data['other_url']; break;
		}

		return $url;
	
	}

	public function getScreenshot($title=null, $placeholder=false) {
	
		$image = '';
		if (isset($title)) $title_tag =' title="'.$title.'"'; else $title_tag='';
		
		$url = $this->getThumbnailUrl();
				
		if (!empty($url)) {
			$md5 = md5($url);
			$link = '/images/cache/resource/'.$this->numeric_id.'-screenshot-'.$md5.'.jpg';
			$filepath = WWW_DIR.$link;
			
			if (file_exists($filepath)) {
				$image = '<div class="screenshot" style="padding:5px;background:#fff;width:260px;"><a href="'.$link.'" target="_blank" class="fancybox"><img id="screenshot" src="'.$link.'" style="width:250px;border:solid 1px #ccc;" '.$title_tag.'/></a></div>';
			} elseif ($placeholder) {
				$image = '<div class="screenshot" style="padding:5px;background:#fff;"><a href="'.$link.'" target="_blank" class="fancybox"><img id="screenshot" src="/images/ajax-loader.gif" newsrc="'.$link.'" style="max-width:250px;" '.$title_tag.'/></a></div>';
			}
		}

		return $image;
	}

}
