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
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';
		
		if (file_exists(WWW_DIR . '/files/xml-calendars.txt')) {
			$xml_events = file(WWW_DIR . '/files/xml-calendars.txt', FILE_IGNORE_NEW_LINES);
			if (!empty($xml_events)) $this->template->xml_events = $xml_events;
		}
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
		$defaults['exclude_connections_user_id'] = $user_id;
		
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

}
