<?php
/**
 * mycitizen.net - Open source social networking for civil society
 *
 * @version 0.2.2 beta
 *
 * @author http://mycitizen.org
 * @copyright  Copyright (c) 2013 Burma Center Prague (http://www.burma-center.org)
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */
 


class Auth
{
	const UNAUTHORIZED = 0;
	const USER = 1;
	const VIP = 2;
	const ADMINISTRATOR = 3;
	const MODERATOR = 2;
	const TYPE_USER = 1;
	const TYPE_GROUP = 2;
	const TYPE_RESOURCE = 3;
	
	public static function getVisibilityLevelArray()
	{
		$result     = dibi::fetchAll("SELECT * FROM `visibility_level`");
		$languagess = array();
		foreach ($result as $row) {
			$data                                    = $row->toArray();
			$languages[$data['visibility_level_id']] = $data['visibility_level_name'];
		}
		return $languages;
		
	}
	public static function getAccessLevelArray()
	{
		$result     = dibi::fetchAll("SELECT * FROM `access_level`");
		$languagess = array();
		foreach ($result as $row) {
			$data                                = $row->toArray();
			$languages[$data['access_level_id']] = $data['access_level_name'];
		}
		return $languages;
		
	}
	
	/**
	*	Checks the authorization of the current user for a given item
	*	returns:
	*		0: not authorized
	*		1: authorized with normal user permissions
	*		2: authorized with moderator permissions
	*		3: authorized with admin permissions
	*/
	public static function isAuthorized($type, $object_id)
	{
		$logged_user = NEnvironment::getUser()->getIdentity();
		
		switch ($type) {
			case self::TYPE_USER:
				
				// object for user that needs to be accessed
				$user = User::create($object_id);

				// User does not exist -> deny
				if (empty($user)) {
					return 0;
				}

				// Guest accesses user with visibility "world": allow with normal authorization
				if (empty($logged_user) && $user->getVisibilityLevel() == 1)
					return 1;
					
				// Guest accesses user with visibility "registered" or "friends": deny
				if (empty($logged_user) && $user->getVisibilityLevel() >= 2) {
					return 0;
				}
				
				if (!empty($logged_user)) {
					// starting with higher return permissions

					// Admin accesses user: allow with admin authorization
					if ($logged_user->getAccessLevel() == 3) {
						return 3;
					}
					
					// Signed-in user can always access own profile with admin permissions.
					if ($object_id == $logged_user->getUserId() && $logged_user->isActive()) {
						return 3;
					}

					// Mod accesses user: allow with mod authorization
					if ($logged_user->getAccessLevel() == 2) {
						return 2;
					}
					
					// Signed-in user accesses user with visibility "registered": allow with user authorization
					if ($user->getVisibilityLevel() <= 2) {
						return 1;
					}

					// Users with visibility "friends" can be seen by friends
					if ($user->getVisibilityLevel() == 3) {
						if ($logged_user->friendsStatus($object_id) == 2) {
							return 1;
						}
					}

				}
				break;
				
			case self::TYPE_GROUP:
				$group = Group::create($object_id);
				if (empty($group)) {
					return 0;
				}
				if (empty($logged_user) && $group->getVisibilityLevel() == 1) {
					return 1;
				}
				if (!empty($logged_user)) {
				
					$top_access = 0;
					
					// User can view groups that are not closed.
					if ($group->getVisibilityLevel() <= 2) {
						$top_access = 1;
					}
					
					if ($logged_user->getAccessLevel() == 3 || $logged_user->getAccessLevel() == 2) {
						$top_access = 3;
					}
					
					$access = $group->getUserAccessLevel($logged_user->getUserId());

					// Moderator has same rights as admin.
					if ($access == 2) {
						$access = 3;
					}
					
					// User role at group can beat general user role.
					if ($top_access < $access) {
						$top_access = $access;
					}
					
					// Owner can always access own group as admin.
					if ($group->getOwner()->getUserId() == $logged_user->getUserId()) {
						$top_access = 3;
					}
					
					return $top_access;
					
				}
				break;
			
			case self::TYPE_RESOURCE:
				$resource = Resource::create($object_id);
				if (empty($resource)) {
					return 0;
				}
				if (empty($logged_user) && $resource->getVisibilityLevel() == 1) {
					return 1;
				}
				if (!empty($logged_user)) {
					
					$top_access = 0;
					
					// User can view resources that are not closed.
					if ($resource->getVisibilityLevel() <= 2) {
						$top_access = 1;
					}
					
					if ($logged_user->getAccessLevel() == 3 || $logged_user->getAccessLevel() == 2) {
						$top_access = 3;
					}
					
					// Mod has same permissions as admin.
					$access = $resource->getUserAccessLevel($logged_user->getUserId());
					if ($access == 2) {
						$access = 3;
					}
					
					// User role at resource can beat general user role.
					if ($top_access < $access) {
						$top_access = $access;
					}
					
					// Member of a group that has access has also access.
					foreach ($logged_user->getGroups() as $row) {
						$group_access = $resource->getGroupAccessLevel($row['group_id']);
						if ($group_access > $top_access) {
							$top_access = $group_access;
						}
					}

					// Owner can always access own resource as admin.
					if ($resource->getOwner()->getUserId() == $logged_user->getUserId()) {
						$top_access = 3;
					}

					return $top_access;
					
				}
				break;
			
			default:
				
				return 0;
		}
		return 0;
	}
}
