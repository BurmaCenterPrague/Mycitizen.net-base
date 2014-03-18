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
 

class Administration extends BaseModel
{
	public static function systemCheck() {
		$result['groups_wo_owner'] = dibi::fetchSingle("SELECT COUNT(`group_id`) FROM `group` gg WHERE gg.`group_author` = '0'");
		$result['resources_wo_owner'] = dibi::fetchSingle("SELECT COUNT(`resource_id`) FROM `resource` rr WHERE rr.`resource_author` = '0'");
		
		return $result;
	}


	/**
	 *	retrieves selected information for the statistics in the admin back end
	 *
	 *	@param void
	 *	@return array containing the data
	 */
	public static function getStatistics() {

		// users
		$result['users'] = dibi::fetchSingle("SELECT COUNT(`user_id`) FROM `user`");
		$result['admins'] = dibi::fetchSingle("SELECT COUNT(`user_id`) FROM `user` uu WHERE uu.`user_access_level` = '3'");
		$result['mods'] = dibi::fetchSingle("SELECT COUNT(`user_id`) FROM `user` uu WHERE uu.`user_access_level` = '2'");
		$result['deactivated_users'] = dibi::fetchSingle("SELECT COUNT(`user_id`) FROM `user` uu WHERE uu.`user_status` = '2'");
		$result['banned_users'] = dibi::fetchSingle("SELECT COUNT(`user_id`) FROM `user` uu WHERE uu.`user_status` = '3'");
		$result['unconfirmed_users'] = dibi::fetchSingle("SELECT COUNT(`user_id`) FROM `user` uu WHERE uu.`user_registration_confirmed` = '0'");
		$result['users_v_1'] = dibi::fetchSingle("SELECT COUNT(`user_id`) FROM `user` uu WHERE uu.`user_visibility_level` = '1'");
		$result['users_v_2'] = dibi::fetchSingle("SELECT COUNT(`user_id`) FROM `user` uu WHERE uu.`user_visibility_level` = '2'");
		$result['users_v_3'] = dibi::fetchSingle("SELECT COUNT(`user_id`) FROM `user` uu WHERE uu.`user_visibility_level` = '3'");
		$result['no_creation_rights'] = dibi::fetchSingle("SELECT COUNT(`user_id`) FROM `user` uu WHERE uu.`user_creation_rights` = '0'");


		// groups
		$result['groups'] = dibi::fetchSingle("SELECT COUNT(`group_id`) FROM `group`");
		$result['deactivated_groups'] = dibi::fetchSingle("SELECT COUNT(`group_id`) FROM `group` gg WHERE gg.`group_status` = '2'");
		$result['groups_v_1'] = dibi::fetchSingle("SELECT COUNT(`group_id`) FROM `group` gg WHERE gg.`group_visibility_level` = '1'");
		$result['groups_v_2'] = dibi::fetchSingle("SELECT COUNT(`group_id`) FROM `group` gg WHERE gg.`group_visibility_level` = '2'");
		$result['groups_v_3'] = dibi::fetchSingle("SELECT COUNT(`group_id`) FROM `group` gg WHERE gg.`group_visibility_level` = '3'");
		
		// resources
		$result['resources'] = dibi::fetchSingle("SELECT COUNT(`resource_id`) FROM `resource` rr WHERE rr.`resource_type` IN (2,3,4,5,6)");
		$result['deactivated_resources'] = dibi::fetchSingle("SELECT COUNT(`resource_id`) FROM `resource` rr WHERE rr.`resource_status` = '2' AND rr.`resource_type` IN (2,3,4,5,6)");
		$result['resources_v_1'] = dibi::fetchSingle("SELECT COUNT(`resource_id`) FROM `resource` rr WHERE rr.`resource_visibility_level` = '1' AND rr.`resource_type` IN (2,3,4,5,6)");
		$result['resources_v_2'] = dibi::fetchSingle("SELECT COUNT(`resource_id`) FROM `resource` rr WHERE rr.`resource_visibility_level` = '2' AND rr.`resource_type` IN (2,3,4,5,6)");
		$result['resources_v_3'] = dibi::fetchSingle("SELECT COUNT(`resource_id`) FROM `resource` rr WHERE rr.`resource_visibility_level` = '3' AND rr.`resource_type` IN (2,3,4,5,6)");
		
		// messages
		$result['private_messages'] = dibi::fetchSingle("SELECT COUNT(`resource_id`) FROM `resource` rr WHERE rr.`resource_type` = 1");
		$result['chat_messages'] = dibi::fetchSingle("SELECT COUNT(`resource_id`) FROM `resource` rr WHERE rr.`resource_type` = 8");		
		return $result;
	
	}


