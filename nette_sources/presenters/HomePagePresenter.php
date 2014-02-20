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
 


final class HomepagePresenter extends BasePresenter
{

	/**
	 *	Some basics done by parent class.
	 *	@param void
	 *	@return void
	*/
	public function startup()
	{
		parent::startup();
	}

	/**
	 *	Some preparations before rendering default.phtml.
	 *	@param void
	 *	@return void
	 */
	public function actionDefault()
	{
		// detecting mobile devices
		require_once LIBS_DIR.'/Mobile-Detect/Mobile_Detect.php';
		$detect = new Mobile_Detect;
		if ($detect->isMobile()) $this->template->mobile = true;
		unset($detect);
		
		$this->template->tooltip_position = 'bottom center';
		$this->template->load_fullcalendar = true;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentFilter($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$options = array(
			'components' => array(
				'userHomepage',
				'groupHomepage',
				'resourceHomepage'
			),
			'refresh_path' => 'Homepage:default',
			'include_map' => true,
			'include_name' => true,
			'include_language' => true,
			'include_type' => true,
			'include_tags' => true,
			'include_pairing' => true
		);
		if (!empty($user)) {
			$options['include_suggest'] = true;
		}
		$control = new ExternalFilter($this, $name, $options);
		return $control;
	}


	/**
	*	Lists top users on home page for guests.
	*	@param $name string Namespace for lister
	*	@return object
	*/
	protected function createComponentUserHomepage($name)
	{
		$options = array(
			'itemsPerPage' => 10,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'show_extended_columns' => true,
				'front_page' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	*	Lists top groups on home page for guests.
	*	@param $name string Namespace for lister
	*	@return object
	*/	
	protected function createComponentGroupHomepage($name)
	{		
		$options = array(
			'itemsPerPage' => 10,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_GROUP
			),
			'template_body' => 'ListerControlMain_groups.phtml',
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'show_extended_columns' => true,
				'front_page' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	*	Lists top resources on home page for guests.
	*	@param $name string Namespace for lister
	*	@return object
	*/
	protected function createComponentResourceHomepage($name)
	{
		$options = array(
			'itemsPerPage' => 10,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'ListerControlMain_resources.phtml',
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'show_extended_columns' => true,
				'front_page' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	/**
	*	List friends on home page when logged in.
	*	@param $name string Namespace for lister
	*	@return object
	*/
	protected function createComponentHomepagefriendlister($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'filter' => array(
				'user_id' => $user->getUserId()
			),
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'front_page' => true,
				'your_connections' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	/**
	*	List groups where user is member on home page when logged in.
	*	@param $name string Namespace for lister
	*	@return object
	*/
	protected function createComponentHomepagegrouplister($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_GROUP
			),
			'template_body' => 'ListerControlMain_groups.phtml',
			'filter' => array(
				'user_id' => $user->getUserId()
			),
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'your_connections' => true,
				'front_page' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	/**
	*	List resources which user has subscribed to on home page when logged in.
	*	@param $name string Namespace for lister
	*	@return object
	*/
	protected function createComponentHomepageresourcelister($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'ListerControlMain_resources.phtml',
			'filter' => array(
				'user_id' => $user->getUserId()
			),
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'your_connections' => true,
				'front_page' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	*	List friends on home page when logged in.
	*	@param $name string Namespace for lister
	*	@return object
	*/
	protected function createComponentHomepagerecommendeduserlister($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		$filter = $this->suggestFilter();
		$number = Administration::getData(array(ListerControlMain::LISTER_TYPE_USER), $filter, true);
		if ($number < 5) {
			$filter = $this->suggestFilter(false);
			$map_used = false;
		} else {
			$map_used = true;
		}
		
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'filter' => $filter,
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'front_page' => true,
				'recommendations' => true,
				'map_used' => $map_used
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	/**
	*	List groups where user is member on home page when logged in.
	*	@param $name string Namespace for lister
	*	@return object
	*/
	protected function createComponentHomepagerecommendedgrouplister($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		$filter = $this->suggestFilter();
		$number = Administration::getData(array(ListerControlMain::LISTER_TYPE_GROUP), $filter, true);
		if ($number < 5) {
			$filter = $this->suggestFilter(false);
			$map_used = false;
		} else {
			$map_used = true;
		}
		
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_GROUP
			),
			'template_body' => 'ListerControlMain_groups.phtml',
			'filter' => $filter,
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'recommendations' => true,
				'front_page' => true,
				'map_used' => $map_used
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	/**
	*	List resources which user has subscribed to on home page when logged in.
	*	@param $name string Namespace for lister
	*	@return object
	*/
	protected function createComponentHomepagerecommendedresourcelister($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		$filter = $this->suggestFilter();
		$number = Administration::getData(array(ListerControlMain::LISTER_TYPE_RESOURCE), $filter, true);
		if ($number < 5) {
			$filter = $this->suggestFilter(false);
			$map_used = false;
		} else {
			$map_used = true;
		}
		
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'ListerControlMain_resources.phtml',
			'filter' => $filter,
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'recommendations' => true,
				'front_page' => true,
				'map_used' => $map_used
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	Sets the filter for retrieving the items according to user's own settings.
	 *
	 *	@param bool @restrict_around_own_location Include location on map if available
	 *	@return array filter values
	 */
	private function suggestFilter($restrict_around_own_location = true) {

		$user = NEnvironment::getUser()->getIdentity();
		$user_id = $user->getUserId();
		
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Filter.suggest");
		$cache->clean();		
		if ($cache->offsetExists($user_id)) {
			$filter = $cache->offsetGet($user_id);
			return $filter;
		}

		if (!empty($user)) {
			$ud = $user->getUserData();
			$t = $user->getTags();
			$defaults['tags']['all'] = 0;
			foreach ($t as $t_row) {
				if ($t_row->getTagId() != "") {
					$defaults['tags'][$t_row->getTagId()] = 1;
				}
			}
		} else {
			$defaults['tags']['all'] = 1;
		}
		
		if (!empty($user) && $user->hasPosition() && $restrict_around_own_location) {
			
			$position = $user->getPosition();

			$distance = NEnvironment::getVariable("MAP_SUGGEST_DST");
			$distance_lat = $distance/111.111;
			$r_x = $position['user_position_x'] + $distance_lat;
			$r_y = $position['user_position_y'] + $distance/(111.111*cos($distance_lat));
			$defaults['mapfilter']['type'] = 'circle';
			$defaults['mapfilter']['center'] = array(
				'lat' => $position['user_position_x'],
				'lng' => $position['user_position_y']
			);
			$defaults['mapfilter']['radius'] = array(
				'lat' => $r_x,
				'lng' => $r_y,
				'length' => $this->haversineGreatCircleDistance($position['user_position_x'], $position['user_position_y'],$r_x,$r_y)
				);			
		} else {
			$defaults['mapfilter']	= NULL;
		}

		$defaults['filter_pairing'] = "and";
		$defaults['name']           = "";
		$defaults['status']         = NULL;
		$defaults['trash']          = NULL;
		$defaults['language']       = $ud['user_language'];
		$defaults['type']           = "all";
		$defaults['exclude_connections_user_id']= $user_id;
		
		$cache->save($user_id, $defaults, array(NCache::EXPIRE => time()+120));
		return $defaults;
	}


	/**
	 * Calculates the great-circle distance between two points, with
	 * the Haversine formula.
	 * @param float $latitudeFrom Latitude of start point in [deg decimal]
	 * @param float $longitudeFrom Longitude of start point in [deg decimal]
	 * @param float $latitudeTo Latitude of target point in [deg decimal]
	 * @param float $longitudeTo Longitude of target point in [deg decimal]
	 * @param float $earthRadius Mean earth radius in [m]
	 * @return float Distance between points in [m] (same as earthRadius)
	 */
	function haversineGreatCircleDistance(
	  $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
	  // convert from degrees to radians
	  $latFrom = deg2rad($latitudeFrom);
	  $lonFrom = deg2rad($longitudeFrom);
	  $latTo = deg2rad($latitudeTo);
	  $lonTo = deg2rad($longitudeTo);

	  $latDelta = $latTo - $latFrom;
	  $lonDelta = $lonTo - $lonFrom;

	  $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
		cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
	  return $angle * $earthRadius;
	}


	/**
	 *	Can be accessed by guests.
	 *	@param void
	 *	@return bool
	 */
	public function isAccessible()
	{
		return true;
	}


	/**
	 *	Translates recent dates into "today" and "yesterday".
	 *	@param int $timestamp Unix timestamp
	 *	@return string
	 */
	private function relativeTime($timestamp) {
	
		if (date('Ymd') == date('Ymd', $timestamp)) {
			return _t('Today');
		}

		if (date('Ymd', strtotime('yesterday')) == date('Ymd', $timestamp)) {
			return _t('Yesterday');
		}
		
		return date('j M Y', $timestamp);
	}

	/**
	 *	Creates HTML output of recent activity
	 *
	 *	@param int $id describes time range: today, yesterday, week, month
	 *	@return void
	 */
	public function handleActivity($id = 2) {
		$user = NEnvironment::getUser()->getIdentity();
		if (!isset($user)) $this->terminate();
		
		$user_id = $user->getUserId();

		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Activity.stream");
		$cache->clean();
		$cache_key = $user_id.'-'.$id;
		if ($cache->offsetExists($cache_key)) {
			$output = $cache->offsetGet($cache_key);
			echo $output;
			$this->terminate();
		}
		
		$header_time = '';		
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
		
		$now = time();
		// get timeframe from id
		switch ($id) {
			case 2: $header_time = _t("Today"); $from = strtotime('today midnight'); $to = null; break;
			case 3: $header_time = _t("Yesterday"); $from = strtotime('yesterday midnight'); $to = strtotime('today midnight'); break;
			case 4: $header_time = _t("Week"); $from = strtotime('7 days ago midnight'); $to = strtotime('yesterday midnight'); break;
			default: $header_time = _t("Month"); $from = strtotime('1 month ago midnight'); $to = strtotime('7 days ago midnight'); break;
		}

		ob_start();
		// for scrolling
		echo '<div id="activity-scroll-target-'.$id.'"></div>';
		
		echo "<h3>".$header_time."</h3>";
		

		$activities = Activity::getActivities($user_id, $from, $to);
		
		if (isset($activities) && count($activities)) {

			foreach ($activities as $activity) {
			
				switch ($activity['object_type']) {
					case 1:
						if (!empty($activity['affected_user_id']) && $activity['object_id'] == $user_id) $object_id = $activity['affected_user_id']; else $object_id = $activity['object_id'];
						$object_link = '/user/?user_id='.$object_id;
						$object_icon = User::getImage($object_id, 'icon');
						$object_name = User::getUserLogin($object_id);
						$time = $this->relativeTime($activity['timestamp']);
					break;
					case 2:
						$object_link = '/group/?group_id='.$activity['object_id'];
						$object_icon = Group::getImage($activity['object_id'], 'icon');
						$object_name = Group::getName($activity['object_id']);
						$time = $this->relativeTime($activity['timestamp']);
					break;
					case 3:
						$object_link = '/resource/?resource_id='.$activity['object_id'];
						$object_icon = '<b class="icon-' . $resource_name[Resource::getResourceType($activity['object_id'])] . '"></b>';
						$object_name = Resource::getName($activity['object_id']);;
						$time = $this->relativeTime($activity['timestamp']);
					break;
				}
			
				switch ($activity['activity']) {
					case Activity::USER_JOINED:
						$description = _t('You signed up.');
					break;
					case Activity::FRIENDSHIP_REQUEST:
						if ($activity['object_id'] != $user_id) {
							$description = sprintf(_t('User %s requested your friendship.'),$object_name);
						} else {
							$description = sprintf(_t('You requested %s\'s friendship.'),$object_name);
						}
					break;
					case Activity::FRIENDSHIP_YES:
						if ($activity['object_id'] != $user_id) {
							$description = sprintf(_t('User %s accepted your friendship.'),$object_name);
						} else {
							$description = sprintf(_t('You accepted %s\'s friendship.'),$object_name);
						}
					break;
					case Activity::FRIENDSHIP_NO:
						if ($activity['object_id'] != $user_id) {
							$description = sprintf(_t('User %s rejected your friendship.'),$object_name);
						} else {
							$description = sprintf(_t('You rejected %s\'s friendship.'),$object_name);
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
					case Activity::USER_UPDATED: $description = _t('You updated your profile.'); break;
					case Activity::GROUP_RESOURCE_REMOVED: $description = _t('The group unsubscribed from a resource.'); break;
					case Activity::GROUP_UPDATED: $description = _t('The group was updated.'); break;
					case Activity::RESOURCE_UPDATED: $description = _t('The resource was updated.'); break;
					case Activity::LOGIN_FAILED: $description = _t('Somebody tried to login with your name and a wrong password.'); break;
					default: $description = 'Unspecified activity'; break;
				}
			
				printf('<div class="activity-item" onclick="window.location=\'%s\'"><div class="activity-time"><h4>%s</h4></div><div class="activity-link"><h4><a href="%s">%s %s</a></h4></div><div class="activity-description"><h4>%s</h4></div></div>', $object_link, $time, $object_link, $object_icon, $object_name, $description);
			}
		} else {
			echo '<div class="activity-item"><h4>'._t("Nothing to display").'</h4></div>';
		}
		
		if ($id < 5) {
			echo '
	<div id="load-more-'.$id .'">
		<p><a href="javascript:void(0);" id="load_more" class="button">'._t("load more...").'</a></p>
	</div>';

			$id_inc = $id +1;
		
			echo '
	<script>
		$("#load_more").click(function(){
			loadActivity("#load-more-'.$id.'", '.$id_inc.');
		});
	</script>
			';
		}
		$output = ob_get_contents();
		ob_end_clean();
		echo $output;
		if ($id == 2) $time = time()+120; else $time = time()+3600;
		$cache->save($cache_key, $output, array(NCache::EXPIRE => $time));
		$this->terminate();
	}


	/**
	 *	Echoes JSON-encoded array of events for the logged-in user
	 *
	 *	@param int $start UNIX timestamp for begin of time frame
	 *	@param int $end UNIX timestamp for end of time frame
	 *	@return void
	 */
	public function handleGetevents($start=null,$end=null) {
		$user_env = NEnvironment::getUser();		
		if (!$user_env->isLoggedIn()) die('You are not logged in.');
		$user_id = $user_env->getIdentity()->getUserId();
		
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Calendar.events");
		$cache->clean();
		$cache_key = $user_id.'-'.$start.'-'.$end;
		if ($cache->offsetExists($cache_key)) {
			$array_feed_items = $cache->offsetGet($cache_key);
			echo json_encode($array_feed_items);
			die();
		}

		$filter = array('user_id'  => $user_id, 'type' => array(2));
  		$events = Administration::getData(array(ListerControlMain::LISTER_TYPE_RESOURCE), $filter);
  		
  		$array_feed_items = array();
		foreach ($events as $event) {
			$event_start = strtotime($event["resource_data"]['event_timestamp']);
			$event_end = strtotime($event["resource_data"]['event_timestamp_end']);
  			if (!isset($start) || ($event_start > $start && $event_end < $end)) {
				$array_feed_item['id'] = $event['id'];
				$array_feed_item['title'] = $event['name'];
				
				if (isset($event['resource_data']['event_allday']) && $event["resource_data"]['event_allday']) {
					$array_feed_item['start'] = strtotime("midnight",$event_start);
					$array_feed_item['end'] = $event_end;
				} else {
					$array_feed_item['start'] = $event_start;
					$array_feed_item['end'] = $event_end;
				}
				
				$array_feed_item['allDay'] = (bool) $event["resource_data"]['event_allday'];
				
				if ($user_id == $event['author']) {
					$array_feed_item['color'] = '#E13C20';
					$array_feed_item['textColor'] = '#fff';
				} else {
					$array_feed_item['color'] = '#4680B3';
					$array_feed_item['textColor'] = '#fff';
				}
				
				if ($event_start-$event['resource_data']['event_alert'] < time()) {
					$array_feed_item['borderColor'] = '#000';
				} else {
					$array_feed_item['borderColor'] = $array_feed_item['color'];
				}
				$array_feed_item['url'] = NEnvironment::getVariable("URI") . '/resource/?resource_id=' . $event['id'];
				if (Auth::MODERATOR <= Auth::isAuthorized(3, $event['id'])) {
					$array_feed_item['editable'] = true;
				}

				if ( isset($event['resource_data']['event_allday']) && $event['resource_data']['event_allday']) {
					$start_h_m = _t('All-day event');
					$end_h_m = '';
					$timezone = '';				
				} else {
					$start_h_m = date(_t('g:ia'),$event_start);
					$timezone = date("e",$event_start);
					if ($event["resource_data"]['event_timestamp'] != $event["resource_data"]['event_timestamp_end']) {
						$end_h_m = date(_t('g:ia'),$event_end);
					} else {
						$end_h_m = '';
					}
				}
				
				$separator = ($end_h_m) ? "-" : "";
				$class = array(1=>'world',2=>'registered',3=>'person');
				$visibility_icon = '<b class="icon-'.$class[$event['visibility_level']].'"></b>';
				$resource = Resource::create($event['id']);
				if ($resource->hasPosition()) {
					$location_icon = '<img src="/images/icon-pin.png"/>';
				} else {
					$location_icon = '';
				}
				$array_feed_item['description'] = "<b>".$event['name']."</b><br/><i>".$start_h_m." ".$separator." ".$end_h_m." ".$timezone."</i><br/><br/>".$event['description'].'<br/><br/><span style="float:right">'.User::getImage($event['author'],'icon').$visibility_icon.$location_icon."</span>";
				$array_feed_items[] = $array_feed_item;
				unset($resource);
			}
		}
		echo json_encode($array_feed_items);
		$cache->save($cache_key, $array_feed_items, array(NCache::EXPIRE => time()+120));
		die();
	}

	/**
	 *	Handler for changes to events by drag and drop on FullCalendar (home page)
	 *
	 *	@param string $changed
	 * 	@param int $resource_id
	 *  @param int $day_delta
	 *  @param int $minute_delte
	 *  @param bool $allday
	 *	@return void
	 */
	public function handleChangeevent($changed,$resource_id,$day_delta=0,$minute_delta=0,$allday=null) {
		if (Auth::isAuthorized(3,$resource_id)<Auth::MODERATOR) die('You are not authorized.');
		$resource = Resource::create($resource_id);
		$resource_data = $resource->getResourceData();

		if ($resource_data['resource_type']!=2) die('This is not an event.');
		
		switch ($changed) {
			case 'start':
				if (isset($allday)) {
					if ($allday=='true') {
						$resource_data['event_allday'] = 1;
					} elseif ($allday=='false') {
						$resource_data['event_allday'] = 0;
					}
				}
				$timestamp = strtotime($resource_data['event_timestamp']);
				$timestamp_end = strtotime($resource_data['event_timestamp_end']);
				If ($timestamp_end == 0) $timestamp_end = strtotime($resource_data['event_timestamp']);
				$string = sprintf("%s day %s minutes", $day_delta, $minute_delta);
				$resource_data['event_timestamp'] = date("r",strtotime($string, $timestamp));
				$resource_data['event_timestamp_end'] = date("r",strtotime($string, $timestamp_end));

				// schedule alarm
				if ($timestamp + 3600 > time()) { // back-schedule max 60 mins.
					// get all subscribers
					$members = $resource->getAllMembers(array('enabled'=>1));
					if (count($members)) foreach ($members as $member) {
						StaticModel::addCron($timestamp - $resource_data['event_alert'], $member['member_type'], $member['member_id'], $resource_data['resource_name']."\r\n\n".$resource_data['resource_description'], 3, $resource_id);
					}
				} else {
					// event has been changed with time in the past: don't send alerts
					StaticModel::removeCron(0, 0, 3, $resource_id);
				}
			break;
			case 'end':
				$timestamp_end = strtotime($resource_data['event_timestamp_end']);
				If ($timestamp_end == 0) $timestamp_end = strtotime($resource_data['event_timestamp']);
				$string = sprintf("%s day %s minutes", $day_delta, $minute_delta);
				$resource_data['event_timestamp_end'] = date("r",strtotime($string, $timestamp_end));
			break;
			default:
				die();
			break;
		}
		$resource_data = array(
			'event_description' => $resource_data['event_description'],
			'event_url' => $resource_data['event_url'],
			'event_allday' => $resource_data['event_allday'],
			'event_timestamp' => $resource_data['event_timestamp'],
			'event_timestamp_end' => $resource_data['event_timestamp_end'],
			'event_alert' => $resource_data['event_alert']
		);		
		$data['resource_data'] = json_encode($resource_data);

		$resource->setResourceData($data);
		if ($resource->save()) echo "true";
		die();
	}
}
