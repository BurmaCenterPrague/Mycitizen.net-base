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
 

class ExternalFilter extends NControl
{
	protected $components = array();
	protected $refresh_path = "Homepage:default";
	protected $refresh_path_params = array();
	protected $include_status = false;
	protected $include_map = false;
	protected $include_type = false;
	protected $include_tags = false;
	protected $include_suggest = false;
	protected $include_trash = false;
	protected $include_pairing = false;
	protected $include_language = false;
	protected $include_name = false;
	protected $hide_reset = false;
	protected $hide_apply = false;
	protected $hide_filter = false;
	
	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function __construct($parent, $name, $options = array())
	{
		parent::__construct($parent, $name);
		
		if (isset($options['refresh_path'])) {
			$this->refresh_path = $options['refresh_path'];
			if (isset($options['refresh_path_params'])) {
				$this->refresh_path_params = $options['refresh_path_params'];
			}
		}

		if (isset($options['hide_filter'])) {
			$this->hide_filter = true;
		}
		
		if (isset($options['hide_reset'])) {
			$this->hide_reset = true;
		}
		
		if (isset($options['hide_apply'])) {
			$this->hide_apply = true;
		}
						
		if (isset($options['include_status'])) {
			$this->include_status = true;
		}
		
		if (isset($options['include_map'])) {
			$this->include_map = true;
		}
		
		if (isset($options['include_type'])) {
			$this->include_type = true;
		}
		
		if (isset($options['include_tags'])) {
			$this->include_tags = true;
		}
		
		if (isset($options['components'])) {
			$this->components = $options['components'];
		}
		
		$user = NEnvironment::getUser()->getIdentity();
		if (isset($options['include_suggest']) && !empty($user)) {
			$this->include_suggest = true;
		}
		
		if (isset($options['include_trash'])) {
			$this->include_trash = true;
		}
		
		if (isset($options['include_pairing'])) {
			$this->include_pairing = true;
		}

		if (isset($options['include_language'])) {
			$this->include_language = true;
		}		

		if (isset($options['include_name'])) {
			$this->include_name = true;
		}		

	}
	
	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function render()
	{
		if (NEnvironment::getVariable("GLOBAL_FILTER")) $this->syncFilterArray();
	
		$template = $this->template;
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}
		$template->setTranslator(new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/messages.mo', $language));
		
		$template->setFile(dirname(__FILE__) . '/ExternalFilter.phtml');
		$template->name = $this->name;
		$this['filter']->setDefaults($this->getFilterArray());
		$template->refresh_path = $this->refresh_path;
		$template->baseUri = NEnvironment::getVariable("URI") . '/';


		if ($this->hide_filter) {
			$template->hide_filter = true;
		}

		if ($this->hide_reset) {
			$template->hide_reset = true;
		}

		if ($this->hide_apply) {
			$template->hide_apply = true;
		}
		
		if ($this->include_status) {
			$template->include_status = true;
		}
		
		if ($this->include_tags) {
			$template->include_tags = true;
		}
		
		if ($this->include_map) {
			$template->include_map = true;
		}
		
		if ($this->include_type) {
			$template->include_type = true;
		}
		
		if ($this->include_suggest) {
			$template->include_suggest = true;
		}
		
		if ($this->include_trash) {
			$template->include_trash = true;
		}
		
		if ($this->include_pairing) {
			$template->include_pairing = true;
		}
		
		if ($this->include_language) {
			$template->include_language = true;
		}

		if ($this->include_name) {
			$template->include_name = true;
		}
				
		$template->render();
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function createComponentFilter()
	{
		$type  = array(
			'all' => _t("all resources")
		);
		$types = Resource::getTypeArray();
		foreach ($types as $id => $name) {
			if ($id == 1) {
				continue;
			}
			$type[$id] = $name;
		}
		
		$form = new NAppForm($this, "filter");
		$form->addRadioList('filter_pairing', _t('Connect filters with'), array(
			'and' => 'AND',
			'or' => 'OR'
		))->separatorPrototype->setName(NULL);
		$form->addText('name', _t('Name'));
		$enabled = array(
			'null' => _t('all'),
			'1' => _t('active'),
			'0' => _t('inactive')
		);

		$logged_user = NEnvironment::getUser()->getIdentity();
		if (isset($logged_user)) {
			$logged_user_id = $logged_user->getUserId();
			$count_unread = User::getUnreadMessages($logged_user_id);
			if ($count_unread) $trash['2'] = _t('Unread');
		}
		$trash['0'] = _t('Mailbox');
		$trash['1'] = _t('Trash');
		
		$form->addSelect('status', _t('Status'), $enabled);
		$form->addRadioList('trash', '', $trash)->getSeparatorPrototype()->setName(NULL);
//		$form['trash']->setDefaultValue('0');
		$form['trash']->getControlPrototype()->class('trash-radio');
				
		$form->addCheckbox("all", _t("all tags"));
		
		$language    = Language::getArray();
		$language[0] = _t('all');
		ksort($language);
		$form->addSelect('language', _t('Language'), $language);
		$form->addSelect('type', _t('Type'), $type);
		$tags = $form->addContainer('tags');
		$tags->addCheckbox("all", _t("all tags"));
		foreach (Tag::getTreeArray() as $key => $row) {
			$level_class = $row['level'] ? 'tag_child tag_child_parent_'.substr('00'.$row['tag_parent_id'],-3,3) : 'tag_parent_'.substr('00'.$row['tag_id'],-3,3);
			$tags->addCheckbox($row['tag_id'], _t_tags($row['tag_name']));
			$tags[$row['tag_id']]->getControlPrototype()->class($level_class);
			$tags['all']->getControlPrototype()->class('tag-checkbox');
		}
		$form->addComponent(new MapContainer('map', 'map'), 'mapfilter');
		$form->addSubmit('reset', _t('Clear Filter'));
		$form->addSubmit('filter', _t('Apply Filter'));
		$form->addSubmit('suggest', _t('Similar to me'));
		$form->onSubmit[] = array(
			$this,
			'filterFormSubmitted'
		);

		$user = NEnvironment::getUser()->getIdentity();
		$defaults['tags']['all'] = 1;
		$defaults['filter_pairing'] = 'and';
		$defaults['trash'] = '2';
		$form->setDefaults($defaults);

		return $form;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentFiltermap($name)
	{
		$data    = array();
		$control = new MapControl($this, $name, $data, array(
			'type' => 'radius'
		));
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function filterFormSubmitted(NAppForm $form)
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		
		if ($form['filter']->isSubmittedBy()) {
			$session->data['object_id'] = NULL;
			$values     = $form->getValues();
			$filter     = $this->getFilterArray();

			if (NEnvironment::getVariable("GLOBAL_FILTER")) $filter=$this->syncFilterArray($values);
			
			if ($values['tags']['all']==1) {
				foreach (Tag::getTreeArray() as $key => $row) {
					$values['tags'][$row['tag_id']] = 0;
				}
			} else {
				$values['tags']['all']=0;
			}
			$new_filter = array_merge($filter, $values);
			$this->setFilterArray($new_filter);
			
			$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
		} else if ($form['reset']->isSubmittedBy()) {
			$user                    = NEnvironment::getUser()->getIdentity();
			$defaults['tags']['all'] = 1;
			foreach (Tag::getTreeArray() as $key => $row) {
				$defaults['tags'][$row['tag_id']] = 0;
			}
			$this->clearFilterArray();
			
			$defaults['mapfilter']      = NULL;
			$defaults['filter_pairing'] = "and";
			$defaults['name']           = "";
			$defaults['status']         = NULL;
			$defaults['trash']          = NULL;
			$defaults['language']       = 0;
			$defaults['type']           = "all";
			
			$filter     = $this->getFilterArray();
			
			$new_filter = array_merge($filter, $defaults);
			
			if (NEnvironment::getVariable("GLOBAL_FILTER")) $this->syncFilterArray($new_filter);
			
			$this->setFilterArray($new_filter);
			$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
		} else if ($form['suggest']->isSubmittedBy()) {
			$session->data['object_id'] = NULL;
			$user = NEnvironment::getUser()->getIdentity();
			if (!empty($user)) {
				$ud   = $user->getUserData();
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
			
			if (!empty($user) && $user->hasPosition()) {
				
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
					'length' => $this->haversineGreatCircleDistance($position['user_position_x'],$position['user_position_y'],$r_x,$r_y)
					);			
			} else {
				$defaults['mapfilter']      = NULL;
			}

			$defaults['filter_pairing'] = "and";
			$defaults['name']           = "";
			$defaults['status']         = NULL;
			$defaults['trash']          = NULL;
			$defaults['language']       = $ud['user_language'];
			$defaults['type']           = "all";
			
			$filter     = $this->getFilterArray();
			$new_filter = array_merge($filter, $defaults);
			
			if (NEnvironment::getVariable("GLOBAL_FILTER")) $this->syncFilterArray($new_filter);
			$this->setFilterArray($new_filter);
			$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
		}
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
	  $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
	{
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
 *	Synchronizes the three separate filters for user, group and resource category pages. (Invoked if variable.GLOBAL_FILTER is set in config.ini)
 *	@param	array $filter optional filter to merge other session filter into
 *	@return	array resulting filter
*/
	public function syncFilterArray($filter=array()) {

		$sync = array('userlister', 'grouplister', 'defaultresourceresourcelister');
		
		if (!count(array_intersect($this->components, $sync))) {
			return $filter;
		}
		
		if (empty($filter)) {		
			foreach ($sync as $component_name) {
				$session = NEnvironment::getSession()->getNamespace($component_name);
				if (is_array($session->filterdata)) {
					$filter = array_merge($filter, $session->filterdata);
					$session->data['object_id'] = NULL;
				}
			}
		}
		
		if (isset($filter['page'])) unset($filter['page']);
		if (isset($filter['limit'])) unset($filter['limit']);
		if (isset($filter['count'])) unset($filter['count']);
		if (isset($filter['trash'])) unset($filter['trash']);

		if (!empty($filter))  {
			foreach ($sync as $component_name) {
				$session = NEnvironment::getSession()->getNamespace($component_name);
				if (is_array($session->filterdata) && is_array($filter)) {
					$session->filterdata = array_merge($session->filterdata, $filter);
				} else {
					$session->filterdata = $filter;
				}
			}
		}

		return $filter;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function setFilterArray($filter)
	{
		foreach ($this->components as $component_name) {
			$old_filter = array();
			$session    = NEnvironment::getSession()->getNamespace($component_name);
			$old_filter = $session->filterdata;

			// preventing strange memory overflows
			if (count($filter) > 1000) $filter = array();
			if (count($old_filter) > 1000) $old_filter = array();
			

			if (is_array($old_filter)) { //var_dump($old_filter);
				$old_filter = array_merge($old_filter, $filter);
			} else {
				$old_filter = $filter;
			}
			$session->filterdata = $old_filter;
			$session->data['object_id'] = NULL;
			
			if ($component_name == 'userlister') $name = 'User';
			if ($component_name == 'grouplister') $name = 'Group';
			if ($component_name == 'defaultresourceresourcelister') $name = 'Resource';
		
			if (isset($name)) {
				$user_session = NEnvironment::getSession()->getNamespace($name);
				$user_session->data = NULL;
			}
	
			unset($old_filter);
		}		
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function clearFilterArray($filter = null)
	{
		if (NEnvironment::getVariable("GLOBAL_FILTER") && !count(array_intersect($this->components, array("homepagefriendlister", "homepagegrouplister", "homepageresourcelister")))) {
			$this->components[]='defaultresourceresourcelister';
			$this->components[]='userlister';
			$this->components[]='grouplister';
		}

		foreach ($this->components as $component_name) {
			$session = NEnvironment::getSession()->getNamespace($component_name);
			$session->data['object_id'] = NULL;
			
			if (is_null($filter)) {
				unset($session->filterdata['name']);
				unset($session->filterdata['status']);
				unset($session->filterdata['type']);
				unset($session->filterdata['mapfilter']);
			} else {
				if ($session->filterdata !== NULL) {
					foreach ($filter as $key => $value) {
						if (in_array($key, $session->filterdata)) {
							unset($session->filterdata[$key]);
						}
					}
				}
			}
		}
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getFilterArray()
	{
		$filter = array();
		foreach ($this->components as $component_name) {
			$session = NEnvironment::getSession()->getNamespace($component_name);
			if (empty($session->filterdata)) {
				continue;
			}
			$filter = $session->filterdata;
		}
		
		return $filter;
	}
	
}
?>
