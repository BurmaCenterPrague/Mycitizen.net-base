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
 

final class ResourcePresenter extends BasePresenter
{
	protected $resource;
	protected $grabzIt;

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function startup()
	{
		parent::startup();
		
		// screen grabbing
		$app_key = NEnvironment::getVariable("GRABZIT_KEY");
		$app_secret = NEnvironment::getVariable("GRABZIT_SECRET");
		
		if (!empty($app_key) && !empty($app_secret)) {
			include(LIBS_DIR.'/GrabzIt/GrabzItClient.class.php');
			$this->grabzIt = new GrabzItClient($app_key, $app_secret);
		}
		
		
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionDefault($resource_id = null)
	{
		// prevent Ajax calls on /resource/ pages to be redirected
		$query = NEnvironment::getHttpRequest();
		$do = $query->getQuery("do");

		if ($this->isAjax() && $do!='defaultPage' && $do!='detailPage' && $do!='changePage' && $do!='clickLink') return;
		
		$session = NEnvironment::getSession()->getNamespace('defaultresourceresourcelister');
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';
		$acceptable_resource_types = array(2, 3, 4, 5, 6);
		if (!is_null($resource_id)) {
			$this->setView('detail');
//			$this->template->load_js_css_editor = true;
			$this->resource = Resource::create($resource_id);
			$d              = $this->resource->getResourceData();
			if (empty($d)) {
				$this->flashMessage(_t("This resource doesn't exist."), 'error');
				$this->redirect("Resource:default");
			}
			if (Auth::isAuthorized(Auth::TYPE_RESOURCE, $resource_id) == 0) {
				$this->flashMessage(_t("You are not allowed to view this resource."), 'error');
				$this->redirect("Resource:default");
			}
			if (!in_array(Resource::getResourceType($resource_id),$acceptable_resource_types)) {
				$this->flashMessage(_t("You are not allowed to view this resource."), 'error');
				$this->redirect("Resource:default");
			}
			
			$this->template->last_activity = $this->resource->getLastActivity();
			$this->template->format_date = _t("j.n.Y");
			
			$this->visit(3, $resource_id);
			if ($this->resource->hasPosition()) {
				$this->template->showmap = true;
			}
			
			$this->template->resource_id = $resource_id;
			$data                        = $this->resource->getResourceData();
			$user                        = NEnvironment::getUser()->getIdentity();
			if (!empty($user)) {
				$this->resource->setOpened($user->getUserId());
			}
			if (isset($data['media_type']) && $data['media_type'] == 'media_youtube') {
				$data['youtube_link'] = preg_replace("/[^?]*\?v=(.+)/", "$1", $data['media_link']);
			}
			if (isset($data['media_type']) && $data['media_type'] == 'media_vimeo') {
				$data['vimeo_link'] = $data['media_link'];
			}
			if (isset($data['media_type']) && $data['media_type'] == 'media_soundcloud') {
				$data['soundcloud_link'] = urlencode($data['media_link']);
			}
			if (isset($data['media_type']) && $data['media_type'] == 'media_bambuser') {
				$data['bambuser_link'] = preg_replace("/.+bambuser.com\/v\/(\d+)/", "$1", $data['media_link']);
			}

			$owner = $this->resource->getOwner();
			if (!is_null($owner)) {
				$owner_data            = $owner->getUserData();
				$this->template->owner = array(
					'owner_id' => $owner->getUserId(),
					'owner_name' => $owner_data['user_login']
				);
				if (!empty($user)) {
					if ($user->getUserId() == $owner->getUserId()) {
						$this->template->iamowner = true;
					}
				}
			}
			
			$this->template->data = $data;
			$session              = NEnvironment::getSession()->getNamespace($this->name);
			$session->data        = array(
				'object_id' => $resource_id
			);
			
			if (!empty($this->resource)) {
				$this->template->resource_tags = $this->resource->groupSortTags($this->resource->getTags());
				
				$image = $this->resource->getScreenshot();
				if (!empty($image)) $this->template->screenshot = $image;
			}
			
		}

		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$data['object_data'] = Resource::create($session->data['object_id'])->getResourceData();
			if (!empty($data['object_data'])) {
				$data['object_data']['resource_id'] = $session->data['object_id'];
				if (!in_array(Resource::getResourceType($data['object_data']['resource_id']),$acceptable_resource_types)) {
					$this->flashMessage(_t("You are not allowed to view this resource."), 'error');
					unset($session->data);
					$this->redirect("Resource:default");
				}

				$resource_object = Resource::create($session->data['object_id']);
				if (!empty($resource_object)) {
					$owner = $resource_object->getOwner();
					if (!is_null($owner)) {
						$owner_data            = $owner->getUserData();
						$this->template->owner = array(
							'owner_id' => $owner->getUserId(),
							'owner_name' => $owner_data['user_login']
						);
						$user                  = NEnvironment::getUser()->getIdentity();
						if (!empty($user)) {
							if ($user->getUserId() == $owner->getUserId()) {
								$this->template->iamowner = true;
							}
						}
					
					}
				
				}
				$user = NEnvironment::getUser()->getIdentity();
				if (!empty($user)) {
					if ($resource_object->userIsRegistered($user->getUserId())) {
						$data['object_data']['logged_user_member'] = 1;
					} else {
						$data['object_data']['logged_user_member'] = 0;
					}
					$this->template->logged_user = $user->getUserId();
				} else {
					$data['object_data']['logged_user_member'] = -1;
				}
			
				$image = $resource_object->getScreenshot();
				if (!empty($image)) $this->template->screenshot = $image;
			
				### ??? $data set?
				$this->template->default_data = $data; 

				/* date_default_timezone_set($ ... ) */
				if (isset($data['object_data']['event_allday']) && $data['object_data']['event_allday']) {
					$this->template->start_formatted = date(_t('l, j F Y'), strtotime($data['object_data']['event_timestamp']));
					if (isset($data['object_data']['event_timestamp_end'])) $this->template->end_formatted = date(_t('l, j F Y'), strtotime($data['object_data']['event_timestamp_end']));
				} else {
					$this->template->start_formatted = date(_t('l, j F Y, g:ia T'), strtotime($data['object_data']['event_timestamp']));
					if (isset($data['object_data']['event_timestamp_end'])) $this->template->end_formatted = date(_t('l, j F Y, g:ia T'), strtotime($data['object_data']['event_timestamp_end']));			
				}			

				// event icon
				if (strtotime($data['object_data']['event_timestamp'])-$data['object_data']['event_alert'] > time()) {
					$this->template->event_ahead = true;
				} else {
					if (isset($data['object_data']['event_timestamp_end']) && $data['object_data']['event_timestamp_end']) {
						if (strtotime($data['object_data']['event_timestamp_end']) > time()) $this->template->event_alert = true;
					} elseif (strtotime($data['object_data']['event_timestamp']) > time()) $this->template->event_alert = true;
				}
				
				if (isset($data['object_data']['resource_language'])) {
					$languages = Language::getArray();
					$this->template->object_language = $languages[$data['object_data']['resource_language']];
				}
		

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
				$this->template->resource_type = $data['object_data']['resource_type']==5 ? $resource_name[$data['object_data']['media_type']] : $resource_name[$data['object_data']['resource_type']];

				$resource_type_labels = array(
					1=>_t('message'),
					2=>_t('event'),
					3=>_t('organization'),
					4=>_t('document'),
					6=>_t('link to external resource'),
					7=>'7',
					8=>'8',
					9=>'friendship',
					10=>'friendship request',
					11=>'noticeboard message',
					'media_soundcloud'=>_t('sound on Soundcloud'),
					'media_youtube'=>_t('video on YouTube'),
					'media_vimeo'=>_t('video on Vimeo'),
					'media_bambuser'=>_t('live-video on Bambuser')
					);
				$this->template->resource_type_label  = $data['object_data']['resource_type']==5 ? $resource_type_labels[$data['object_data']['media_type']] : $resource_type_labels[$data['object_data']['resource_type']];

				/* date_default_timezone_set($ ... ) */
				if (isset($data['object_data']['event_allday']) && $data['object_data']['event_allday']) {
					$this->template->start_formatted = date(_t('l, j F Y'), strtotime($data['object_data']['event_timestamp']));
					if (isset($data['object_data']['event_timestamp_end'])) $this->template->end_formatted = date(_t('l, j F Y'), strtotime($data['object_data']['event_timestamp_end']));
				} else {
					$this->template->start_formatted = date(_t('l, j F Y, g:ia T'), strtotime($data['object_data']['event_timestamp']));
					if (isset($data['object_data']['event_timestamp_end'])) $this->template->end_formatted = date(_t('l, j F Y, g:ia T'), strtotime($data['object_data']['event_timestamp_end']));			

			
				// event icon
				if (strtotime($data['object_data']['event_timestamp'])-$data['object_data']['event_alert'] > time()) {
					$this->template->event_ahead = true;
				} else {
					if (isset($data['object_data']['event_timestamp_end']) && $data['object_data']['event_timestamp_end']) {
						if (strtotime($data['object_data']['event_timestamp_end']) > time()) $this->template->event_alert = true;
					} elseif (strtotime($data['object_data']['event_timestamp']) > time()) $this->template->event_alert = true;
				}
			}
		}
		$user = NEnvironment::getUser()->getIdentity();
		if (!empty($user)) {
			$this->template->logged_user = $user->getUserId();
		}

		$this->template->event_alert_times = array(
			0 => _t('no alert'),
			60 => '1 min',
			300 => '5 min',
			600 => '10 min',
			900 => '15 min',
			1800 => '30 min',
			3600 => '1 h',
			3600*12 => '12 h',
			3600*24 => '24 h',
			3600*24*7 => _t('1 week')
		);
		}
		
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionCreate()
	{
		// all users can create resources, but without sufficient permissions they must be private!

		$query = NEnvironment::getHttpRequest();
		$date = $query->getQuery("date");
		if (!empty($date)) $this->template->initial_selection = true;
		
		$resource = Resource::create();

		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';
		$this->template->load_js_css_editor = true;
		$this->template->load_js_css_datetimepicker = true;
		$this->template->load_js_css_tree = true;
		
		$this->resource = $resource;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionEdit($resource_id = null)
	{
		if (Auth::isAuthorized(3,$resource_id) < Auth::ADMINISTRATOR) {
			$this->flashMessage("You are not allowed to edit this resource.","error");
			$this->redirect("Resource:default", $resource_id);
		}
		$user     = NEnvironment::getUser()->getIdentity();
		$resource = Resource::create($resource_id);
		if (!empty($resource)) {
			$this->resource = $resource;
			$resource_id    = $this->resource->getResourceId();
			
			if (!empty($resource_id)) {
				$access = Auth::isAuthorized(Auth::TYPE_RESOURCE, $resource_id);
				if ($access < 2) {
					$this->redirect("Resource:default");
				}
				
				$this->template->resource_id = $resource_id;
				$form = $this['updateform'];

			}
			
			$screenshot_url = $resource->getThumbnailUrl();
			If (!empty($screenshot_url)) $this->template->screenshot_url = $screenshot_url;
			$image = $resource->getScreenshot(_t('View big screenshot'),true);
			if (!empty($image)) $this->template->screenshot = $image;

			
			$this->template->resource_tags = $this->resource->groupSortTags($this->resource->getTags());
		} else {
			$this->redirect("Resource:default");
		}
		
		$this->template->resource_id = $resource->getResourceId();
		$this->template->load_js_css_editor = true;
		$this->template->load_js_css_datetimepicker = true;
		$this->template->load_js_css_tree = true;
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';
	}


	/**
	 *	Preparing form submission for comments on Resources
	 *	@param
	 *	@return
	 */
	protected function createComponentChatform()
	{
		$form = new NAppForm($this, 'chatform');
		$form->addTextarea('message_text', '');
		$form['message_text']->getControlPrototype()->class('ckeditor-small');
		$form->addSubmit('send', _t('Post'));
		$form->addProtection(_t('Error submitting form.'));
		
		$form->onSubmit[] = array(
			$this,
			'chatformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	Processing form submission for comments on Resources
	 *	@param
	 *	@return
	 */
	public function chatformSubmitted(NAppForm $form)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$values                  = $form->getValues();
		$resource                = Resource::create();
		$data                    = array();
		$data['resource_author'] = $user->getUserId();
		$data['resource_type']   = 8;
		$data['resource_data']   = json_encode(array(
			'message_text' => $values['message_text']
		));
		$resource->setResourceData($data);
		$resource->setParent($this->resource->getResourceId());
		$data_doublette_check = array_merge($data, array('resource_parent_id' => $this->resource->getResourceId()));
		$check = $resource->check_doublette($data_doublette_check, $user->getUserId(), 1);
		if ($check === true) {
			$this->flashMessage(_t('You just said that.'));
			$this->redirect("Resource:default", array(
				'resource_id' => $this->resource->getResourceId()
			));
		}
		$resource->save();
		$this->resource->setLastActivity();
		
		Activity::addActivity(Activity::RESOURCE_COMMENT, $this->resource->getResourceId(), 3);
		
		$resource->updateUser($user->getUserId(), array(
			'resource_user_group_access_level' => 1
		));

		### clear cache for $this->resource->getResourceId()
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		$cache->clean(array(NCache::TAGS => array("resource_id/".$this->resource->getResourceId(), "name/chatlisterresource")));
		$cache->clean(array(NCache::TAGS => array("resource_id/".$this->resource->getResourceId(), "name/chatlisterresource")));

		$this->redirect("Resource:default", array(
			'resource_id' => $this->resource->getResourceId()
		));

	}


	/**
	*	Form on resource detail pages that list the user's own group and offer to subscribe.
	*
	*/
	protected function createComponentSubscriberesourceform()
	{
		$group_selection = array();
		
		$form = new NAppForm($this, 'subscriberesourceform');		
		$user = NEnvironment::getUser()->getIdentity();
		$my_groups = $user->getGroups();
		
		foreach($my_groups as $my_group) {
		
			$group_id = $my_group['group_id'];
			

			// check permission
			if (Auth::isAuthorized(Auth::TYPE_GROUP, $group_id) >= 2) {

				// check if already subscribed
				if (isset($this->resource) && !$this->resource->groupIsRegistered($group_id)) {				
					$group = new Group($group_id);
					$data = $group->getGroupData();
					$group_selection[$group_id] = $data['group_name'];
					unset($group);
				}

			}
		
		}

		if (empty($group_selection)) {
			$form->addMultiSelect('group_id', '', array('0'=>"No groups available."));
			return;
		}
		
		$form->addMultiSelect('group_id', '', $group_selection);
		$form->addSubmit('send', _t('Subscribe'));
		$form->addProtection(_t('Error submitting form.'));
		
		$form->onSubmit[] = array(
			$this,
			'subscriberesourceformSubmitted'
		);
		
		return $form;
	}
	
	/**
	*	Receiving and processing return values to subscribe group(s)
	*	@param string|array $values['group_id']
	*	@return
	*/
	public function subscriberesourceformSubmitted(NAppForm $form)
	{
	
		if (Auth::isAuthorized(Auth::TYPE_RESOURCE, $this->resource->getResourceId()) < 1) {
			$this->flashMessage(sprintf(_t("Insufficient permissions to subscribe group %s to this resource."),$group_name), 'error');
			$this->redirect("Resource:default", array(
				'resource_id' => $this->resource->getResourceId()
			));
		}
		
		$user = NEnvironment::getUser()->getIdentity();
		$values = $form->getValues();
		
		if (is_array($values['group_id'])) {

			foreach ($values['group_id'] as $group_id) {
			
				$this->subscribeGroup($group_id);

			}
		
		} else {

			$this->subscribeGroup($values['group_id']);
		
		}

		$this->redirect("Resource:default", array(
				'resource_id' => $this->resource->getResourceId()
			));
			
	}


	/**
	 *	Doing the actual subscription for subscriberesourceformSubmitted().
	 *	@param
	 *	@return
	 */
	private function subscribeGroup($group_id) {
	
		if (empty($group_id) || $group_id<1) {
			$this->terminate();
		}
		
		if (Auth::isAuthorized(Auth::TYPE_GROUP, $group_id) < 2) {
			$this->terminate();
		}
		
		$group = new Group($group_id);
		$group_name = Group::getName($group_id);
		
		if (!$this->resource->groupIsRegistered($group_id)) {
			$this->resource->updateGroup($group_id, array(
				'resource_user_group_status' => 1,
				'resource_user_group_access_level' => 1
			));
			
			$group->setLastActivity();
			
			// adding cron for notifications
			$data = $this->resource->getResourceData();
			$resource_id = $this->resource->getResourceId();
			if ($data['resource_type'] == 2) {
				$event_time = strtotime($data['event_timestamp']);
				if ($event_time + 600 > time()) { // remind of max 10 mins. back
					Cron::addCron($event_time - $data['event_alert'], 2, $group_id, $data['resource_name']."\r\n\r\n".$data['resource_description'], 3, $resource_id);
				}
			}

			Activity::addActivity(Activity::GROUP_RESOURCE_ADDED, $group_id, 2);


			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage);
			$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id", "name/defaultresourcegrouplister")));
			$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id", "name/detailresourcegrouplister")));
			$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/defaultgroupresourcelister")));
			$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/detailgroupresourcelister")));

			$this->flashMessage(sprintf(_t("Group %s subscribed to this resource."),$group_name));
			
			// add activity to chat
			$group->addActivityToChat($resource_id, 3, 'subscribe');
		} else {
			$this->flashMessage(sprintf(_t("Group %s is already subscribed."),$group_name), 'error');
		}
		
		unset($group);

	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentTagform()
	{
		$resource_id = $this->resource->getResourceId();
		$form        = new NAppForm($this, 'tagform');
		$form->addComponent(new AddTagComponent("resource", $resource_id, _t("add new tag")), 'add_tag');
		return $form;
		
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentMemberlister($name)
	{
		$resource_id = $this->resource->getResourceId();
		$options     = array(
			'itemsPerPage' => 50,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER,
				ListerControlMain::LISTER_TYPE_GROUP
			),
			'filter' => array(
				'resource_id' => $resource_id
			),
			'refresh_path' => 'Resource:edit',
			'template_variables' => array(
				'administration' => true,
				'show_online_status' => true
			),
			'refresh_path_params' => array(
				'resource_id' => $resource_id
			),
			'cache_tags' => array("resource_id/$resource_id")
		);
		$control     = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentUpdateform()
	{
		$query = NEnvironment::getHttpRequest();
		$date = $query->getQuery("date");
		$all_day = $query->getQuery("all_day");
		$defaults = array();

		$user = NEnvironment::getUser()->getIdentity();
		if (!$user->hasRightsToCreate() && !$user->getAccessLevel() >= 2) {
			$this->flashMessage(_t("Your permissions only allow to create private resources."));
			$visibility = array(
						3 => _t('private event')
					);
		} else {
			$visibility = array(
						1 => _t('world'),
						2 => _t('registered'),
						3 => _t('subscribers')
					);
		}
		$language      = Language::getArray();
		if ($date) {
			$resource_type = array(2 => _t('event'));
		} else {
			$resource_type = Resource::getTypeArray();
		}
		if (!empty($this->resource)) {
			$resource_data = $this->resource->getResourceData();
			$resource_id   = $this->resource->getResourceId();
		}

		$form = new NAppForm($this, 'updateform');
		$form->addGroup();
		$form->addText('resource_name', _t('Name:'))->addRule(NForm::FILLED, _t('Resource name cannot be empty!'))->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Enter a name for the resource.'))->id("help-name"));
		$form->addSelect('resource_type', _t('Resource type:'), $resource_type)->addCondition(NForm::EQUAL, 2)->toggle("type_event")->endCondition()->addCondition(NForm::EQUAL, 3)->toggle("type_organization")->endCondition()->addCondition(NForm::EQUAL, 4)->toggle("type_information")->endCondition()->addCondition(NForm::EQUAL, 5)->toggle("type_media")->endCondition()->addCondition(NForm::EQUAL, 6)->toggle("type_other");
		$form->addSelect('resource_visibility_level', _t('Visibility:'), $visibility)->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Make the resource visible to everyone (world), only users of this website (registered) or to subscribers of this resource (subscribers).'))->id("help-name"));
		$form->addSelect('resource_language', _t('Language:'), $language)->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Select a language of this resource.'))->id("help-name"));
		
		$form->addTextArea('resource_description', _t('Description:'), 50, 10)->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Describe in few sentences what this resource is about.'))->id("help-name"));
		
		if (isset($resource_data['resource_type'])) {
			$form->addHidden('resource_type_exists');
			$defaults['resource_type_exists'] = $resource_data['resource_type'];
		} else {
			$defaults['resource_type'] = 6;
		}
		
		//different resource fields according to type
		//event
		if (!empty($resource_id) && $resource_data['resource_type'] == 2) {
			$form->addGroup()->setOption('container', NHtml::el('fieldset')->id("type_event"));
		} else {
			$form->addGroup()->setOption('container', NHtml::el('fieldset')->id("type_event")->style("display:none"));
		}
		$form->addTextArea('event_description', _t('Event description:'), 50, 10);
		$form['event_description']->getControlPrototype()->class('ckeditor-big');
		$form->addText('event_url', _t('URL to external source:'))->addCondition(~NForm::EQUAL, "")->addRule($form::REGEXP, _t("URL must start with http:// or https://!"), '/^http[s]?:\/\/.+/');
		$form->addCheckbox('event_allday', _t('All day event'));
		$defaults['event_allday'] = $all_day;
		$form->addText('event_timestamp', _t('Event time:'));
		$defaults['event_timestamp'] = $date;
		$form->addCheckbox('event_end', _t('Different end time'));
		$form->addText('event_timestamp_end', _t('Event end time:'));
				
		$event_alert_times = array(
			0 => _t('no alert'),
			60 => '1 min',
			300 => '5 min',
			600 => '10 min',
			900 => '15 min',
			1800 => '30 min',
			3600 => '1 h',
			3600*12 => '12 h',
			3600*24 => '24 h',
			3600*24*7 => _t('1 week')
		);
		$form->addSelect('event_alert', _t('Alarm:'), $event_alert_times);
		$form->addHidden('preset_time', $date);
		if ($date) $defaults['resource_visibility_level'] = 3;

		//organization
		if (!empty($resource_id) && $resource_data['resource_type'] == 3) {
			$form->addGroup()->setOption('container', NHtml::el('fieldset')->id("type_organization"));
		} else {
			$form->addGroup()->setOption('container', NHtml::el('fieldset')->id("type_organization")->style("display:none"));
		}
		$form->addTextArea('organization_information', _t('Information:'), 50, 10);
		$form['organization_information']->getControlPrototype()->class('ckeditor-big');
		$form->addText('organization_url', _t('URL to external source:'))->addCondition(~NForm::EQUAL, "")->addRule($form::REGEXP, _t("URL must start with http:// or https://!"), '/^http[s]?:\/\/.+/');
		
		//text information
		if (!empty($resource_id) && $resource_data['resource_type'] == 4) {
			$form->addGroup()->setOption('container', NHtml::el('fieldset')->id("type_information"));
		} else {
			$form->addGroup()->setOption('container', NHtml::el('fieldset')->id("type_information")->style("display:none"));
		}
		$form->addTextArea('text_information', _t('Text:'), 50, 10);
		$form['text_information']->getControlPrototype()->class('ckeditor-big');
		$form->addText('text_information_url', _t('URL to external source:'))->addCondition(~NForm::EQUAL, "")->addRule($form::REGEXP, _t("URL must start with http:// or https://!"), '/^http[s]?:\/\/.+/');
		
		//other (external link)
		if (!empty($resource_id) && $resource_data['resource_type'] == 6) {
			$form->addGroup()->setOption('container', NHtml::el('fieldset')->id("type_other"));
		} else {
			$form->addGroup()->setOption('container', NHtml::el('fieldset')->id("type_other")->style("display:none"));
		}
		$form->addText('other_url', _t('URL to external source:'))->addCondition(~NForm::EQUAL, "")->addRule($form::REGEXP, _t("URL must start with http:// or https://!"), '/^http[s]?:\/\/.+/');
		
		//audio/video link
		if (!empty($resource_id) && $resource_data['resource_type'] == 5) {
			$form->addGroup()->setOption('container', NHtml::el('fieldset')->id("type_media"));
		} else {
			$form->addGroup()->setOption('container', NHtml::el('fieldset')->id("type_media")->style("display:none"));
		}
		$media_types = array(
			'media_youtube' => 'Youtoube',
			'media_vimeo' => 'Vimeo',
			'media_soundcloud' => 'Soundcloud',
			'media_bambuser' => 'Bambuser'
		);
		$form->addSelect('media_type', _t('Media type:'), $media_types);
		$form->addText('media_link', _t('Media ID:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Please paste here the <u>underlined</u> part of the url for the media that you want to add:').'<br/>
	<ul>
		<li><b>YouTube:</b> https://www.youtube.com/watch?v=<u>xxxxx</u></li>
		<li><b>Vimeo:</b> http://vimeo.com/<u>xxxxx</u></li>
		<li><b>Soundcloud:</b> (click on "embed") ... https://api.soundcloud.com/tracks/<u>xxx</u>&amp;...</li>
		<li><b>Bambuser:</b> http://bambuser.com/v/<u>xxx</u></li>
	</ul>')->id("help-name"));
		$form->setCurrentGroup(NULL);
		
		if (empty($resource_id)) {
			$form->addSubmit('register', _t('Create new resource'));
		} else {
			$form->addSubmit('send', _t('Update'));
		}
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'updateformSubmitted'
		);
		if (!empty($resource_data)) {
			$defaults = array_merge($defaults, $resource_data);
		}
		$form->setDefaults($defaults);
 
		return $form;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function updateformSubmitted(NAppForm $form)
	{
		$user          = NEnvironment::getUser()->getIdentity();
		$values        = $form->getValues();

		if (strtotime($values['event_timestamp']) > strtotime($values['event_timestamp_end'])) $values['event_timestamp_end'] = $values['event_timestamp'];

		if ($values['event_allday']) {
			$values['event_timestamp'] = date("r", strtotime("midnight", strtotime($values['event_timestamp'])));
			$values['event_timestamp_end'] = date("r", strtotime("tomorrow midnight -1 minute", strtotime($values['event_timestamp_end'])));
		}
		
		$resource_type = (isset($values['resource_type'])) ? $values['resource_type'] : $values['resource_type_exists'];
		
		switch ($resource_type) {
			case 2: $values['organization_url']=''; $values['text_information_url']=''; $values['other_url']=''; break; //event
			case 3: $values['event_url']=''; $values['text_information_url']=''; $values['other_url']=''; break; // organization
			case 4: $values['organization_url']=''; $values['event_url']=''; $values['other_url']=''; break; // text
			case 6: $values['organization_url']=''; $values['text_information_url']=''; $values['event_url']=''; break; // website
		}

		$resource_data = array(
			'event_description' => $values['event_description'],
			'organization_information' => $values['organization_information'],
			'text_information' => $values['text_information'],
			'media_type' => $values['media_type'],
			'media_link' => $values['media_link'],
			'event_url' => $values['event_url'],
			'event_allday' => $values['event_allday'],
			'event_timestamp' => $values['event_timestamp'],
			'event_timestamp_end' => $values['event_timestamp_end'],
			'event_alert' => $values['event_alert'],
			'organization_url' => $values['organization_url'],
			'text_information_url' => $values['text_information_url'],
			'other_url' => $values['other_url']
		);
		$data = array(
			'resource_name' => $values['resource_name'],
			'resource_visibility_level' => $values['resource_visibility_level'],
			'resource_language' => $values['resource_language'],
			'resource_description' => $values['resource_description'],
			'resource_data' => json_encode($resource_data)
		);
		if (isset($form['register']) && $form['register']->isSubmittedBy()) {
			if (Auth::MODERATOR > $user->getAccessLevel()) {
				if (!$user->hasRightsToCreate() && $data['resource_visibility_level']!=3) {
					$this->flashMessage(_t("You have no permission to create public resources."), 'error');
					$data['resource_visibility_level']=3;
//					$this->redirect("Resource:default");
				}
			}
			$data['resource_type']   = $values['resource_type'];
			$data['resource_author'] = $user->getUserId();
		} elseif ($user->getAccessLevel() >= 2) {
			$data['resource_type']   = $values['resource_type'];		
		}
		$this->resource->setResourceData($data);
		if ($this->resource->save()) $this->flashMessage(_t("Resource updated"));
		$this->resource->setLastActivity();
		if (isset($form['register']) && $form['register']->isSubmittedBy()) {
			$this->resource->updateUser($user->getUserId(), array(
				'resource_user_group_access_level' => 3
			));
		}

		$resource_id = $this->resource->getResourceId();
		if (isset($form['register']) && $form['register']->isSubmittedBy()) {
			Activity::addActivity(Activity::RESOURCE_CREATED, $resource_id, 3, $user->getUserId());
		} else {
			Activity::addActivity(Activity::RESOURCE_UPDATED, $resource_id, 3);
		}
		
		// create screenshot
		switch ($resource_type) {
			case 2: if (isset($values['event_url']) && !empty($values['event_url'])) $url = $values['event_url']; break;
			case 3: if (isset($values['organization_url']) && !empty($values['organization_url'])) $url = $values['organization_url']; break;
			case 4: if (isset($values['text_information_url']) && !empty($values['text_information_url'])) $url = $values['text_information_url']; break;
			case 6: if (isset($values['other_url']) && !empty($values['other_url'])) $url = $values['other_url']; break;
			case 5: if (isset($values['media_link']) && !empty($values['media_link'])) {
						switch ($values['media_type']) {
							case 'media_soundcloud':  $url = ''; break;
							case 'media_youtube': $direct_url = 'https://img.youtube.com/vi/'.$values['media_link'].'/1.jpg'; break;
							case 'media_vimeo': $tmp = unserialize(@file_get_contents("http://vimeo.com/api/v2/video/".$values['media_link'].".php")); $direct_url = $tmp[0]['thumbnail_medium']; break;
							case 'media_bambuser': break;
						}
					}
			break;

		}

		$bad_extensions = array('.pdf');
		if (!empty($url) && in_array(strtolower(substr($url,-4,4)),$bad_extensions)) {
			$url = '';
		}

		if (isset($direct_url) && !empty($direct_url)) {
			// for all URLs that don't need a callback but where the resource is already avalible
			$this->saveDirectScreenshot($resource_id,$direct_url);
			$this->flashMessage(_t("Screenshot processing"));
		} elseif (isset($url) && !empty($url) && isset($this->grabzIt)) {
			// Grabz.it needs some time to process the screenshot
			$headers = @get_headers($url);
			if(!$headers || strpos($headers[0], '404 Not Found')!==false) {
				$this->flashMessage(_t("URL doesn't seem to exist"),'error');
			} else {
				// delete previous versions
				// We keep screenshots up to 1 day old, assuming that usually the views of websites don't change fundamentally during that time.
				$files = glob(WWW_DIR.'/images/cache/resource/'.$resource_id.'-screenshot-*.jpg');
				if ( is_array ( $files ) && count($files) ) {
					foreach($files as $file) {
						if (time() - filemtime($file) > 3600*24) {
							unlink($file);
						}
					}
				}

				$md5 = md5($url);							
				$ssl = NEnvironment::getVariable("GRABZIT_HTTPS");
				$s = &$_SERVER;
				// $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
				$host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : $s['SERVER_NAME'];
				// $callback = 'http'. (($ssl) ? 's' : '').'://'. $host. "/?do=savescreenshot&resource_id=". $resource_id. "&md5=". $md5;
				$callback = NEnvironment::getVariable("URI") . "/?do=savescreenshot&resource_id=". $resource_id. "&md5=". $md5;
				// check existence of screenshot to avoid unneccessary API calls
				$filepath = WWW_DIR.'/images/cache/resource/'.$resource_id.'-screenshot-'.$md5.'.jpg';
				if (!file_exists($filepath)) {
					try
					{
						$this->grabzIt->SetImageOptions($url,$md5);
						if ($this->grabzIt->Save($callback)) {
							$this->flashMessage(_t("Screenshot processing."));
						}
					}
					catch(GrabzItException $e)
					{
						$this->flashMessage(_t("Error processing screenshot."), "error");
					}
				} else $this->flashMessage(_t("Screenshot already exists."));
			}
					
		}

		// set reminder for all users
		if ($resource_type == 2) {
			$event_time = strtotime($values['event_timestamp']);
			if ($event_time + 3600 > time()) { // back-schedule max 60 mins.
				// get all subscribers
				$members = $this->resource->getAllMembers(array('enabled'=>1));
				if (count($members)) foreach ($members as $member) {
					Cron::addCron($event_time - $values['event_alert'], $member['member_type'], $member['member_id'], $values['resource_name']."\r\n\n".$values['resource_description'], 3, $resource_id);
				}
			} else {
				// event has been changed with time in the past: don't send alerts
				Cron::removeCron(0, 0, 3, $resource_id);
			}
			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage);
			$cache->clean(array(NCache::TAGS => array("name/events")));
		}
		
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id")));


		$this->redirect("Resource:edit", array(
			'resource_id' => $resource_id
		));
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function saveDirectScreenshot($resource_id = null, $url = null) {
		$md5 = md5($url);
		$filepath = WWW_DIR.'/images/cache/resource/'.$resource_id.'-screenshot-'.$md5.'.jpg';
		if (!file_exists($filepath)) {
			$image = file_get_contents($url);
			file_put_contents($filepath, $image);
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleResourceAdministration($resource_id, $values)
	{
		$resource = Resource::create($resource_id);
		
		$resource->setResourceData($values);
		$resource->save();
		$this->terminate();
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentDefaultresourcememberlister($name)
	{	
		$options = array(
			'itemsPerPage' => 5,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'template_variables' => array(
				'show_extended_columns' => true,
				'connection_columns' => true,
				'show_online_status' => true
			),
			'refresh_path' => 'Resource:default'
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'resource_id' => $session->data['object_id']
			);
			$options['cache_tags'] = array("resource_id/".$session->data['object_id']);
		}
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentDefaultresourcegrouplister($name)
	{
		
		$options = array(
			'itemsPerPage' => 5,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_GROUP
			),
			'template_body' => 'ListerControlMain_groups.phtml',
			'template_variables' => array(
				'show_extended_columns' => true,
				'connection_columns' => true
			),
			'refresh_path' => 'Resource:default'
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'resource_id' => $session->data['object_id']
			);
			$options['cache_tags'] = array("resource_id/".$session->data['object_id']);
		}
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentDetailresourcememberlister($name)
	{
		
		$options = array(
			'itemsPerPage' => 10,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER_DETAIL
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'template_variables' => array(
				'show_extended_columns' => true,
				'connection_columns' => true,
				'show_online_status' => true
			),
			'refresh_path' => 'Resource:default',
			'refresh_path_params' => array(
				'resource_id' => $this->resource->getResourceId()
			)
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'resource_id' => $session->data['object_id']
			);
			$options['cache_tags'] = array("resource_id/".$session->data['object_id']);
		}
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentDetailresourcegrouplister($name)
	{
		
		$options = array(
			'itemsPerPage' => 10,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_GROUP_DETAIL
			),
			'template_body' => 'ListerControlMain_groups.phtml',
			'template_variables' => array(
				'show_extended_columns' => true,
				'connection_columns' => true
			),
			'refresh_path' => 'Resource:default',
			'refresh_path_params' => array(
				'resource_id' => $this->resource->getResourceId()
			)
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'resource_id' => $session->data['object_id']
			);
			$options['cache_tags'] = array("resource_id/".$session->data['object_id']);
		}
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	
	/**
	*	Default page, left column
	**/
	protected function createComponentDefaultresourceresourcelister($name)
	{
		$session      = NEnvironment::getSession()->getNamespace($this->name);
		$selected_row = 0;
		if (!empty($session->data)) {
			$selected_row = $session->data['object_id'];
		}
		
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'ListerControlMain_resources.phtml',
			'refresh_path' => 'Resource:default',
			'template_variables' => array(
				'include_pairing' => true,
				'include_map' => true,
				'include_name' => true,
				'include_language' => true,
				'detail' => 'ajax',
				'selected_row' => $selected_row,
				'show_extended_columns' => true,
				'user_group_resource_page' => true
			)
		);
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleInsertTag($resource_id, $tag_id)
	{
		$this->resource = Resource::create($resource_id);
		if (!empty($this->resource)) {
			$resource_id = $this->resource->getResourceId();
			if (!empty($resource_id)) {
				$this->resource->insertTag($tag_id);
				Activity::addActivity(Activity::RESOURCE_UPDATED, $resource_id, 3);
				$this->resource->setLastActivity();
				$this->template->resource_tags = $this->resource->groupSortTags($this->resource->getTags());
				$this->invalidateControl('tagHandle');
			}
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function handleRemoveTag($resource_id, $tag_id)
	{
		$this->resource = Resource::create($resource_id);
		if (!empty($this->resource)) {
			$resource_id = $this->resource->getResourceId();
			if (!empty($resource_id)) {
				$this->resource->removeTag($tag_id);
				Activity::addActivity(Activity::RESOURCE_UPDATED, $resource_id, 3);
				$this->resource->setLastActivity();
				$this->template->resource_tags = $this->resource->groupSortTags($this->resource->getTags());
				$this->invalidateControl('tagHandle');
			}
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function handleDefaultPage($object_type, $object_id)
	{
		$this->resource               = Resource::create($object_id);
		$data                         = $this->resource->getResourceData();
		$this->template->default_data = array(
			'object_type' => $object_type,
			'object_id' => $object_id,
			'object_data' => $data
		);
		
		$session       = NEnvironment::getSession()->getNamespace($this->name);
		$session->data = array(
			'object_type' => $object_type,
			'object_id' => $object_id
		);
		
		if ($this->isAjax()) {
			$this->actionDefault();
            $this->invalidateControl('mainContent');
            $this->invalidateControl('mainMenu');
		} else {
		  	$this->redirect('this');
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function handleDetailPage($object_type, $object_id)
	{
		$this->resource               = Resource::create($object_id);
		$data                         = $this->resource->getResourceData();
		$this->template->default_data = array(
			'object_type' => $object_type,
			'object_id' => $object_id,
			'object_data' => $data
		);
		
		$session       = NEnvironment::getSession()->getNamespace($this->name);
		$session->data = array(
			'object_type' => $object_type,
			'object_id' => $object_id
		);
		
		if ($this->isAjax()) {
			$this->actionDefault($object_id);
            $this->invalidateControl('mainContent');
            $this->invalidateControl('mainMenu');
		} else {
		  	$this->redirect('this');
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleUserResourceInsert($user_id, $resource_id)
	{
		if (empty($resource_id) || empty($user_id)) {
			print "false";
			$this->terminate();
		} else {
			$user     = NEnvironment::getUser()->getIdentity();
			$resource = Resource::create($resource_id);
			
			if (!empty($resource)) {
				$resource_id = $resource->getResourceId();
				if (!empty($resource_id)) {
					if (!$resource->userIsRegistered($user_id)) {
						$resource->updateUser($user_id, array());
						$data = $resource->getResourceData();
						if ($data['resource_type'] == 2) {
							$event_time = strtotime($data['event_timestamp']);
							if ($event_time + 600 > time()) { // remind of max 10 mins. back
								Cron::addCron($event_time - $data['event_alert'], 1, $user_id, $data['resource_name']."\r\n\n".$data['resource_description'], 3, $resource_id);
							}
						}
					}
					Activity::addActivity(Activity::RESOURCE_SUBSCRIBED, $resource_id, 3, $user_id);
					$storage = new NFileStorage(TEMP_DIR);
					$cache = new NCache($storage);
					$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id", "name/detailresourcememberlister")));
					$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id", "name/defaultresourcememberlister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/detailuserresourcelister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/defaultuserresourcelister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepageresourcelister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagerecommendedresourcelister")));
					
					print "true";
				}
			}
		}
		
		$this->terminate();
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleUserResourceRemove($user_id, $resource_id)
	{
		if (empty($resource_id) || empty($user_id)) {
			print "false";
			$this->terminate();
		} else {
			$user     = NEnvironment::getUser()->getIdentity();
			$resource = Resource::create($resource_id);
			
			if (!empty($resource)) {
				$resource_id = $resource->getResourceId();
				if (!empty($resource_id)) {
					$resource->removeUser($user_id);
					Cron::removeCron(1, $user_id, 3, $resource_id);
					Activity::addActivity(Activity::RESOURCE_UNSUBSCRIBED, $resource_id, 3, $user_id);
					$storage = new NFileStorage(TEMP_DIR);
					$cache = new NCache($storage);
					$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id", "name/defaultresourcememberlister")));
					$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id", "name/detailresourcememberlister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/defaultuserresourcelister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/detailuserresourcelister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepageresourcelister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagerecommendedresourcelister")));

					print "true";
				}
			}
		}
		
		$this->terminate();
	}
	
	/**
	*	Chat on resource detail pages
	*/
	protected function createComponentChatlisterresource($name)
	{
		$options = array(
			'itemsPerPage' => 30,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'filter' => array(
				'type' => 8,
				'resource_id' => $this->resource->getResourceId(),
				'status' => 1
			),
			'template_body' => 'ListerControlMain_messages.phtml',
			'refresh_path' => 'Resource:default',
			'refresh_path_params' => array(
				'resource_id' => $this->resource->getResourceId()
			),
			'template_variables' => array(
				'hide_apply' => true,
				'hide_reset' => true,
				'moderation_enabled' => true,
				'resource_id' => $this->resource->getResourceId()
			),
			'cache_tags' => array("resource_id/".$this->resource->getResourceId())
		);

		$session = NEnvironment::getSession()->getNamespace($this->name);
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentMap($name)
	{
		$data    = array(
			array(
				'type' => 'resource',
				'id' => $this->resource->getResourceId()
			)
		);
		$control = new MapControl($this, $name, $data, array(
			'object' => array(
				'type' => 'resource',
				'id' => $this->resource->getResourceId()
			)
		));
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentMapedit($name)
	{
		$data    = array(
			array(
				'type' => 'resource',
				'id' => $this->resource->getResourceId()
			)
		);
		$control = new MapControl($this, $name, $data, array(
			'type' => 'edit',
			'object' => array(
				'type' => 'resource',
				'id' => $this->resource->getResourceId()
			)
		));
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function createComponentResourceadministrator($data_row)
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$resource_id = $session->data['object_id'];
		}
		$form = new NAppForm($this, 'resourceadministrator');
		$resource      = Resource::create($resource_id);
		$resource_data = $resource->getResourceData();
		$form->addCHeckbox('status');
		$form->addSubmit('send', _t('Update'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'adminResourceFormSubmitted'
		);
		$form->setDefaults(array(
			'status' => $resource_data['resource_status']
		));
		return $form;
	}

/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function adminResourceFormSubmitted(NAppForm $form)
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$resource_id = $session->data['object_id'];
			
			$values   = $form->getValues();
			$resource = Resource::create($resource_id);
			foreach ($values as $key => $value) {
				$values["resource_" . $key] = $value;
				unset($values[$key]);
			}
			
			$resource->setResourceData($values);
			$resource->save();
			$this->redirect("Resource:default");
		}
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentReportform()
	{
		$types = array(
			'0' => _t('This is not a real resource but spam.'),
			'1' => _t('This resource contains wrong information.'),
			'2' => _t('This resource violates the rules of conduct.')
		);
		$form  = new NAppForm($this, 'reportform');
		$form->addRadioList('report_type', _t('Reason:'), $types);
		$form->addTextarea('report_text', _t('Tell us why you report this resource, including examples:'))->addRule(NForm::FILLED, _t('Please give us some details.'));
		$form->addSubmit('send', _t('Send'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'reportformSubmitted'
		);
		
		return $form;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function reportformSubmitted(NAppForm $form)
	{
		$user = NEnvironment::getUser()->getIdentity();
		if (!empty($this->resource) && !empty($user)) {
			$values                  = $form->getValues();
			$resource_data           = array(
				'report_type' => $values['report_type'],
				'reported_object' => 'resource',
				'reported_id' => $this->resource->getResourceId()
			);
			$reported_resource_data  = $this->resource->getResourceData();
			
			$types = array(
			'0' => _t('(spam)'),
			'1' => _t('(error)'),
			'2' => _t('(inappropriate language)')
			);

			$data                    = array(
				'resource_name' => sprintf(_t("Report about resource %s, reason: %s"), $reported_resource_data['resource_name'], $types[$resource_data['report_type']]),
				'resource_type' => 7,
				'resource_visibility_level' => 3,
				'resource_description' => $values['report_text'],
				'resource_data' => json_encode($resource_data)
			);
			$data['resource_author'] = $user->getUserId();
			$resource                = Resource::Create();
			$resource->setResourceData($data);
			$resource->save();
			$resource_id = $resource->getResourceId();
			$this->flashMessage(_t("Your report has been received."));
		}
	}
	
	
	/**
	*	List of group members on group edit page
	**/
	protected function createComponentResourcesubscriberlister($name)
	{
		$user     = NEnvironment::getUser()->getIdentity();
		$resource_id = $this->resource->getresourceId();
		
		$options = array(
			'itemsPerPage' => 20,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'filter' => array(
				'resource_id' => $resource_id
			),
			'refresh_path' => 'resource:edit',
			'refresh_path_params' => array(
				'resource_id' => $resource_id
			),
			'template_variables' => array(
				'administration' => true,
                'hide_filter'=>true,
                'resource_edit_subscriber_lister'=>true
			),
			'cache_tags' => array("resource_id/$resource_id")
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function isAccessible()
	{
		if ($this->getAction() == "default") {
			return true;
		}
		return false;
	}


	/**
	 *	Triggered by clicking on a tag; sets filter to this tag
	 *	@param int $tag_id
	 *	@return void
	 */
	public function handleSearchTag($tag_id)
	{
		$filter = new ExternalFilter($this,'defaultresourceresourcelister');
		$session = NEnvironment::getSession()->getNamespace('defaultresourceresourcelister');
		unset($session->filterdata);
		
		$filterdata = array(
			'tags' => array(
				'all' => false,
				$tag_id => true
				)
			);
		
		$filter->clearFilterArray();
		
		if (NEnvironment::getVariable("GLOBAL_FILTER")) $filter->syncFilterArray($filterdata); else $session->filterdata=$filterdata;

		$this->redirect("Resource:default");
	}

	
	/**
	 *	For the moderation of chat messages
	 *	@param
	 *	@return
	*/
	public function handleRemoveMessage($message_id,$resource_id)
	{
		if (Auth::isAuthorized(3,$resource_id) < Auth::MODERATOR) {
			print 'false';
			$this->terminate();
		}		

		$message_o = Resource::create($message_id);
		if (!empty($message_o)) {
			$result = $message_o->remove_message(3, $resource_id);
		}

		if ($result) {
			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage);
			$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id", "name/chatlisterresource")));
		
			print 'true';
		} else {
			print 'false';
		}
		
		$this->terminate();	
	}

	
	/**
	 *	Removing screenshots on edit resource page
	 *	@param int $resource_id
	 *	@return
	*/
	public function handleRemoveScreenshot($resource_id)
	{
		if (Auth::isAuthorized(3,$resource_id) < Auth::ADMINISTRATOR) {
			$this->flashMessage("You are not allowed to edit this resource.","error");
			$this->redirect("Resource:default", $resource_id);
		}

		$files = glob(WWW_DIR.'/images/cache/resource/'.$resource_id.'-screenshot-*.jpg');
		$done = false;
		if ( is_array ( $files ) && count($files) ) {
			foreach($files as $file) {
				unlink($file);
				$done = true;
			}
		}

		if ($done) {
			$this->flashMessage("The screenshot has been removed.");
		}
		$this->redirect("Resource:default", $resource_id);
	}


}
