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
 

class Activity extends BaseModel {
	const USER_JOINED = 1;
	const FRIENDSHIP_YES = 2;
	const FRIENDSHIP_NO = 3;
	const GROUP_JOINED = 4;
	const RESOURCE_SUBSCRIBED = 5;
	const GROUP_RESOURCE_ADDED = 6;
	const GROUP_CHAT = 7;
	const RESOURCE_COMMENT = 8;
	const GROUP_CREATED = 9;
	const RESOURCE_CREATED = 10;
	const USER_UPDATED = 11;
	const GROUP_RESOURCE_REMOVED = 12;
	const GROUP_UPDATED = 13;
	const RESOURCE_UPDATED = 14;
	const FRIENDSHIP_END = 15;
	const GROUP_LEFT = 16;
	const RESOURCE_UNSUBSCRIBED = 17;
	const LOGIN_FAILED = 18;
	const FRIENDSHIP_REQUEST = 19;
	

	/**
	*	adds an activity to the database
	*	
	*	If $affected_user_id is NULL then this activity will be listed for all users that have view permissions for that object.
	*/
	public static function addActivity($activity, $object_id, $object_type, $affected_user_id = null) {
  
  		$data = array(
  					'activity' => $activity,
  					'object_id' => $object_id,
  					'object_type' => $object_type,
  					'affected_user_id' => $affected_user_id,
  					'timestamp' => time()
  			);
  		
  		return dibi::query('INSERT INTO `activity`', $data);
  	}


	/**
	*	reads activities from the database for which the user has permissions to view
	*
	*	@params	
	*	$user_id is the user for whome the list is being compiled
	*	$min_timestamp, $max_timestamp specify the time frame
	*
	*	note: Only groups and resources listed because friends' activities should be considered private.
	*/
  	public static function getActivities($user_id, $min_timestamp = 0, $max_timestamp = null) {
  		
  		if ($max_timestamp == null) $max_timestamp = time();
  		
  		// get all connections of this user
  		$filter = array('user_id'  => $user_id, 'type' => array(2,3,4,5,6));
  		$groups = Administration::getData(array(ListerControlMain::LISTER_TYPE_GROUP), $filter);
  		$resources = Administration::getData(array(ListerControlMain::LISTER_TYPE_RESOURCE), $filter);

  		// extract IDs
  		$connections = array();
  		$data = array();
  		


  		if (count($groups)) {
	  		foreach ($groups as $item) {
  				$connections[2][] = $item['id'];
  			}
  		} else {
  			$connections[2][] = 0;
  		}
  		
  		if (count($resources)) {
	  		foreach ($resources as $item) {
  				$connections[3][] = $item['id'];
  			}
  		} else {
  			$connections[3][] = 0;
  		}

  		// retrieve relevant items from database
  		$result = dibi::fetchAll('SELECT * FROM `activity` WHERE
  		(
			(`affected_user_id` = %i)
			OR
			(`object_type` = 1
				AND
				(
					`object_id` = %i
					OR
					`affected_user_id` = %i
				)
			)
			OR
			(`object_type` = 2 AND `object_id` IN %in AND `affected_user_id` IS NULL)
			OR
			(`object_type` = 3 AND `object_id` IN %in AND `affected_user_id` IS NULL)
  		)
  		AND `timestamp` > %i AND `timestamp` < %i	
  		ORDER BY `timestamp` DESC', $user_id, $user_id, $user_id, $connections[2], $connections[3], $min_timestamp, $max_timestamp);

  		
  		foreach ($result as $row) {
			$data[] = $row->toArray();
		}
		
		// remove multiple notifications about chat messages and failed logins (starting from latest)
		if (isset($data) && is_array($data)) {
			foreach ($data as $row) {
				if ($row['activity'] == Activity::GROUP_CHAT || $row['activity'] == Activity::RESOURCE_COMMENT || $row['activity'] == Activity::LOGIN_FAILED || $row['activity'] == Activity::USER_UPDATED || $row['activity'] == Activity::GROUP_UPDATED || $row['activity'] == Activity::RESOURCE_UPDATED) {
					foreach ($data as $key=>$row2) {
						if ($row2['activity_id'] < $row['activity_id'] && $row2['activity'] == $row['activity'] && $row2['object_id'] == $row['object_id'] && strtotime("midnight",$row2['timestamp']) == strtotime("midnight",$row['timestamp'])) {
							unset($data[$key]);
						}
					}
				}
			}
		}

		// remove notifications about items that date before making connection (starting from latest)
		if (isset($data) && is_array($data)) {
			foreach ($data as $row) {
				if ($row['activity'] == Activity::FRIENDSHIP_YES || $row['activity'] == Activity::GROUP_JOINED || $row['activity'] == Activity::RESOURCE_SUBSCRIBED) {
					foreach ($data as $key=>$row2) {
						if ($row2['activity_id'] != $row['activity_id'] &&  $row2['activity'] != Activity::FRIENDSHIP_END && $row2['activity'] != Activity::GROUP_LEFT &&   $row2['activity'] != Activity::RESOURCE_UNSUBSCRIBED && $row['activity'] != Activity::FRIENDSHIP_YES && $row['activity'] != Activity::GROUP_JOINED && $row['activity'] != Activity::RESOURCE_SUBSCRIBED  && $row2['object_id'] == $row['object_id'] && $row2['timestamp']<$row['timestamp']) {
							unset($data[$key]);
						}
					}
				}
			}
		}
		
  		return $data;
  	}

}