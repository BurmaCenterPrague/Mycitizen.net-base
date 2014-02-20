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
 

class MapControl extends NControl
{
	protected $data;
	protected $options = array('type' => 'view');
	
	protected $object;

/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function __construct($parent, $name, $data = array(), $options = array())
	{
		parent::__construct($parent, $name);
		$this->data = $data;
		if (isset($options['object'])) {
			$this->object = $options['object'];
		}
		unset($options['object']);
		if (count($options) > 0) {
			$this->options = $options;
		}
		
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function render()
	{
		$template = $this->template;
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}
		$template->setTranslator(new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/messages.mo', $language));
		
		
		$template->setFile(dirname(__FILE__) . '/MapControl.phtml');
		$template->name = $this->name;
		$template->data = $this->data;
		if (isset($this->options['type'])) {
			$template->type = $this->options['type'];

			switch ($this->object['type']) {
				case 'user': $template->item_location_label = _t('user');break;
				case 'group': $template->item_location_label = _t('group');break;
				case 'resource': $template->item_location_label = _t('resource');break;
			}
		}
		if (isset($this->options['external_container'])) {
			$template->container_id = $this->options['external_container'];
		} else {
			$template->container_id = null;
		}
		$template->default_latitude  = Settings::getVariable('gps_default_latitude');
		$template->default_longitude = Settings::getVariable('gps_default_longitude');
		$template->render();
		
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function createComponentMapfilter()
	{
		$form = new NAppForm($this, "mapfilter");
		$form->addText('location', 'Location');
		$form->addSubmit('filter', 'Filter');
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'filterFormSubmitted'
		);
		return $form;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleMapData()
	{
		$position = array();
		foreach ($this->data as $key => $object) {
			if ($object['type'] == 'user') {
				$obj = User::Create($object['id']);
				if (!empty($obj)) {
					$object_data = $obj->getUserData();
					
					$lat = $object_data['user_position_x'];
					$lng = $object_data['user_position_y'];
					if ($lat == 0 && $lng == 0) {
						$lat = null;
						$lng = null;
					}
					$position[] = array(
						'type' => 'user',
						'location' => array(
							'latitude' => $lat,
							'longitude' => $lng
						)
					);
				}
				
			}
			if ($object['type'] == 'group') {
				$obj = Group::Create($object['id']);
				if (!empty($obj)) {
					$object_data = $obj->getGroupData();
					$lat         = $object_data['group_position_x'];
					$lng         = $object_data['group_position_y'];
					if ($lat == 0 && $lng == 0) {
						$lat = null;
						$lng = null;
					}
					
					$position[] = array(
						'type' => 'group',
						'location' => array(
							'latitude' => $lat,
							'longitude' => $lng
						)
					);
				}
				
			}
			if ($object['type'] == 'resource') {
				$obj = Resource::Create($object['id']);
				if (!empty($obj)) {
					$object_data = $obj->getResourceData();
					$lat         = $object_data['resource_position_x'];
					$lng         = $object_data['resource_position_y'];
					if ($lat == 0 && $lng == 0) {
						$lat = null;
						$lng = null;
					}
					
					$position[] = array(
						'type' => 'resource',
						'location' => array(
							'latitude' => $lat,
							'longitude' => $lng
						)
					);
				}
				
			}
		}
		print_r(json_encode($position));
		$this->parent->terminate();
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleMapEdit($latitude, $longitude)
	{
		if ($this->object['type'] == 'user') {
			$obj = User::Create($this->object['id']);
			if (!empty($obj)) {
				$data['user_position_x'] = $latitude;
				$data['user_position_y'] = $longitude;
				$obj->setUserData($data);
				$obj->save();
			}
		}
		if ($this->object['type'] == 'group') {
			$obj = Group::Create($this->object['id']);
			if (!empty($obj)) {
				$data['group_position_x'] = $latitude;
				$data['group_position_y'] = $longitude;
				$obj->setGroupData($data);
				$obj->save();
			}
		}
		if ($this->object['type'] == 'resource') {
			$obj = Resource::Create($this->object['id']);
			if (!empty($obj)) {
				$data['resource_position_x'] = $latitude;
				$data['resource_position_y'] = $longitude;
				$obj->setResourceData($data);
				$obj->save();
			}
		}
		$this->parent->terminate();
	}
	
}
?>