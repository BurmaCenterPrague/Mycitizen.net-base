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
	const USER_PW_CHANGE = 20;
	const GROUP_PERMISSION_CHANGE = 21;
	const RESOURCE_PERMISSION_CHANGE = 22;
	const NOTICEBOARD_MESSAGE = 23;
	const FRIEND_INVITED = 24;


	/**
	 *	Add an activity to the database.
	 *	@param const $activity
	 *	@param int $object_id
	 *	@param int $object_type
	 *	@param int $affected_user_id If $affected_user_id is NULL then this activity will be listed for all users that have view permissions for that object.
	 *	@return boolean
	 */
	public static function addActivity($activity, $object_id, $object_type, $affected_user_id = null)
	{
  		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		if ($object_type == 1) {
			$cache->clean(array(NCache::TAGS => array("user_id/$object_id", "name/activity")));
		} elseif ($object_type == 2) {
			$cache->clean(array(NCache::TAGS => array("group_id/$object_id", "name/activity")));
		} elseif ($object_type == 3) {
			$cache->clean(array(NCache::TAGS => array("resource_id/$object_id", "name/activity")));
		}
		if ($affected_user_id != null) {
			$cache->clean(array(NCache::TAGS => array("user_id/$affected_user_id", "name/activity")));
		}
							
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
	 *	Remove an activity from the database
	 *	@param const $activity
	 *	@param int $object_id
	 *	@param int $object_type
	 *	@return boolean
	 */
	public static function removeActivity($activity, $object_id, $object_type) {
  		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		if ($object_type == 1) {
			$cache->clean(array(NCache::TAGS => array("user_id/$object_id", "name/activity")));
		} elseif ($object_type == 2) {
			$cache->clean(array(NCache::TAGS => array("group_id/$object_id", "name/activity")));
		} elseif ($object_type == 3) {
			$cache->clean(array(NCache::TAGS => array("resource_id/$object_id", "name/activity")));
		}
  		return dibi::query('DELETE from `activity` WHERE `activity` = %i AND `object_id` = %i AND `object_type` = %i', $activity, $object_id, $object_type);
  	}


	/**
	 *	Read activities from the database for which the user has permissions to view.
	 *	note: Only groups and resources listed because friends' activities should be considered private.
	 *	@param int $user_id - the user for whome the list is being compiled
	 *	@param int $min_timestamp
	 *	@param int $max_timestamp specify the time frame
	 *	@param int $include_all_latest
	 *	@return array
	 */
  	public static function getActivities($user_id, $min_timestamp = 0, $max_timestamp = null, $include_all_latest = 0)
  	{
  		if ($max_timestamp == null) $max_timestamp = time();
  		
  		// get all connections of this user
  		$filter = array('user_id'  => $user_id, 'type' => array(2,3,4,5,6));
  		$groups = Administration::getData(array(ListerControlMain::LISTER_TYPE_GROUP), $filter);
  		$resources = Administration::getData(array(ListerControlMain::LISTER_TYPE_RESOURCE), $filter);

  		// extract IDs
  		$connections = array();
  		$data = array();

  		if (!empty($groups)) {
	  		foreach ($groups as $item) {
  				$connections[2][] = $item['id'];
  			}
  		} else {
  			$connections[2][] = 0;
  		}
  		
  		if (!empty($resources)) {
	  		foreach ($resources as $item) {
  				$connections[3][] = $item['id'];
  			}
  		} else {
  			$connections[3][] = 0;
  		}

  		// retrieve relevant items from database
  		if ($include_all_latest) {
			$result = dibi::fetchAll('SELECT `activity_id`, `timestamp`, `activity`, `object_type`, `object_id`, `affected_user_id`
			FROM `activity` WHERE
			`timestamp` > %i AND `timestamp` < %i AND
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
				OR
				(`object_type` = 1 AND `activity` = %i)
				OR
				(`object_type` = 2 AND `activity` = %i)
				OR
				(`object_type` = 3 AND `activity` = %i)
				OR
				(`activity` = %i)
			)	
			ORDER BY `timestamp` DESC', $min_timestamp, $max_timestamp, $user_id, $user_id, $user_id, $connections[2], $connections[3], Activity::USER_JOINED, Activity::GROUP_CREATED, Activity::RESOURCE_CREATED, Activity::NOTICEBOARD_MESSAGE);
  		} else {
			$result = dibi::fetchAll('SELECT `activity_id`, `timestamp`, `activity`, `object_type`, `object_id`, `affected_user_id`
			FROM `activity` WHERE
			`timestamp` > %i AND `timestamp` < %i AND
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
				OR
				(`activity` = %i)
			)
			ORDER BY `timestamp` DESC', $min_timestamp, $max_timestamp, $user_id, $user_id, $user_id, $connections[2], $connections[3], Activity::NOTICEBOARD_MESSAGE);
		}
  		
  		foreach ($result as $row) {
			$data[] = $row->toArray();
		}
		

		// We need to remove certain items to make the list more useful:
		$unduplicate_activities = array(Activity::GROUP_CHAT, Activity::RESOURCE_COMMENT, Activity::LOGIN_FAILED, Activity::USER_UPDATED, Activity::GROUP_UPDATED, Activity::RESOURCE_UPDATED, Activity::GROUP_RESOURCE_ADDED, Activity::GROUP_RESOURCE_REMOVED, Activity::FRIENDSHIP_REQUEST, Activity::FRIENDSHIP_YES, Activity::FRIENDSHIP_NO, Activity::FRIENDSHIP_END);
		$first_viewing_events = array(Activity::FRIENDSHIP_YES, Activity::GROUP_JOINED, Activity::RESOURCE_SUBSCRIBED);

		if (isset($data) && is_array($data)) {
			foreach ($data as $row) {
				// remove notifications about items that date before making connection (starting from latest)
				if (in_array($row['activity'], $first_viewing_events)) {
					foreach ($data as $key=>$row2) {
						if ($row2['timestamp'] < $row['timestamp'] && $row2['activity_id'] != $row['activity_id'] && $row2['activity'] != Activity::FRIENDSHIP_END && $row2['activity'] != Activity::GROUP_LEFT && $row2['activity'] != Activity::RESOURCE_UNSUBSCRIBED && $row['activity'] != Activity::FRIENDSHIP_YES && $row['activity'] != Activity::GROUP_JOINED && $row['activity'] != Activity::RESOURCE_SUBSCRIBED && $row2['object_id'] == $row['object_id']) {
							unset($data[$key]);
						}
					}
				}

				// remove multiple identical notifications from same day (keeping the latest)
				if (in_array($row['activity'], $unduplicate_activities)) {
					foreach ($data as $key=>$row2) {
						if ($row2['activity_id'] < $row['activity_id'] && $row2['activity'] == $row['activity'] && $row2['object_id'] == $row['object_id'] && strtotime("midnight",$row2['timestamp']) == strtotime("midnight",$row['timestamp'])) {
							unset($data[$key]);
						}
					}
				}
			}
		}

  		if ($include_all_latest) {
  			// remove notifications about items that user is not allowed to view
  			$user = User::create($user_id);
			if ($user->getAccessLevel() < 2) {
	  			// moderators and administrators have no restrictions regarding visibility
				if (isset($data) && is_array($data)) {
					foreach ($data as $key=>$row) {
						if ($row['activity'] == Activity::NOTICEBOARD_MESSAGE) continue;
						switch ($row['object_type']) {
							case 1:
								if ($row['object_id'] == $user_id || $row['affected_user_id'] == $user_id) continue 2;
								$object = User::create($row['object_id']);
							break;
							case 2:
								if (in_array($row['object_id'], $connections[2])) continue 2;
								$object = Group::create($row['object_id']);
							break;
							case 3:
								if (in_array($row['object_id'], $connections[3])) continue 2;
								$object = Resource::create($row['object_id']);
							break;
						}
						if (empty($object) || !$object->isActive() || Auth::isAuthorized($row['object_type'],$row['object_id']) == Auth::UNAUTHORIZED) {
							unset($data[$key]);
						}
					}			
				}
			}
  		}
		
  		return $data;
  	}


	/**
	 *	Render the list of activities into string (HTML-formatted).
	 *	@param array $activities The activities in the shape of an array.
	 *	@param int $user_id The recipient for whome the list is made.
	 *	@param boolean $email Whether to use formatting for email output. (<table> instead of <div>)
	 *	@return string
	 */
	public static function renderList($activities, $user_id, $email=false)
	{
		if ($email) {
			$output = '<table style="width:100%;">';
		} else {
			$output = '';
		}
		
		if (isset($activities) && count($activities)) {
			foreach ($activities as $activity) {
				if ($activity['activity'] != Activity::NOTICEBOARD_MESSAGE) {
					switch ($activity['object_type']) {
						case 1:
							if (!empty($activity['affected_user_id']) && $activity['object_id'] == $user_id) $object_id = $activity['affected_user_id']; else $object_id = $activity['object_id'];
							$object_link = NEnvironment::getVariable("URI").'/user/?user_id='.$object_id;
							$object_icon = User::getImage($object_id, 'icon');
							$object_name = User::getUserLogin($object_id);
							$time = self::relativeTime($activity['timestamp']);
						break;
						case 2:
							$object_link = NEnvironment::getVariable("URI").'/group/?group_id='.$activity['object_id'];
							$object_icon = Group::getImage($activity['object_id'], 'icon');
							$object_name = Group::getName($activity['object_id']);
							$time = self::relativeTime($activity['timestamp']);
						break;
						case 3:
							$object_link = NEnvironment::getVariable("URI").'/resource/?resource_id='.$activity['object_id'];
							$object_icon = '<b class="'.Resource::getIconClass($activity['object_id']).'"></b>';
							$object_name = Resource::getName($activity['object_id']);;
							$time = self::relativeTime($activity['timestamp']);
						break;
					}
			
					switch ($activity['activity']) {
						case Activity::USER_JOINED:
							if ($activity['object_id'] == $user_id) {
								$description = _t('You signed up.');
							} else {
								$description = _t('A new user has signed up.');
							}
						break;
						case Activity::FRIENDSHIP_REQUEST:
							if ($activity['object_id'] != $user_id) {
								$description = sprintf(_t('User %s requested your friendship.'),$object_name);
							} else {
								$description = sprintf(_t("You requested %s's friendship."),$object_name);
							}
						break;
						case Activity::FRIENDSHIP_YES:
							if ($activity['object_id'] != $user_id) {
								$description = sprintf(_t('User %s accepted your friendship.'),$object_name);
							} else {
								$description = sprintf(_t("You accepted %s's friendship."),$object_name);
							}
						break;
						case Activity::FRIENDSHIP_NO:
							if ($activity['object_id'] != $user_id) {
								$description = sprintf(_t('User %s rejected your friendship.'),$object_name);
							} else {
								$description = sprintf(_t("You rejected %s's friendship."),$object_name);
							}
						break;
						case Activity::FRIENDSHIP_END:
							if ($activity['object_id'] != $user_id) {
								$description = sprintf(_t('User %s canceled your friendship.'),$object_name);
							} else {
								$description = sprintf(_t('You canceled the friendship with %s.'),$object_name);
							}
						break;

						case Activity::GROUP_JOINED: $description = _t('You joined the group.'); break;
						case Activity::RESOURCE_SUBSCRIBED: $description = _t('You subscribed to the resource.'); break;
						case Activity::GROUP_LEFT: $description = _t('You left the group.'); break;
						case Activity::RESOURCE_UNSUBSCRIBED: $description = _t('You unsubscribed from the resource.'); break;
						case Activity::GROUP_RESOURCE_ADDED: $description = _t('The group added a resource.'); break;
						case Activity::GROUP_CHAT: $description = _t('A new chat message was posted in the group.'); break;
						case Activity::RESOURCE_COMMENT: $description = _t('The resource has new comments.'); break;
						case Activity::GROUP_CREATED: $description = _t('A new group was created.'); break;
						case Activity::RESOURCE_CREATED: $description = _t('A new resource was created.'); break;
						case Activity::USER_UPDATED: $description = _t('Your profile was updated.'); break;
						case Activity::GROUP_RESOURCE_REMOVED: $description = _t('The group unsubscribed from a resource.'); break;
						case Activity::GROUP_UPDATED: $description = _t('The group was updated.'); break;
						case Activity::RESOURCE_UPDATED: $description = _t('The resource was updated.'); break;
						case Activity::LOGIN_FAILED: $description = _t('Somebody tried to login with your name and a wrong password.'); break;
						case Activity::USER_PW_CHANGE: $description = _t('Your password was changed.'); break;
						case Activity::GROUP_PERMISSION_CHANGE: $description = _t('Your permissions of the group were changed.'); break;
						case Activity::RESOURCE_PERMISSION_CHANGE: $description = _t('Your permissions of the resource were changed.'); break;
						case Activity::FRIEND_INVITED:
							if ($activity['object_id'] != $user_id) {
								$description = sprintf(_t('%s invited you to a group.'),$object_name);
							} else {
								$description = sprintf(_t('You invited %s to a group.'),$object_name);
							}
						break;
						
						default: $description = 'Unspecified activity'; break;
					}
				}
				if ($activity['activity'] == Activity::NOTICEBOARD_MESSAGE) {
					$message_o = Resource::create($activity['object_id']);
					if (!empty($message_o) && $message_o->isActive()) {
						$data = $message_o->getResourceData();
						$config = HTMLPurifier_Config::createDefault();
						$config->set('Attr.EnableID', true);
						$config->set('Attr.IDBlacklistRegexp', '/^(?!((quoting_\d+)|(reply\d+))).*/'); // blacklisting all id attributes that don't start with "quoting_" followed by a number
						$config->set('HTML.Nofollow', true);
						$config->set('HTML.Allowed', 'h2,h3,h4,a[href|target|rel],strong,b,div,br,img[src|alt|height|width|style],dir,span[style],blockquote[id],ol,ul,li[type],pre,u,hr,code,strike,sub,sup,p[style],table,tr,td[colspan],th,iframe[src|width|height|frameborder]');
						$config->set('Attr.AllowedFrameTargets', array('_blank', '_top'));
						$config->set('HTML.SafeIframe', true);
						$config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www.youtube.com/embed/.*)|(player.vimeo.com/video/)%'); //allow YouTube and Vimeo
						$config->set('Filter.YouTube', true);
						$purifier = new HTMLPurifier($config);
						$message_text = StaticModel::purify_and_convert($data['message_text']);
						$time = self::relativeTime($activity['timestamp']);
						if ($email) {
							$output .= sprintf('<tr><td style="width:100px; padding:5px 20px; margin:5px 0; min-height:30px; background-color:#eae9e3; border-radius:10px 0 0 0">%s</td><td colspan="2" style="padding:5px 20px; margin:5px 0; min-height:30px; background-color:#eae9e3; border-radius:0 10px 0 0;">'._t("Message to all users").'</td></tr>
							<tr><td colspan="3" style="padding:5px 20px; margin:5px 0; min-height:30px; background-color:#eae9e3; border-radius:0 0 10px 10px;">%s</td></tr>', $time, $message_text)."\n\r";
						} else {
							$output .= sprintf('<div class="activity-item" style="cursor:default;"><div class="activity-time"><h4>%s</h4></div><div class="activity-description"><h4>'._t("Message to all users").'</h4></div><div style="clear:both;"></div><div class="activity-time"></div><div class="activity-description" style="max-width:80%%;">%s</div></div>', $time, $message_text);
						}
					}
				} else {
					if ($email) {
						$output .= sprintf('<tr><td style="width:100px; padding:5px 20px; margin:5px 0; min-height:30px;background-color:#eae9e3; border-radius:10px 0 0 10px;">%s</td><td style="width:180px; padding:5px 20px; margin:5px 0; min-height:30px; background-color:#eae9e3;"><a href="%s">%s</a>&nbsp;<a href="%s">%s</a></td><td style="padding:5px 20px; margin:5px 0; min-height:30px; border-radius:0 10px 10px 0;background-color:#eae9e3;">%s</td></tr>', $time, $object_link, $object_icon, $object_link, $object_name, $description)."\n\r";
					} else {
						$output .= sprintf('<div class="activity-item" onclick="window.location=\'%s\'"><div class="activity-time"><h4>%s</h4></div><div class="activity-link"><h4><a href="%s">%s %s</a></h4></div><div class="activity-description"><h4>%s</h4></div></div>', $object_link, $time, $object_link, $object_icon, $object_name, $description);
					}
				}
			}
		} else {
			if ($email) {
				$output .= '<tr><td colspan="3" style="padding:5px 20px; margin:5px 0; min-height:30px;"><span style="background-color:#eae9e3; border-radius:10px;"><h4>'._t("Nothing to display")."</h4></span></td></tr>\n\r";
			} else {
				$output .= '<div class="activity-item" style="cursor:default;"><h4>'._t("Nothing to display")."</h4></div>\n\r";
			}
		}

		if ($email) {
			$output .= '</table>';
		}
		return $output;
	}


	/**
	 *	Translate recent dates into "today" and "yesterday".
	 *	@param int $timestamp Unix timestamp
	 *	@return string
	 */
	public static function relativeTime($timestamp)
	{
	
		if (date('Ymd') == date('Ymd', $timestamp)) {
			return _t('Today');
		}

		if (date('Ymd', strtotime('yesterday')) == date('Ymd', $timestamp)) {
			return _t('Yesterday');
		}
		
		return date('j M Y', $timestamp);
	}

}