	/**
	 *	Deletes users from the database who never confirmed their registration and who registered min. a defined time ago
	 *	
	 *	@param int $months Period before which users who are now still inactive had registered 
	 *	@return int Number of affected rows
	 */
	public static function clearUsers($months) {
		if ($months < 1) $months = 1;
		return dibi::query("DELETE FROM `user` WHERE `user_registration_confirmed` = 0 AND `user_status` = 0 AND  `user_registration` < NOW() - INTERVAL %i MONTH", $months);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getAllUsers($filter)
	{
		$limit    = null;
		$count    = null;
		$filter_o = array();
		if (isset($filter['limit'])) {
			$limit = $filter['limit'];
			unset($filter['limit']);
		}
		if (isset($filter['count'])) {
			$count = $filter['count'];
			unset($filter['count']);
		}
		if (isset($filter['user_access_level']) && $filter['user_access_level'] != 'null') {
			$filter_o['user_access_level'] = $filter['user_access_level'];
		}
		if (isset($filter['user_status']) && $filter['user_status'] != 'null') {
			$filter_o['user_status'] = $filter['user_status'];
		}
		if (isset($filter['name']) && $filter['name'] != "") {
			$filter_o[] = array(
				'%or',
				array(
					array(
						'`user_login` LIKE %~like~',
						$filter['name']
					),
					array(
						'`user_name` LIKE %~like~',
						$filter['name']
					),
					array(
						'`user_surname` LIKE %~like~',
						$filter['name']
					)
				)
			);
		}
		if (!is_null($limit) && !is_null($count)) {
			$result = dibi::fetchAll("SELECT * FROM `user` WHERE %and LIMIT %i,%i", $filter_o, $limit, $count);
		} else {
			$result = dibi::fetchAll("SELECT * FROM `user` WHERE %and", $filter_o);
		}
		$users = array();
		foreach ($result as $row) {
			$data      = $row->toArray();
			$result_2  = dibi::fetchAll("SELECT `resource_id` FROM `resource_user_group` WHERE `member_id` = %i AND `member_type` = 1", $data['user_id']);
			$resources = array();
			foreach ($result_2 as $r2_row) {
				$res         = $r2_row->toArray();
				$resources[] = $res['resource_id'];
			}
			$data['registered_resources'] = $resources;
			$users[]                      = $data;
		}
		return $users;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getAllGroups($filter)
	{
		$limit    = null;
		$count    = null;
		$filter_o = array();
		if (isset($filter['limit'])) {
			$limit = $filter['limit'];
			unset($filter['limit']);
		}
		if (isset($filter['count'])) {
			$count = $filter['count'];
			unset($filter['count']);
		}
		if (isset($filter['group_status']) && $filter['group_status'] != 'null') {
			$filter_o['group_status'] = $filter['group_status'];
		}
		if (isset($filter['name']) && $filter['name'] != "") {
			$filter_o[] = array(
				array(
					'`group_name` LIKE %~like~',
					$filter['name']
				)
			);
		}
		if (!is_null($limit) && !is_null($count)) {
			$result = dibi::fetchAll("SELECT * FROM `group` g WHERE %and GROUP BY g.`group_id` LIMIT %i,%i", $filter_o, $limit, $count);
		} else {
			$result = dibi::fetchAll("SELECT * FROM `group` g WHERE %and GROUP BY g.`group_id`", $filter_o);
		}
		$groups = array();
		foreach ($result as $row) {
			
			$data         = $row->toArray();
			$group_object = Group::create($data['group_id']);
			$user         = NEnvironment::getUser()->getIdentity();
			if (!empty($user)) {
				if ($group_object->userIsRegistered($user->getUserId())) {
					$data['logged_user_member'] = 1;
				} else {
					$data['logged_user_member'] = 0;
				}
			} else {
				$data['logged_user_member'] = -1;
			}
			$result_2  = dibi::fetchAll("SELECT `resource_id` FROM `resource_user_group` WHERE `member_id` = %i AND `member_type` = 2", $data['group_id']);
			$resources = array();
			foreach ($result_2 as $r2_row) {
				$res         = $r2_row->toArray();
				$resources[] = $res['resource_id'];
			}
			$data['registered_resources'] = $resources;
			$groups[]                     = $data;
		}
		return $groups;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getAllResources($filter)
	{
		$limit    = null;
		$count    = null;
		$filter_o = array();
		if (isset($filter['limit'])) {
			$limit = $filter['limit'];
			unset($filter['limit']);
		}
		if (isset($filter['count'])) {
			$count = $filter['count'];
			unset($filter['count']);
		}
		if (isset($filter['resource_status']) && $filter['resource_status'] != 'null') {
			$filter_o['resource_status'] = $filter['resource_status'];
		}
		if (isset($filter['name']) && $filter['name'] != "") {
			$filter_o[] = array(
				array(
					'`resource_name` LIKE %~like~',
					$filter['name']
				)
			);
		}
		if (isset($filter['resource_type'])) {
			$filter_o[] = array(
				array(
					'`resource_type` = %i',
					$filter['resource_type']
				)
			);
		}
		if (!is_null($limit) && !is_null($count)) {
			$result = dibi::fetchAll("SELECT * FROM `resource` r WHERE %and GROUP BY r.`resource_id` LIMIT %i,%i", $filter_o, $limit, $count);
		} else {
			$result = dibi::fetchAll("SELECT * FROM `resource` r WHERE %and GROUP BY r.`resource_id`", $filter_o);
		}
		$groups = array();
		foreach ($result as $row) {
			$groups[] = $row->toArray();
		}
		return $groups;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getAllTags($filter)
	{
		$limit      = null;
		$count      = null;
		$filter_o   = array();
		$filter_tag = array();
		
		if (isset($filter['limit'])) {
			$limit = $filter['limit'];
			unset($filter['limit']);
		}
		if (isset($filter['count'])) {
			$count = $filter['count'];
			unset($filter['count']);
		}
		if (isset($filter['name']) && $filter['name'] != "") {
			$filter_o[] = array(
				array(
					'`tag_name` LIKE %~like~',
					$filter['name']
				)
			);
		}
		if (!is_null($limit) && !is_null($count)) {
			$result = dibi::fetchAll("SELECT * FROM `tag` WHERE %and ORDER BY `tag_position`,`tag_parent_id`,`tag_id` LIMIT %i,%i", $filter_o, $limit, $count);
		} else {
			$result = dibi::fetchAll("SELECT * FROM `tag` WHERE %and ORDER BY `tag_position`,`tag_parent_id`,`tag_id`", $filter_o);
		}
		$tags = array();
		foreach ($result as $row) {
			$tags[] = $row->toArray();
		}
		return $tags;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getData($types, $filter, $counter_mode = false)
	{
		$user                = NEnvironment::getUser()->getIdentity();
		$sql                 = "";
		$sql_user_select     = "";
		$sql_user            = "";
		$sql_group_select    = "";
		$sql_group           = "";
		$sql_resource_select = "";
		$sql_resource        = "";
		$filter_o            = array();
		foreach ($types as $key => $type) {
		
			if ($type == ListerControlMain::LISTER_TYPE_USER || $type == ListerControlMain::LISTER_TYPE_USER_DETAIL) {
				$filter_o['user'] = array();
				$types[$key]      = "user";
				if ($counter_mode) {
					$sql_user = "SELECT COUNT(`user`.`user_id`) as count FROM `user`";
				} else {
					$sql_user = "SELECT '" . User::getType() . "' as type,'user' as type_name,`user`.`user_id` as id,`user`.`user_login` as name,`user`.`user_access_level` as access_level,`user`.`user_visibility_level` as visibility_level,`user`.`user_status` as status, `user_viewed` as viewed, `user_largeicon` as avatar, (SELECT COUNT(`friend_id`) FROM `user_friend` uu WHERE uu.`user_id` = `user`.`user_id` AND uu.`user_friend_status` = '2') as links FROM `user`";
				}
				if (isset($filter['user_id'])) {
					if ($counter_mode) {
						$sql_user = "SELECT COUNT(`user`.`user_id`) as count FROM `user`";
					} else {
						$sql_user = "SELECT '" . User::getType() . "' as type,'user' as type_name,`user`.`user_id` as id,`user`.`user_login` as name,`user_friend`.`user_friend_access_level` as access_level,`user`.`user_visibility_level` as visibility_level,`user_friend`.`user_friend_status` as status, `user_viewed` as viewed, `user_largeicon` as avatar, (SELECT COUNT(`friend_id`) FROM `user_friend` uu WHERE uu.`user_id` = `user`.`user_id` AND uu.`user_friend_status` = '2') as links FROM `user`";
					}
					$sql_user .= " INNER JOIN `user_friend` ON (`user_friend`.`friend_id` = `user`.`user_id` AND `user_friend`.`user_id` = '" . $filter['user_id'] . "' AND `user_friend`.`user_friend_status` = '2')";
				}
				if (isset($filter['group_id'])) {
					if ($counter_mode) {
						$sql_user = "SELECT COUNT(`user`.`user_id`) as count FROM `user`";
					} else {
						
						$sql_user = "SELECT '" . User::getType() . "' as type,'user' as type_name,`user`.`user_id` as id,`user`.`user_login` as name,`group_user`.`group_user_access_level` as access_level,`user`.`user_visibility_level` as visibility_level,`group_user`.`group_user_status` as status, `user_viewed` as viewed, `user_largeicon` as avatar, (SELECT COUNT(`friend_id`) FROM `user_friend` uu WHERE uu.`user_id` = `user`.`user_id` AND uu.`user_friend_status` = '2') as links FROM `user`";
					}
					$sql_user .= " INNER JOIN `group_user` ON (`group_user`.`user_id` = `user`.`user_id` AND `group_user`.`group_id` = '" . $filter['group_id'] . "')";
				}
				if (isset($filter['resource_id'])) {
					if ($counter_mode) {
						$sql_user = "SELECT COUNT(`user`.`user_id`) as count FROM `user`";
					} else {
						
						$sql_user = "SELECT '" . User::getType() . "' as type,'user' as type_name,`user`.`user_id` as id,`user`.`user_login` as name,`resource_user_group`.`resource_user_group_access_level` as access_level,`user`.`user_visibility_level` as visibility_level,`resource_user_group`.`resource_user_group_status` as status,`user_viewed` as viewed, `user_largeicon` as avatar, (SELECT COUNT(`friend_id`) FROM `user_friend` uu WHERE uu.`user_id` = `user`.`user_id` AND uu.`user_friend_status` = '2') as links FROM `user`";
					}
					$sql_user .= " INNER JOIN `resource_user_group` ON (`resource_user_group`.`member_id` = `user`.`user_id` AND `resource_user_group`.`member_type` = '1' AND `resource_user_group`.`resource_id` = '" . $filter['resource_id'] . "')";
					
				}	
			}
			
			if ($type == ListerControlMain::LISTER_TYPE_GROUP || $type == ListerControlMain::LISTER_TYPE_GROUP_DETAIL) {
				$filter_o['group'] = array();
				$types[$key]       = "group";
				if ($counter_mode) {
					$sql_group = "SELECT COUNT(`group`.`group_id`) as count FROM `group`";
				} else {
					$sql_group = "SELECT '" . Group::getType() . "' as type,'group' as type_name,`group`.`group_id` as id,`group`.`group_name` as name,`group`.`group_description` as description,`group`.`group_access_level` as access_level,`group`.`group_visibility_level` as visibility_level,`group`.`group_status` as status,`group_viewed` as viewed, `group_icon` as icon, `group_largeicon` as avatar, (SELECT COUNT(`user_id`) FROM `group_user` gu WHERE gu.`group_id` = `group`.`group_id`) as links FROM `group`";
				}
				if (isset($filter['user_id'])) {
					$sql_group .= " INNER JOIN `group_user` ON (`group_user`.`group_id` = `group`.`group_id` AND `group_user`.`user_id` = '" . $filter['user_id'] . "')";
				}
				if (isset($filter['group_id'])) {
				}
				if (isset($filter['resource_id'])) {
					if ($counter_mode) {
						$sql_group = "SELECT COUNT(`group`.`group_id`) as count FROM `group`";
					} else {
						$sql_group = "SELECT '" . Group::getType() . "' as type,'group' as type_name,`group`.`group_id` as id,`group`.`group_name` as name,`group`.`group_description` as description,`resource_user_group`.`resource_user_group_access_level` as access_level,`group`.`group_visibility_level` as visibility_level,`resource_user_group`.`resource_user_group_status` as status,`group_viewed` as viewed, `group_icon` as icon, `group_largeicon` as avatar, (SELECT COUNT(`user_id`) FROM `group_user` gu WHERE gu.`group_id` = `group`.`group_id`) as links FROM `group`";
					}
					$sql_group .= " INNER JOIN `resource_user_group` ON (`resource_user_group`.`member_id` = `group`.`group_id` AND `resource_user_group`.`member_type` = '2' AND `resource_user_group`.`resource_id` = '" . $filter['resource_id'] . "')";
				}	
			}
			
			if ($type == ListerControlMain::LISTER_TYPE_RESOURCE || $type == ListerControlMain::LISTER_TYPE_RESOURCE_DETAIL) {
				$filter_o['resource'] = array();
				$types[$key]          = "resource";
				if ($counter_mode) {
					$sql_resource = "SELECT COUNT(`resource`.`resource_id`) as count FROM `resource`";
				} else {
				$trash = "";
				if(!empty($user)) {
					$trash = ",opened.`resource_trash` as trashed,`resource`.`resource_author` as author,`resource`.`resource_type` as message_type";
				}
				$sql_resource = "SELECT '".Resource::getType()."' as type,'resource' as type_name,`resource`.`resource_id` as id,`resource`.`resource_name` as name,`resource`.`resource_description` as description,'0' as access_level,`resource`.`resource_visibility_level` as visibility_level,`resource`.`resource_status` as status,`resource_viewed` as viewed, `resource_type` as type, (SELECT COUNT(`member_id`) FROM `resource_user_group` ru WHERE ru.`resource_id` = `resource`.`resource_id` AND ru.`member_type` = '1' AND ru.`resource_user_group_status` = '1') as links,`resource_data`".$trash." FROM `resource`";
				}
				if (!empty($user)) {
					$sql_resource .= " LEFT JOIN `resource_user_group` opened ON (opened.`resource_id` = `resource`.`resource_id` AND opened.`member_type` = 1 AND opened.`member_id` = '" . $user->getUserId() . "')";
				}
				if (isset($filter['user_id'])) {
					$sql_resource .= " INNER JOIN `resource_user_group` ON (`resource_user_group`.`resource_id` = `resource`.`resource_id` AND `resource_user_group`.`member_type` = '1' AND `resource_user_group`.`resource_user_group_status` = '1' AND `resource_user_group`.`member_id` = '" . $filter['user_id'] . "')";
				}
				if (isset($filter['all_members_only'])) {
					foreach ($filter['all_members_only'] as $key => $member) {
						if (is_array($member['id'])) {
							$ids_s = '(' . implode(',', $member['id']) . ')';
							$sql_resource .= " INNER JOIN `resource_user_group` member" . $key . " ON (member$key.`resource_id` = `resource`.`resource_id` AND member$key.`member_type` = '" . $member['type'] . "' AND member$key.`member_id` IN " . $ids_s . ")";
						} else {
							$sql_resource .= " INNER JOIN `resource_user_group` member" . $key . " ON (member$key.`resource_id` = `resource`.`resource_id` AND member$key.`member_type` = '" . $member['type'] . "' AND member$key.`member_id` = '" . $member['id'] . "')";
						}
					}
				}
				
				if (isset($filter['group_id'])) {
					$sql_resource .= " INNER JOIN `resource_user_group` ON (`resource_user_group`.`resource_id` = `resource`.`resource_id` AND `resource_user_group`.`member_type` = '2' AND `resource_user_group`.`resource_user_group_status` = '1' AND `resource_user_group`.`member_id` = '" . $filter['group_id'] . "')";
				}
				if (isset($filter['resource_id'])) {
					$filter_o['resource'][] = array(
						array(
							'`resource_parent_id` = %i',
							$filter['resource_id']
						)
					);
				}
				
			}
		}
		
		$limit = null;
		$count = null;
		if (isset($filter['limit'])) {
			$limit = $filter['limit'];
			unset($filter['limit']);
		}
		if (isset($filter['count'])) {
			$count = $filter['count'];
			unset($filter['count']);
		}
		if (isset($filter['access_level']) && $filter['access_level'] != 'null') {
			foreach ($types as $object) {
				$filter_o[$object][$object . '_access_level'] = $filter['access_level'];
			}
		}
		if (isset($filter['type'])) {
			foreach ($types as $object) {
				if ($object == "resource") {
					if ($filter['type'] != "all") {
						if (!is_array($filter['type'])) {
							$filter['type'] = array(
								$filter['type']
							);
						}
						$filter_o[$object][] = // array(
							array(
								'`resource_type` IN %in',
								$filter['type']
//							)
						);
					}
				}
			}
		}
		$filter_tag   = array();
		$tag_extended = array();
		if (isset($filter['tags'])) {
			$all = false;
			foreach ($filter['tags'] as $tag_id => $tag_status) {
				if ($tag_id == "all" && $tag_status == 1) {
					$all = true;
					break;
				}
				if ($tag_status == 0) {
					unset($filter['tags'][$tag_id]);
				} else {
					$parents = Tag::getParentTree($tag_id);
					foreach ($parents as $row => $tag_parent_id) {
						$tag_extended[$tag_parent_id] = 1;
					}
				}
			}
			foreach ($tag_extended as $tid => $tst) {
				$filter['tags'][$tid] = 1;
			}

			foreach($types as $object) {
				if ($all) {
					$filter_tag[$object] = array();
				} else if (count($filter['tags']) > 0) {
				
					$tgs = array();
					foreach ($filter['tags'] as $id => $value) {
						if ($id == 'all') {
							break;
						}
						$tgs[] = array(
							"`" . $object . "_tag`." . '`tag_id` = %i',
							$id
						);
					}
					$filter_tag[$object][] = array(
						'%or',
						$tgs
					);
				}
			}
		}
		
		if (isset($filter['mapfilter']) && $filter['mapfilter'] != 'null' && is_array($filter['mapfilter']['center']) && is_array($filter['mapfilter']['radius'])) {
			$R         = 6371; // km
			$latitude  = $filter['mapfilter']['center']['lat'];
			$longitude = $filter['mapfilter']['center']['lng'];
			$length    = ($filter['mapfilter']['radius']['length'] / 1000);
			foreach ($types as $object) {	
				$filter_o[$object][] = array(
					'(' . $object . '_position_x <> 0 AND ' . $object . '_position_y <> 0) AND (ACOS(SIN(RADIANS(%f))*SIN(RADIANS(`' . $object . '_position_x`))+COS(RADIANS(%f))*COS(RADIANS(`' . $object . '_position_x`))*COS(RADIANS(`' . $object . '_position_y`) - RADIANS(%f))) * %i) <= %f',
					$latitude,
					$latitude,
					$longitude,
					$R,
					$length
				);

			}
		}

/*		
		if (isset($filter['owner_not']) && $filter['owner_not'] != 'null') {
			foreach ($types as $object) {
				$filter_o[$object][] = array(
					'`'.$object.'.'.$object . '_id` != %i', $filter['owner_not']
				);
			}
		}
*/

		if (isset($filter['status']) && $filter['status'] != 'null') {
			foreach ($types as $object) {
				$filter_o[$object][$object . '_status'] = $filter['status'];
			}
		}

		if (isset($filter['trash']) && $filter['trash'] != 'null') {
			foreach ($types as $object) {
				if ($object == "resource") {
					if (!empty($user)) {
						if ($filter['trash'] < 2) {
							$filter_o[$object]['opened.resource_trash'] = $filter['trash'];
						} else {
							$filter_o[$object]['opened.resource_opened_by_user'] = '0';
						}
					}
				}
			}
		}

    	if(isset($filter['opened']) && $filter['opened'] != 'null') {
        	foreach($types as $object) {
				if($object == "resource") {
					if(!empty($user)) {
            			$filter_o[$object]['opened.resource_opened_by_user'] = $filter['opened'];
					}
				}
         	}
		}

		if (isset($filter['name']) && $filter['name'] != "") {
			foreach ($types as $object) {
				if ($object == "user") {
					$filter_o[$object][] = array(
						'%or',
						array(
							array(
								'`user_login` LIKE %~like~',
								$filter['name']
							),
							array(
								'`user_name` LIKE %~like~',
								$filter['name']
							),
							array(
								'`user_surname` LIKE %~like~',
								$filter['name']
							)
						)
					);
				}
				if ($object == "group") {
					$filter_o[$object][] = array(
						array(
							'`group_name` LIKE %~like~',
							$filter['name']
						)
					);
				}
				if ($object == "resource") {
					$filter_o[$object][] = array(
						array(
							'`resource_name` LIKE %~like~',
							$filter['name']
						)
					);
				}
			}
		}

		if (isset($filter['owner']) && $filter['owner']!=NULL) {
			foreach ($types as $object) {
				if ($object == "group") {
					$filter_o[$object][] = array(
						'`group_author` IN %in',
						$filter['owner']

					);
				}
				if ($object == "resource") {
					$filter_o[$object][] = array(
						'`resource_author` IN %in',
						$filter['owner']

					);
				}
			}
		}

		$filter_pairing = "(%and)";
		$pairing_operator = 'AND';
		if (isset($filter['filter_pairing'])) {
			if ($filter['filter_pairing'] == 'or') {
				$filter_pairing = "(%or)";
				$pairing_operator = 'OR';
			} else {
				$filter_pairing = "(%and)";
				$pairing_operator = 'AND';
			}
		}
		
		$language = "'".implode("','",Language::getIds())."'";
		if (isset($filter['language']) && $filter['language'] != "") {
			$language = "";
			if ($filter['language'] == 0) {
				$lng = Language::getArray();
				foreach ($lng as $lng_id => $lng_value) {
					if ($language != "") {
						$language .= ",";
					}
					$language .= "'" . $lng_id . "'";
				}
			} else {
				$language = "'" . $filter['language'] . "'";
			}
		}
		
		//--------------------------
		$logged_user              = NEnvironment::getUser()->getIdentity();
		$logged_user_id           = 0;
		$logged_user_groups_array = array();
		if (!empty($logged_user)) {
			$logged_user_groups_array = $logged_user->getGroups();
			
			$logged_user_id = $logged_user->getUserId();
			$own_access_level   = $logged_user->getAccessLevel();

			// $access_level: visibility of item 1:world, 2:registered, 3:connections
			// $status_level: enabled or disabled
			if ($own_access_level > 1) {
				$access_level = "'1','2','3'";
				$status_level = "'0','1'";
			} else {
				$access_level = "'1','2'";
				$status_level = "'1'";
			}
		} else {
			$access_level = "'1'";
			$status_level = "'1'";
		}
		
		// for group chat
		If (isset($filter['only_active'])) $status_level = "'1'";
		
		//--------------------------
		foreach ($types as $object) {
			if ($sql != "") {
				$sql .= " UNION ALL ";
			}
			if ($object == "user") {
				$sql .= "(" . $sql_user . " WHERE " . $filter_pairing . " AND `user_language` IN (" . $language . ") AND ((`user_visibility_level` IN (" . $access_level . ")) OR (`user_visibility_level` = '3' AND EXISTS (SELECT `friend_id` FROM `user_friend` a WHERE a.`user_id` = `user_id` AND a.`friend_id` = '" . $logged_user_id . "')))";
				if (isset($filter['user_id'])) {
					$sql .= " ".$pairing_operator." EXISTS (SELECT f.`user_id` FROM `user_friend` f WHERE f.`user_id` = `user_id` AND f.`friend_id` = '" . $logged_user_id . "' AND f.`user_friend_status` = '2' )";
				}
				$sql .= " AND `user`.`user_status` IN (" . $status_level . ")";
				if (isset($filter['group_id'])) {
					$sql .= " AND (`group_user`.`group_user_status` = '1' OR (`group_user`.`group_user_status` IN('0','2') AND EXISTS (SELECT `user_id` FROM `group_user` e WHERE e.`group_id` = '" . $filter['group_id'] . "' AND e.`user_id` = '" . $logged_user_id . "' AND e.`group_user_access_level` > 1)))";
				}
				if (isset($filter['resource_id'])) {
					$logged_user_groups = "";
					foreach ($logged_user_groups_array as $lugroup_id) {
						if ($logged_user_groups != "") {
							$logged_user_groups .= " OR ";
						}
						
						$logged_user_groups .= "e.`member_id` = '" . $lugroup_id['group_id'] . "'";
						
					}
					if ($logged_user_groups == "") {
						$logged_user_groups = "1=2";
					}
					
					$sql .= " AND (`resource_user_group`.`resource_user_group_status` = '1' OR (`resource_user_group`.`resource_user_group_status` IN('0','2') AND EXISTS (SELECT `member_id` FROM `resource_user_group` e WHERE e.`resource_id` = '" . $filter['resource_id'] . "' AND e.`resource_user_group_access_level` > 1 AND ((e.`member_type` = '1' AND e.`member_id` = '" . $logged_user_id . "') OR (e.`member_type` = '2' AND (" . $logged_user_groups . "))))))";
				}
				
				if (!empty($filter_tag['user']) && count($filter_tag['user']) > 0) {
					$sql .= " ".$pairing_operator." EXISTS (SELECT `user_tag`.`user_id` FROM `user_tag` WHERE `user_tag`.`user_id` = `user`.`user_id` AND (%or))";
				}
				if ($counter_mode) {
					$sql .= " )";
				} else {
					$sql .= " GROUP BY `id` ORDER BY links DESC)";
				}	
			}
			
			if ($object == "group") {
				$sql .= "(" . $sql_group . " WHERE " . $filter_pairing . " AND `group_language` IN (" . $language . ") AND ((`group_visibility_level` IN (" . $access_level . ")) OR (`group_visibility_level` = '3' AND EXISTS (SELECT `user_id` FROM `group_user` a WHERE a.`group_id` = `group_id` AND a.`user_id` = '" . $logged_user_id . "')))";

				if (!empty($filter_tag['group']) && count($filter_tag['group']) > 0) {
					$sql .= " ".$pairing_operator." EXISTS (SELECT `group_tag`.`group_id` FROM `group_tag` WHERE `group_tag`.`group_id` = `group`.`group_id` AND (%or))";
				}
				$sql .= " AND `group`.`group_status` IN (" . $status_level . ")";
				if ($counter_mode) {
					$sql .= " )";
				} else {
					$sql .= " GROUP BY `id` ORDER BY links DESC)";
				}	
			}
			
			if ($object == "resource") {
				if (!empty($user)) {
					$logged_user_groups = "";
					foreach ($logged_user_groups_array as $lugroup_id) {
						if ($logged_user_groups != "") {
							$logged_user_groups .= " OR ";
						}
						
						$logged_user_groups .= "a.`member_id` = '" . $lugroup_id['group_id'] . "'";
						
					}
					if ($logged_user_groups == "") {
						$logged_user_groups = "1=2";
					}
					
					$sql .= "(" . $sql_resource . " WHERE " . $filter_pairing . " AND `resource_language` IN (" . $language . ") AND ((`resource_visibility_level` IN (" . $access_level . ")) OR (`resource_visibility_level` = '3' AND EXISTS (SELECT `member_id` FROM `resource_user_group` a WHERE a.`resource_id` = `resource_id` AND ((a.`member_type` = '1' AND a.`member_id` = '" . $logged_user_id . "') OR (a.`member_type` = '2' AND (" . $logged_user_groups . "))))))";
					if (!empty($filter_tag['resource']) && count($filter_tag['resource']) > 0) {
						$sql .= " ".$pairing_operator." EXISTS (SELECT `resource_tag`.`resource_id` FROM `resource_tag` WHERE `resource_tag`.`resource_id` = `resource`.`resource_id` AND (%or))";
					}
					$sql .= " AND `resource`.`resource_status` IN (" . $status_level . ")";
					if ($counter_mode) {
						$sql .= " )";
					} else {
						if (in_array(1,$filter['type']) || in_array(9,$filter['type'])) {
							$sql .= " GROUP BY `id` ORDER BY `resource`.`resource_creation_date` DESC)";
						} else {
							$sql .= " GROUP BY `id` ORDER BY links DESC, `resource`.`resource_creation_date` DESC,opened.`resource_opened_by_user` ASC)";
						}
					}
				} else {
					$logged_user_groups = "";
					foreach ($logged_user_groups_array as $lugroup_id) {
						if ($logged_user_groups != "") {
							$logged_user_groups .= " OR ";
						}
						
						$logged_user_groups .= "a.`member_id` = '" . $lugroup_id['group_id'] . "'";
						
					}
					if ($logged_user_groups == "") {
						$logged_user_groups = "1=2";
					}
					$sql .= "(" . $sql_resource . " WHERE " . $filter_pairing . " AND `resource_language` IN (" . $language . ") AND ((`resource_visibility_level` IN (" . $access_level . ")) OR (`resource_visibility_level` = '3' AND EXISTS (SELECT `member_id` FROM `resource_user_group` a WHERE a.`resource_id` = `resource_id` AND ((a.`member_type` = '1' AND a.`member_id` = '" . $logged_user_id . "') OR (a.`member_type` = '2' AND (" . $logged_user_groups . "))))))";
					if (isset($filter_tag['resource']) && count($filter_tag['resource']) > 0) {
						$sql .= " ".$pairing_operator." EXISTS (SELECT `resource_tag`.`resource_id` FROM `resource_tag` WHERE `resource_tag`.`resource_id` = `resource`.`resource_id` AND (%or))";
					}
					$sql .= " AND `resource`.`resource_status` IN (" . $status_level . ")";
					
					if ($counter_mode) {
						$sql .= " )";
					} else {
						$sql .= " GROUP BY `id` ORDER BY links DESC,`resource`.`resource_creation_date` DESC )";
					}
					
				}
			}
		}
		

		if (!is_null($limit) && !is_null($count)) {
			$sql .= " LIMIT {$limit},{$count}";
		}
			

		$params[] = $sql;
		foreach ($filter_o as $p) {
			$params[] = $p;
		}
		foreach ($filter_tag as $t) {
			$params[] = $t;
		}

		$result = call_user_func_array(array(
			'dibi',
			"fetchAll"
		), $params);
		
		$return         = array();
		$counter_result = 0;
		foreach ($result as $row) {
			$data = $row->toArray();
			if ($counter_mode) {
				$counter_result += $data['count'];
				continue;
			}
			
			// get subscribed resources
			if ($data['type_name'] == "user") {
				$resources = array();
				$result_2  = dibi::fetchAll("SELECT `resource_id` FROM `resource_user_group` WHERE `member_id` = %i AND `member_type` = 1", $data['id']);
		
				$resources = array();
				foreach ($result_2 as $r2_row) {
					$res         = $r2_row->toArray();
					$resources[] = $res['resource_id'];
				}
				$data['registered_resources'] = $resources;
			}
			
			if($data['type_name'] == "resource") {
				if(isset($data['resource_data'])) {
					$data['resource_data'] = json_decode($data['resource_data'],true);
				}
			}

			if ($data['type_name'] == "group") {
				$resources    = array();
				$group_object = Group::create($data['id']);
				$user         = NEnvironment::getUser()->getIdentity();
				if (!empty($user)) {
					if ($group_object->userIsRegistered($user->getUserId())) {
						$data['logged_user_member'] = 1;
					} else {
						$data['logged_user_member'] = 0;
					}
				} else {
					$data['logged_user_member'] = -1;
				}
				$result_2  = dibi::fetchAll("SELECT `resource_id` FROM `resource_user_group` WHERE `member_id` = %i AND `member_type` = 2", $data['id']);
				
				$resources = array();
				foreach ($result_2 as $r2_row) {
					$res         = $r2_row->toArray();
					$resources[] = $res['resource_id'];
				}
				$data['registered_resources'] = $resources;
				
			}
			$return[] = $data;
		}
		if ($counter_mode) {
			return $counter_result;
		} else {
			return $return;
		}
		
	}
}
