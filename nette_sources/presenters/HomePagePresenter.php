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
/*		require_once LIBS_DIR.'/Mobile-Detect/Mobile_Detect.php';
		$detect = new Mobile_Detect;
		if ($detect->isMobile()) $this->template->mobile = true;
		unset($detect);
*/	

		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';

		$user_o = NEnvironment::getUser()->getIdentity();
		if (!empty($user_o)) {
			$access_level = $user_o->getAccessLevel();
			switch ($access_level) {
				case 1: break;
				case 2: $this->template->access_level_welcome =_t('You are a moderator on this platform.');break;
				case 3: $this->template->access_level_welcome =_t('You are an administrator on this platform.');break;
			}
			if ($access_level == 3 || $access_level == 2) {
				$this->template->admin = true;
			}
			$this->template->access_level = $access_level;
		
			if ($access_level > 1) {
				$filter = array(
						'type' => 7
					);
				$number = Administration::getData(array(ListerControlMain::LISTER_TYPE_RESOURCE), $filter, true);
				if ($number > 0) {
					$this->template->reports_pending = $number;
					$this->template->reports_url = NEnvironment::getVariable("URI") . '/administration/reports/';
				}
			}
		
			$this->template->load_fullcalendar = true;
			if (file_exists(WWW_DIR . '/files/xml-calendars.txt')) {
				$xml_events = file(WWW_DIR . '/files/xml-calendars.txt', FILE_IGNORE_NEW_LINES);
				if (!empty($xml_events)) {
					$this->template->xml_events = array_map('trim', $xml_events);
				}
			}
			if (file_exists(WWW_DIR . '/files/home-tabs.txt')) {
				$home_tabs = file(WWW_DIR . '/files/home-tabs.txt', FILE_IGNORE_NEW_LINES);
				if (!empty($home_tabs)) {
					$this->template->home_tabs = array_map('trim', $home_tabs);
				}
			}
		}

	}

	/**
	 *	Filter for My Connections
	 *	@param string $name
	 *	@return object
	 */
	protected function createComponentFilter($name)
	{	
		$options = array(
			'components' => array(
				'homepagefriendlister',
				'homepagegrouplister',
				'homepageresourcelister'
			),
			'refresh_path' => 'Homepage:default',
			'include_map' => true,
			'include_name' => true,
			'include_language' => true,
			'include_type' => true,
			'include_tags' => true,
			'include_pairing' => true
		);
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
				'front_page' => true,
				'top' => true
			),
			'cache_expiry' => 1200
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
				'front_page' => true,
				'top' => true
			),
			'cache_expiry' => 1200
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
				'front_page' => true,
				'top' => true
			),
			'cache_expiry' => 1200
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
				'user_id' => $user->getUserId(),
				'sort_by_activity' => true
			),
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'front_page' => true,
				'your_connections' => true,
				'show_online_status' => true
			),
			'cache_tags' => array("user_id/".$user->getUserId()),
			'cache_expiry' => 600
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
				'user_id' => $user->getUserId(),
				'sort_by_activity' => true
			),
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'your_connections' => true,
				'front_page' => true
			),
			'cache_tags' => array("user_id/".$user->getUserId()),
			'cache_expiry' => 600
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
				'user_id' => $user->getUserId(),
				'sort_by_activity' => true
			),
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'your_connections' => true,
				'front_page' => true
			),
			'cache_tags' => array("user_id/".$user->getUserId()),
			'cache_expiry' => 600
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
		$filter = $this->suggestFilter(1);
		$number = Administration::getData(array(ListerControlMain::LISTER_TYPE_USER), $filter, true);
		if ($number < 5) {
			$filter = $this->suggestFilter(10);
			$number = Administration::getData(array(ListerControlMain::LISTER_TYPE_USER), $filter, true);
			if ($number < 5) {
				$filter = $this->suggestFilter(0);
				$map_used = false;
			} else {
				$map_used = true;
			}
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
			),
			'cache_tags' => array("user_id/".$user->getUserId(), "name/number"),
			'cache_expiry' => 600
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
		$filter = $this->suggestFilter(1);
		$number = Administration::getData(array(ListerControlMain::LISTER_TYPE_GROUP), $filter, true);
		if ($number < 5) {
			$filter = $this->suggestFilter(10);
			$number = Administration::getData(array(ListerControlMain::LISTER_TYPE_GROUP), $filter, true);
			if ($number < 5) {
				$filter = $this->suggestFilter(0);
				$map_used = false;
			} else {
				$map_used = true;
			}
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
			),
			'cache_tags' => array("user_id/".$user->getUserId()),
			'cache_expiry' => 600
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
		$filter = $this->suggestFilter(1);
		$number = Administration::getData(array(ListerControlMain::LISTER_TYPE_RESOURCE), $filter, true);
		if ($number < 5) {
			$filter = $this->suggestFilter(10);
			$number = Administration::getData(array(ListerControlMain::LISTER_TYPE_RESOURCE), $filter, true);
			if ($number < 5) {
				$filter = $this->suggestFilter(0);
				$map_used = false;
			} else {
				$map_used = true;
			}
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
			),
			'cache_tags' => array("user_id/".$user->getUserId()),
			'cache_expiry' => 600
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	 *	Sets the filter for retrieving the items according to user's own settings.
	 *
	 *	@param bool @radius_multiplicator Include location on map if available
	 *	@return array filter values
	 */
	private function suggestFilter($radius_multiplicator = 1) {

		$user = NEnvironment::getUser()->getIdentity();
		$user_id = $user->getUserId();
		$key = $user_id.'-'.$radius_multiplicator;
/*		
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Filter.suggest");
		$cache->clean();
		if ($cache->offsetExists($key)) {
			$filter = $cache->offsetGet($key);
			return $filter;
		}
*/
		if (!empty($user)) {
//			$user_data = $user->getUserData();
			$tags = $user->getTags();
			if (count($tags) == 0) {
				$defaults['tags']['all'] = 1;
			} else {
				$defaults['tags']['all'] = 0;
				foreach ($tags as $tag_row) {
					if ($tag_row->getTagId()) {
						$defaults['tags'][$tag_row->getTagId()] = 1;
					}
				}
			}
		} else {
			$defaults['tags']['all'] = 1;
		}
		
		if ($radius_multiplicator > 0 && !empty($user) && $user->hasPosition()) {
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
				'length' => $this->haversineGreatCircleDistance($position['user_position_x'], $position['user_position_y'],$r_x,$r_y) * $radius_multiplicator
				);			
		} else {
			$defaults['mapfilter']	= NULL;
		}

		$defaults['filter_pairing'] = "and";
		$defaults['name']           = "";
		$defaults['status']         = NULL;
		$defaults['trash']          = NULL;
		$defaults['language']       = User::getUserLanguage($user_id);
		$defaults['type']           = "all";
		$defaults['exclude_connections_user_id'] = $user_id;
		
//		$cache->save($key, $defaults, array(NCache::EXPIRE => time()+300, NCache::TAGS => array("user_id/$user_id")));
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

}
