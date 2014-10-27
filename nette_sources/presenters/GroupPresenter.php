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
 

final class GroupPresenter extends BasePresenter
{
	protected $group;

	/**
	 *	Startup in BasePresenter
	 *	@param void
	 *	@return void
	 */
	public function startup()
	{
		parent::startup();
	}


	/**
	 *	Prepares the template for the User Default and User Detail screens
	 *	@param int $group_id
	 *	@return void
	 */
	public function actionDefault($group_id = null)
	{

		$this->template->load_google_maps = true;

		$query = NEnvironment::getHttpRequest();
		
		if ($query->getQuery("do")=='invitation') return;
		
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';
		
		$user = NEnvironment::getUser()->getIdentity();
		if (!is_null($group_id)) {
			$this->setView('detail');
			$this->group = Group::create($group_id);
			$d           = $this->group->getGroupData();
			if (empty($d)) {
				$this->flashMessage(_t("Given group does not exist."), 'error');
				$this->redirect("Group:default");
			}
			if (Auth::isAuthorized(Auth::TYPE_GROUP, $group_id) == 0) {
				$this->flashMessage(_t("You are not allowed to view this group."), 'error');
				$this->redirect("Group:default");
				
			}
			
			$this->template->last_activity = $this->group->getLastActivity();
			$this->template->format_date = _t("j.n.Y");
			
			$this->visit(2, $group_id);

			if ($this->group->hasPosition()) {
				$this->template->showmap = true;
			}

			$this->template->img = Group::getImage($group_id, 'img');
			$this->template->icon = Group::getImage($group_id, 'icon');
			$this->template->large_icon = Group::getImage($group_id, 'large_icon');
			
			$this->template->group_id = $group_id;
			$this->template->data     = $this->group->getGroupData();
			// $this->template->hash     = group->getGroupHash();
			$hash = $this->group->getGroupHash();
			if (!empty($hash)) {
				$this->template->key_link   = NEnvironment::getVariable("URI").$this->link('Group:default', array('group_id'=>$group_id)).'&do=invitation&key='.$this->group->getGroupHash();
			}
			
			$owner                    = $this->group->getOwner();
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
			$session       = NEnvironment::getSession()->getNamespace($this->name);
			$session->data = array(
				'object_id' => $group_id
			);
			
			$this->template->group_tags = $this->group->groupSortTags($this->group->getTags());
		}
		
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$data['object_data']             = Group::create($session->data['object_id'])->getGroupData();
			
			if (!empty($data['object_data'])) {
				$data['object_data']['group_id'] = $session->data['object_id'];

				$this->template->img = Group::getImage($session->data['object_id'], 'img');
				$this->template->icon = Group::getImage($session->data['object_id'], 'icon');
				$this->template->large_icon = Group::getImage($session->data['object_id'], 'large_icon');
			
				$group_object = Group::create($session->data['object_id']);
				$user         = NEnvironment::getUser()->getIdentity();

				if (!empty($group_object)) {
					$owner = $group_object->getOwner();
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
				
				}
				if (!empty($user)) {
					if ($group_object->userIsRegistered($user->getUserId())) {
						$data['object_data']['logged_user_member'] = 1;
					} else {
						$data['object_data']['logged_user_member'] = 0;
					}
					$this->template->logged_user = $user->getUserId();
				} else {
					$data['object_data']['logged_user_member'] = -1;
				}
				$this->template->default_data = $data;
			}
		}
		
		if (isset($data) && isset($data['object_data']['group_language'])) {
			$languages = Language::getArray();
			if (isset($languages[$data['object_data']['group_language']])) $this->template->object_language = $languages[$data['object_data']['group_language']];
		}	
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionCreate()
	{
		$user = NEnvironment::getUser()->getIdentity();
		if (Auth::MODERATOR > $user->getAccessLevel()) {
			if (!$user->hasRightsToCreate()) {
				$this->flashMessage(_t("You have no permission to create groups."), 'error');
				$this->redirect("Group:default");
			}
		}
		$group = Group::create();

		$this->group = $group;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionEdit($group_id = null)
	{
		$this->template->load_js_css_jcrop = true;
		$this->template->load_js_css_tree = true;
		$this->template->load_google_maps = true;

		$query = NEnvironment::getHttpRequest();
		$do = $query->getQuery("do");
		if ($do == 'makebigicon' || $do == 'makeicon' || $do == 'crop') return;

		$user  = NEnvironment::getUser()->getIdentity();
		$group = Group::create($group_id);
		
		if (!empty($group)) {
			$this->group = $group;
			$group_id    = $this->group->getGroupId();
			if (!empty($group_id)) {
				$access = Auth::isAuthorized(Auth::TYPE_GROUP, $group_id);
				if ($access < 2) {
					$this->redirect("Group:default");
				}
				$this->template->group_id = $group_id;
			}
			
				
		} else {
			$this->redirect("Group:default");
		}

		if (!empty($this->group)) {
			$this->template->group_tags = $this->group->groupSortTags($this->group->getTags());
		}

		$this->template->group_id = $group->getGroupId();
		
		$this->template->hash = $this->group->getGroupHash();

		$size_x = 0;
		$size_y = 0;

		$image = Image::createimage($group->getGroupId(), 2);
		if($image !== false) {
			$this->template->icon = $image->renderImg('icon');
			$this->template->large_icon = $image->renderImg('large_icon');
			$this->template->mime_type = $image->mime_type;
			$size_x = $image->width;
			$size_y = $image->height;
			$this->template->factor = 1;
			$this->template->min_size_x = 120;
			$this->template->min_size_y = 150;
	
			if ($size_x == 0 || $size_y == 0) {
				unset($this->template->img_src);
				$group->removeAvatar();
			
			} elseif ($size_x < 120 || $size_y < 150) {
				$this->flashMessage(sprintf(_t("The image is too small. Minimum size is %s."), "80px x 100px"), 'error');
				$group->removeAvatar();
				$group->removeIcons();			
			} elseif ($size_x > 160 || $size_y > 200 ) {
				$this->template->image_too_large = true;
				$this->flashMessage(_t("Your image still needs to be resized before you can continue!"));
				$group->removeIcons();
				
				// check if image is too large to be cropped on screen
				$max_x = 600;
				$max_y = 600;
				if ($size_x > $max_x || $size_y > $max_y) {
					$factor = ($size_x / $max_x > $size_y / $max_y ) ? $size_x / $max_x : $size_y / $max_y;
					$this->template->factor = $factor;
					$this->template->min_size_x = round(120 / $factor);
					$this->template->min_size_y = round(150 / $factor);
					$this->template->img_src = $image->resize($max_x, $max_y)->src();
				} else {
					$this->template->img_src = $image->src();
				}
			} elseif (abs(round($size_x/$size_y*500/4)-100) > 10) {
			// more than 10% deviation from ideal ratio
				$this->template->image_props_wrong = true;
				$this->flashMessage(_t("Your image still needs to be cropped to the right dimensions before you can continue!"));
				$this->template->img_src = $image->src();
				$group->removeIcons();
			} else {
				$this->template->img_src = $image->src();
			}
		} else {
			$group->removeIcons();
		}
		
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentTagform()
	{
		$group_id = $this->group->getGroupId();
		$form     = new NAppForm($this, 'tagform');
		$form->addComponent(new AddTagComponent("group", $group_id, _t("add new tag")), 'add_tag');
		return $form;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentDefaultgroupuserlister($name)
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
                'hide_filter'=>false,
                'membership_detail'=>true,
				'show_online_status' => true
			),
			'refresh_path' => 'Group:default'
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'group_id' => $session->data['object_id'],
				'purpose' => 'members'
			);
			$options['cache_tags'] = array("group_id/".$session->data['object_id']);
		}
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentDefaultgroupresourcelister($name)
	{
		$options = array(
			'itemsPerPage' => 5,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'ListerControlMain_resources.phtml',
			'template_variables' => array(
				'show_extended_columns' => true,
				'connection_columns' => true
			),
			'refresh_path' => 'Group:default'
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'group_id' => $session->data['object_id']
			);
			$options['cache_tags'] = array("group_id/".$session->data['object_id']);
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentDetailgroupuserlister($name)
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
                'hide_filter'=> true,
                'membership_detail'=> true,
                'detail_connections' => true,
				'show_online_status' => true 
			),
			'refresh_path' => 'Group:default',
			'refresh_path_params' => array(
				'group_id' => $this->group->getGroupId()
			),
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'group_id' => $session->data['object_id'],
				'purpose' => 'members'
			);
			$options['cache_tags'] = array("group_id/".$session->data['object_id']);
		}
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentDetailgroupresourcelister($name)
	{
		$options = array(
			'itemsPerPage' => 10,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE_DETAIL
			),
			'template_body' => 'ListerControlMain_resources.phtml',
			'template_variables' => array(
				'show_extended_columns' => true,
                'hide_filter'=>true,
				'connection_columns' => true,
                'detail_connections' => true ,
				'show_online_status' => true
			),
			'refresh_path' => 'Group:default',
			'refresh_path_params' => array(
				'group_id' => $this->group->getGroupId()
			),
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'group_id' => $session->data['object_id']
			);
			$options['cache_tags'] = array("group_id/".$session->data['object_id']);
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentUserlister($name)
	{
		$name= 'groupmemberlister';
		$user     = NEnvironment::getUser()->getIdentity();
		$group_id = $this->group->getGroupId();
		
		$options = array(
			'itemsPerPage' => 20,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'filter' => array(
				'group_id' => $group_id,
				
			),
			'refresh_path' => 'Group:edit',
			'refresh_path_params' => array(
				'group_id' => $group_id
			),
			'template_variables' => array(
				'administration' => true,
                'hide_filter'=>true,
				'show_online_status' => true
			),
			'cache_tags' => array("group_id/$group_id")
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	*	List of group members on group edit page
	*/
	protected function createComponentGroupmemberlister($name)
	{
		$user     = NEnvironment::getUser()->getIdentity();
		$group_id = $this->group->getGroupId();
		
		$options = array(
			'itemsPerPage' => 20,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'filter' => array(
				'group_id' => $group_id
			),
			'refresh_path' => 'Group:edit',
			'refresh_path_params' => array(
				'group_id' => $group_id
			),
			'template_variables' => array(
				'administration' => true,
                'hide_filter'=>true,
                'group_edit_member_lister'=>true
			),
			'cache_tags' => array("group_id/$group_id")
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentUpdateform()
	{
		$visibility = array(
						1 => 'world',
						2 => 'registered',
						3 => 'members'
					); // Visibility::getArray();
		$language   = Language::getArray();
		if (!empty($this->group)) {
			$group_data = $this->group->getGroupData();
			$group_data['group_hash'] = $this->group->getGroupHash();
			$group_id   = $this->group->getGroupId();
		}
		$form = new NAppForm($this, 'updateform');
		$form->addText('group_name', _t('Name:'))->addRule(NForm::FILLED, _t('Group name cannot be empty!'))->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Enter a name for the group.'))->id("help-name"));
		$form->addSelect('group_visibility_level', _t('Visibility:'), $visibility)->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Make the group visible to everyone (world), only users of this website (registered) or to members of this group (members).'))->id("help-name"));
		$form->addSelect('group_language', _t('Language:'), $language)->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Select a language that will be used in this group for communication.'))->id("help-name"));
		$form->addTextArea('group_description', _t('Description:'), 50, 10)->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Describe in few sentences what this group is about.'))->id("help-name"));
		$form->addFile('group_avatar', _t('Upload group image:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(sprintf(_t('Avatars are small images that will be visible with your group. Here you can upload an avatar for your group (min. %s, max. %s). In the next step you can crop it.'), "120x150px","2500x2500px"))->id("help-name"))->addCondition(NForm::FILLED)->addRule(NForm::MIME_TYPE, _t('Image must be in JPEG or PNG format.'), 'image/jpeg,image/png')->addRule(NForm::MAX_FILE_SIZE, sprintf(_t('Maximum image size is %s'),"2MB"), 2 * 1024 * 1024);
		
		if (isset($group_data['group_visibility_level']) && $group_data['group_visibility_level'] == 3) {
			$form->addText('group_hash', _t('Group key:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Enter a key that will be used for inviting members into this group. Use letters, numbers and "-", with a minimum lenght of 5.'))->id("help-name"))->addRule($form::REGEXP, _t("Only letters, numbers and '-', with a minimum lenght of 5."), '/^[a-zA-Z0-9\-]{5,}$/');
		}
		
		if (empty($group_id)) {
			$form->addSubmit('register', _t('Create new group'));
			
		} else {
		
			$img = $this->group->getAvatar();
			$size_x = 0;
			$size_y = 0;

			if(!empty($img)) {
				$img_r = imagecreatefromstring(base64_decode($img));
		
				$size_x = imagesx($img_r);
				$size_y = imagesy($img_r);
			}
		
			if ($size_x<=160 || $size_y<=200) {
		
				$form->addSubmit('send', _t('Update'));
			}
		}
		
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'updateformSubmitted'
		);
		if (!empty($group_data)) {
			$form->setDefaults($group_data);
		}
		return $form;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function updateformSubmitted(NAppForm $form)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$values = $form->getValues();
		
		if (isset($form['register']) && $form['register']->isSubmittedBy()) {
			if (Auth::MODERATOR > $user->getAccessLevel()) {
				if (!$user->hasRightsToCreate()) {
					$this->flashMessage(_t("You have no permission to create groups."), 'error');
					$this->redirect("Group:default");
				}
			}
			$values['group_author'] = $user->getUserId();
		}
		
		if ($values['group_avatar']->getTemporaryFile() != "") {
		
			$size = getimagesize($values['group_avatar']->getTemporaryFile());
			
			if ($size[0]>2500 || $size[1]>2500) {
				$this->flashMessage(sprintf(_t("Image is too big! Max. size is for upload is %s"), "2500x2500"),'error');
			} elseif ($size[0]<80 || $size[1]<100) {
				$this->flashMessage(sprintf(_t("The image is too small! Min. size for upload is %s"), "80x100"),'error');
			} else {
				$values['group_portrait'] = base64_encode(file_get_contents($values['group_avatar']->getTemporaryFile()));
			}
		}
		unset($values['group_avatar']);

		$this->group->setGroupData($values);
		if ($this->group->save()) $this->flashMessage(_t("Group updated"));
		$this->group->setLastActivity();

		if (isset($form['register']) && $form['register']->isSubmittedBy()) {
			$this->group->updateUser($user->getUserId(), array(
				'group_user_access_level' => 3
			));
		}
		$group_id = $this->group->getGroupId();
		if (isset($form['register']) && $form['register']->isSubmittedBy()) {
			Activity::addActivity(Activity::GROUP_CREATED, $group_id, 2, $user->getUserId());
		} else {
			Activity::addActivity(Activity::GROUP_UPDATED, $group_id, 2);
		}

		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		$cache->clean(array(NCache::TAGS => array("group_id/$group_id")));

		$this->redirect("Group:edit", array(
			'group_id' => $group_id
		));
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleGroupAdministration($group_id, $values)
	{
		if (Auth::isAuthorized(2, $group_id) < Auth::MODERATOR) die('no permission');
		
		$group = Group::create($group_id);
		
		$group->setGroupData($values);
		$group->save();
		$this->terminate();
	}

	
	/**
	 *	Default page, left column
	 */
	protected function createComponentGrouplister($name)
	{
		$session      = NEnvironment::getSession()->getNamespace($this->name);
		$selected_row = 0;
		if (!empty($session->data)) {
			$selected_row = $session->data['object_id'];
		}
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array( 
				ListerControlMain::LISTER_TYPE_GROUP
			),
			'template_body' => 'ListerControlMain_groups.phtml',
			'components' => 'global',
			'refresh_path' => 'Group:default',
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
	public function handleInsertTag($group_id, $tag_id)
	{
		if (Auth::isAuthorized(2, $group_id) < Auth::MODERATOR) die('no permission');
		
		$this->group = Group::create($group_id);
		if (!empty($this->group)) {
			$group_id = $this->group->getGroupId();
			if (!empty($group_id)) {
				$this->group->insertTag($tag_id);
				Activity::addActivity(Activity::GROUP_UPDATED, $group_id, 2);
				$this->group->setLastActivity();
				$this->template->group_tags = $this->group->groupSortTags($this->group->getTags());
				$this->invalidateControl('tagHandle');
			}
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleRemoveTag($group_id, $tag_id)
	{
		if (Auth::isAuthorized(2, $group_id) < Auth::MODERATOR) die('no permission');

		$this->group = Group::create($group_id);
		if (!empty($this->group)) {
			$group_id = $this->group->getGroupId();
			if (!empty($group_id)) {
				$this->group->removeTag($tag_id);
				Activity::addActivity(Activity::GROUP_UPDATED, $group_id, 2);
				$this->group->setLastActivity();
				$this->template->group_tags = $this->group->groupSortTags($this->group->getTags());
				$this->invalidateControl('tagHandle');
			}
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleGroupUserInsert($group_id, $user_id)
	{
		if (Auth::isAuthorized(2, $group_id) < Auth::USER || Auth::isAuthorized(1, $user_id) < Auth::MODERATOR) die('no permission');
		
		if (empty($group_id) || empty($user_id)) {
			print "false";
			$this->terminate();
		} else {
			$user  = NEnvironment::getUser()->getIdentity();
			$group = Group::create($group_id);
			
			if (!empty($group)) {
				$group_id = $group->getGroupId();
				if (!empty($group_id)) {
					//insert here
					if (!$group->userIsRegistered($user_id)) {
						$group->updateUser($user_id, array());
					}
					Activity::addActivity(Activity::GROUP_JOINED, $group_id, 2, $user_id);
					$storage = new NFileStorage(TEMP_DIR);
					$cache = new NCache($storage);
					$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/detailgroupuserlister")));
					$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/defaultgroupuserlister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/detailusergrouplister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/defaultusergrouplister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagegrouplister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagerecommendedgrouplister")));

					// add activity to chat
					$group->addActivityToChat($user_id, 1, 'join');
		
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
	public function handleGroupUserRemove($group_id, $user_id)
	{
		if (Auth::isAuthorized(2, $group_id) < Auth::MODERATOR || Auth::isAuthorized(1, $user_id) < Auth::MODERATOR) die('no permission');

		if (empty($group_id) || empty($user_id)) {
			print "false";
			$this->terminate();
		} else {
			$user  = NEnvironment::getUser()->getIdentity();
			$group = Group::create($group_id);
			
			if (!empty($group)) {
				$group_id = $group->getGroupId();
				if (!empty($group_id)) {
					//insert here
					$group->removeUser($user_id);
					Activity::addActivity(Activity::GROUP_LEFT, $group_id, 2, $user_id);
					$storage = new NFileStorage(TEMP_DIR);
					$cache = new NCache($storage);
					$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/detailgroupuserlister")));
					$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/defaultgroupuserlister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/detailusergrouplister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/defaultusergrouplister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagegrouplister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagerecommendedgrouplister")));

					// add activity to chat
					$group->addActivityToChat($user_id, 1, 'leave');

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
	public function handleDefaultPage($object_type, $object_id)
	{
		$this->group                  = Group::create($object_id);
		$data                         = $this->group->getGroupData();
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
		$this->group                  = Group::create($object_id);
		$data                         = $this->group->getGroupData();
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
	protected function createComponentChatlistergroup($name)
	{
		$options = array(
			'itemsPerPage' => 30,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'filter' => array(
				'type' => 8,
				'all_members_only' => array(
					array(
						'type' => 2,
						'id' => $this->group->getGroupId()
					)
				),
				'status' => 1
			),
			'template_body' => 'ChatLister_body.phtml',
			'refresh_path' => 'Group:default',
			'refresh_path_params' => array(
				'group_id' => $this->group->getGroupId()
			),
            'template_variables' => array(
                    'hide_filter'=>1,
                    'reply_enabled'=>1
                    ),
            'cache_tags' => array("group_id/".$this->group->getGroupId(), "name/chatlistergroup")
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
				'type' => 'group',
				'id' => $this->group->getGroupId()
			)
		);
		$control = new MapControl($this, $name, $data, array(
			'object' => array(
				'type' => 'group',
				'id' => $this->group->getGroupId()
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
		$data = array(
			array(
				'type' => 'group',
				'id' => $this->group->getGroupId()
			)
		);
		
		$control = new MapControl($this, $name, $data, array(
			'type' => 'edit',
			'object' => array(
				'type' => 'group',
				'id' => $this->group->getGroupId()
			)
		));
		return $control;
	}


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function createComponentGroupadministrator($data_row)
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$group_id = $session->data['object_id'];
		} else {
			return;
		}
		$form = new NAppForm($this, 'groupadministrator');
		
		$group      = Group::create($group_id);
		$group_data = $group->getGroupData();
		$form->addCheckbox('status');
		$form->addSubmit('send', _t('Update'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'adminGroupFormSubmitted'
		);
		$form->setDefaults(array(
			'status' => $group_data['group_status']
		));
		return $form;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function adminGroupFormSubmitted(NAppForm $form)
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$group_id = $session->data['object_id'];
			
			$values = $form->getValues();
			$group  = Group::create($group_id);
			
			foreach ($values as $key => $value) {
				$values["group_" . $key] = $value;
				unset($values[$key]);
			}
			
			$group->setGroupData($values);
			$group->save();
			$this->redirect("Group:default");
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
			'0' => _t('This is not a real group but spam.'),
			'1' => _t('This group contains wrong information.'),
			'2' => _t('This group violates the rules of conduct.')
		);
		$form  = new NAppForm($this, 'reportform');
		$form->addRadioList('report_type', _t('Reason:'), $types);
		$form->addTextarea('report_text', _t('Tell us why you report this group, including examples:'))->addRule(NForm::FILLED, _t('Please give us some details.'));
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
		if (!empty($this->group) && !empty($user)) {
			$values                  = $form->getValues();
			$resource_data           = array(
				'report_type' => $values['report_type'],
				'reported_object' => 'group',
				'reported_id' => $this->group->getGroupId()
			);
			$reported_group_data     = $this->group->getGroupData();
			
			$types = array(
			'0' => _t('(spam)'),
			'1' => _t('(error)'),
			'2' => _t('(inappropriate language)')
			);
			
			$data                    = array(
				'resource_name' => sprintf(_t("Report about group %s, reason: %s"), $reported_group_data['group_name'], $types[$resource_data['report_type']]),
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
	 *	Prepares the form to send group messages to all group members
	 *	@param void
	 *	@return object
	 */
	protected function createComponentNotifyform()
	{
		$types = array(
			'local' => _t('Send them a local message.'),
			'email' => _t('Send them an email.')
		);
		$form  = new NAppForm($this, 'notifyform');
		$form->addRadioList('notification_type', _t('How to contact them').':', $types);
		$form->addTextarea('notification_text', _t('Text').':')->addRule(NForm::FILLED, _t('Please enter some text.'));
		$form->addSubmit('send', _t('Send'));
		$form->addProtection(_t('Error submitting form.'));
		$form->setDefaults(array('notification_type'=>'local'));
		$form->onSubmit[] = array(
			$this,
			'notifyformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	Sends a group message to all group members
	 *	@param object $form
	 *	@return void
	 */
	public function notifyformSubmitted(NAppForm $form)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		if (Auth::isAuthorized(Auth::TYPE_GROUP, $this->group->getGroupId()) < Auth::MODERATOR) {
			$this->terminate();
		}
		
		$values = $form->getValues();
		$filter = array(
				'enabled' => 1
			);
		$users_a = $this->group->getAllUsers($filter);
		$group_name = Group::getName($this->group->getGroupId());
		$URI = NEnvironment::getVariable("URI");
		foreach ($users_a as $user_a) {
			if ($values['notification_type'] == 'email') {
				Cron::addCron(time(), 1, $user_a['user_id'], sprintf(_t('A message from your group %s'),'"'.$group_name.'" ('.$URI.'/group/?group_id='.$this->group->getGroupId().')').":\r\n\r\n".$values['notification_text'], 2, $this->group->getGroupId());
			} else {
				$resource                          = Resource::create();
				$data                              = array();
				$data['resource_author']           = $user->getUserId();
				$data['resource_type']             = 1;
				$data['resource_visibility_level'] = 3;
				$data['resource_name'] = '<group message>';
				$data['resource_data']             = json_encode(array(
					'message_text' => '<p><b>'.sprintf(_t('A message from your group %s'),'<a href="'.$URI.'/group/?group_id='.$this->group->getGroupId().'">"'.$group_name.'"</a>').":</b></p>\n<p>".nl2br($values['notification_text']).'</p>'
				));
				$resource->setResourceData($data);
				$resource->save();
//				$user_o = User::create($user_a['user_id']);
				if ($user->getUserId() == $user_a['user_id'] ) {
					$resource->updateUser($user_a['user_id'], array(
						'resource_user_group_access_level' => 1,
						'resource_opened_by_user' => 1
					));
				} else {
					$resource->updateUser($user_a['user_id'], array(
						'resource_user_group_access_level' => 1,
						'resource_opened_by_user' => 0
					));				
				}
//				unset($resource);
//				unset($user_o);
			}	
			
		}
		$this->flashMessage(_t("Your message has been sent to the members of this group."));
		$this->redirect("this");
	}


	/**
	 *	Only the default page is fully accessible for guests
	 *	@param void
	 *	@return boolean
	 */
	public function isAccessible()
	{
		if ($this->getAction() == "default") {
			return true;
		}
		return false;
	}


	/**
	*	Form on group detail pages that lists the subscribed resources and offers to unsubscribe.
	*
	*/
	protected function createComponentUnsubscriberesourceform()
	{
		$resource_selection = array();

		$form = new NAppForm($this, 'unsubscriberesourceform');		
		$user = NEnvironment::getUser()->getIdentity();
		$group_resources = $this->group->getSubscribedResources();
		
		foreach($group_resources as $group_resource) {
			$resource_id = $group_resource['resource_id'];
			// check permission
			if (Auth::isAuthorized(Auth::TYPE_RESOURCE, $resource_id) >= 1) {
				$resource_selection[$resource_id] = $group_resource['resource_name'];
			}
		}

		if (empty($resource_selection))  {
			$form->addMultiSelect('resource_id', '', array('0'=>_t("Not subscribed to any resource.")));
			return;
		}
		
		$form->addMultiSelect('resource_id', '', $resource_selection);
		$form->addSubmit('send', _t('Unsubscribe'));
		$form->addProtection(_t('Error submitting form.'));
		
		$form->onSubmit[] = array(
			$this,
			'unsubscriberesourceformSubmitted'
		);
		
		return $form;
	}


	/**
	*	Processing and receiving return values to unsubscribe from resource.
	*
	*/
	public function unsubscriberesourceformSubmitted(NAppForm $form)
	{
	
		if (Auth::isAuthorized(Auth::TYPE_GROUP, $this->group->getGroupId()) < Auth::MODERATOR) {
			$this->terminate();
		}
		
		$user = NEnvironment::getUser()->getIdentity();
		$values = $form->getValues();
		$group_id = $this->group->getGroupId();
		
		if (is_array($values['resource_id'])) {

			foreach ($values['resource_id'] as $resource_id) {
			
				$this->unsubscribefromresource($resource_id);
			
			}
		
		} else {

			$this->unsubscribefromresource($values['resource_id']);
		
		}

		$this->group->setLastActivity();

		// add activity to chat
		$this->group->addActivityToChat($values['resource_id'], 3, 'unsubscribe');

		$this->redirect("Group:default", array(
				'group_id' => $this->group->getGroupId()
			));
		
	}


	/**
	 *	Doing the actual unsubscription from resource for unsubscriberesourceformSubmitted().
	 *	@param int $resource_id
	 */
	private function unsubscribefromresource($resource_id) {

		$group_id = $this->group->getGroupId();
		$data = $this->group->getGroupData();
		$group_name = $data['group_name'];

		if (empty($resource_id) || $resource_id<1) {
			$this->terminate();
		}
		
		$resource = new Resource($resource_id);
		
		if (Auth::isAuthorized(Auth::TYPE_RESOURCE, $resource_id) < 2) {
			$this->terminate();
		}
		
		if ($resource->groupIsRegistered($group_id)) {
			$resource->removeGroup($group_id);
			
			// remove cron
			Cron::removeCron(2, $group_id, 3, $resource_id);
			
			Activity::addActivity(Activity::GROUP_RESOURCE_REMOVED, $group_id, 2);

			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage);
			$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/defaultgroupresourcelister")));
			$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/detailgroupresourcelister")));
			$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id", "name/detailresourcegrouplister")));
			$cache->clean(array(NCache::TAGS => array("resource_id/$resource_id", "name/defaultresourcegrouplister")));
	
			$this->flashMessage(sprintf(_t("Group %s unsubscribed from resource."),$group_name));
		} else {
			$this->flashMessage(sprintf(_t("Group %s has not been subscribed to resource."),$group_name), 'error');
		}
		
		unset($resource);
	
	}


	/**
	*	Form on group detail pages that lists the friends and offers to invite them.
	*
	*/
	protected function createComponentInvitefriendsform()
	{
		$resource_selection = array();

		$form = new NAppForm($this, 'invitefriendsform');		
		$user = NEnvironment::getUser()->getIdentity();
		$filter = array(
				'enabled' => 1
			);
			
		$members = array();
		$members_tmp = $this->group->getAllUsers($filter);
		foreach($members_tmp as $member) {
			$members[] = $member['user_id'];
		}
		$friends = $user->getFriends();
		foreach($friends as $friend_id => $friend_username) {
			if (!in_array($friend_id, $members)) {
				$friend_selection[$friend_id] = $friend_username;
			}
		}

		if (empty($friend_selection))  {
			$form->addMultiSelect('friend_id', '', array('0'=>_t("All friends are members.")));
			return;
		}
		
		$form->addMultiSelect('friend_id', '', $friend_selection);
		$form->addSubmit('send', _t('Invite'));
		$form->addProtection(_t('Error submitting form.'));
		
		$form->onSubmit[] = array(
			$this,
			'invitefriendsformSubmitted'
		);
		
		return $form;
	}


	/**
	*	Processing and receiving return values to invite friends.
	*
	*/
	public function invitefriendsformSubmitted(NAppForm $form)
	{
	
		if (Auth::isAuthorized(Auth::TYPE_GROUP, $this->group->getGroupId()) < Auth::USER) {
			$this->redirect('this');
		}
		
		$user = NEnvironment::getUser()->getIdentity();
		$values = $form->getValues();
		$group_id = $this->group->getGroupId();
		
		if (is_array($values['friend_id'])) {
			foreach ($values['friend_id'] as $friend_id) {
				$this->invitefriend($friend_id);
			}
		} else {
			$this->invitefriend($values['friend_id']);
		}

		$this->redirect("Group:default", array(
				'group_id' => $this->group->getGroupId()
			));
		
	}


	/**
	 *	Doing the actual invitation of friends for invitefriendsformSubmitted().
	 *	@param int $friend_id
	 */
	private function invitefriend($friend_id) {

		$group_id = $this->group->getGroupId();
		$data = $this->group->getGroupData();
		$group_name = $data['group_name'];

		if (empty($friend_id) || $friend_id<1) {
			$this->terminate();
		}
		
		$user_id = NEnvironment::getUser()->getIdentity()->getUserId();
		$sender_name = User::getFullName($user_id);
		$resource                          = Resource::create();
		$data                              = array();
		$data['resource_author']           = $user_id;
		$data['resource_type']             = 1;
		$data['resource_visibility_level'] = 3;
		$data['resource_name'] = '<invitation>';
		$data['resource_data']             = json_encode(array(
			'message_text' => '<p>'.sprintf(_t('Your friend %s invites you to the group %s.'),
			$sender_name, '<a href="'.$URI.'/group/?group_id='.$this->group->getGroupId().'">"'.$group_name.'"</a>').'</p>'
		));

		$resource->setResourceData($data);
		$resource->save();
		if ($user_id == $friend_id ) {
			$resource->updateUser($friend_id, array(
				'resource_user_group_access_level' => 1,
				'resource_opened_by_user' => 1
			));
		} else {
			$resource->updateUser($friend_id, array(
				'resource_user_group_access_level' => 1,
				'resource_opened_by_user' => 0
			));				
		}
		Activity::addActivity(Activity::FRIEND_INVITED, $user_id, 1, $friend_id);
		$friend_name = User::getFullName($friend_id);
		$this->flashMessage(_t("An invitation was sent to your friend %s.", $friend_name));
	
	}


	/**
	 *	Removes the group avatar
	 *	@param int $group_id
	 *	@return void
	 */
	public function handleRemoveAvatar($group_id = null)
	{
		if (Auth::isAuthorized(2, $group_id) < Auth::MODERATOR) die('no permission');

		if (Auth::isAuthorized(Auth::TYPE_GROUP, $group_id) < 2) {
			$this->terminate();
		}
		
		$group = Group::Create($group_id);

		if (empty($group)) {
			$this->terminate();
		}
		if (!empty($group)) {
			$image = Image::createimage($group_id,2);
			if ($image !== false) {
				$image->remove_cache();
				$group->removeAvatar();
				$group->removeIcons();
			}
		}
		$this->redirect("this");
	}


	/**
	 *	Crops and resizes the group avatar
	 *	@param int $group_id
	 *	@param int $x
	 *	@param int $y
	 *	@param int $w
	 *	@param int $h
	 *	@return
	*/
	public function handleCrop() {
		$request = NEnvironment::getHttpRequest();
		$factor = $request->getQuery("factor");
		if ($factor < 1) {
			$factor = 1;
		}
		$x = $request->getQuery("x") * $factor;
		$y = $request->getQuery("y") * $factor;
		$w = $request->getQuery("w") * $factor;
		$h = $request->getQuery("h") * $factor;
		$group_id = $request->getQuery("group_id");

		
		if ($group_id == 0 || Auth::isAuthorized(Auth::TYPE_GROUP, $group_id) < 2) {
			
			$this->flashMessage(_t("You are not allowed to edit this group."), 'error');
			$this->redirect("Group:default",$group_id);
		}
				
		// remove from cache
		$image = Image::createimage($group_id,2);
		$image->remove_cache();
		$image->crop($x, $y, $w, $h);
		$image->save_data();
		$result = $image->create_cache();
		if ($result !== true) $this->flashMessage($result, 'error');
		$this->flashMessage(_t("Finished cropping and resizing."));
		Activity::addActivity(Activity::GROUP_UPDATED, $group_id, 2);

		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		$cache->clean(array(NCache::TAGS => array("group_id/$group_id")));

		$this->redirect("Group:edit",$group_id);

	}


	/**
	 *	Checks if key to closed group is correct and user may join
	 *	@param int $group_id
	 *	$param string $key
	 *	@return
	 */
	public function handleInvitation() {
	
		$query = NEnvironment::getHttpRequest();
		$group_id = $query->getQuery("group_id");
		$key = trim($query->getQuery("key"));
		$user = NEnvironment::getUser()->getIdentity();
		$user_id = $user->getUserId();

		if ($group_id == 0 || $user_id == 0) {
			$this->redirect("Group:default");
		}

		$group = new Group($group_id);
		if ($group->isMember($user_id)) {
			$this->flashMessage(_t("You are already a member."), 'error');
			$this->redirect("Group:default",$group_id);
		}

		$hash = $group->getGroupHash();
		if (empty($hash)) {
			$this->flashMessage(_t("This group doesn't have a key."), 'error');
			$this->redirect("Group:default");
		} elseif ($key != $hash ) {
			$this->flashMessage(_t("You have entered a wrong key."), 'error');
			sleep(5);
			$this->redirect("Group:default");
		} else {
			$group->setMember($user_id);
			$this->flashMessage(_t("You have joined the group."));
		
			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage);
			$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/detailgroupuserlister")));
			$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/defaultgroupuserlister")));
			$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/detailusergrouplister")));
			$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/defaultusergrouplister")));
			$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagegrouplister")));
			$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagerecommendedgrouplister")));

			// add activity to chat
			$group->addActivityToChat($user_id, 1, 'join');
		
			$this->redirect("Group:default",$group_id);
		}
	}


	/**
	 *	Triggered by clicking on a tag; sets filter to this tag
	 *	@param int $tag_id
	 *	@return void
	 */
	public function handleSearchTag($tag_id)
	{
		if (NEnvironment::getVariable("GLOBAL_FILTER")) $name='defaultresourceresourcelister' ; else $name='grouplister';
		$filter = new ExternalFilter($this,$name);
		$session = NEnvironment::getSession()->getNamespace($name);
		unset($session->filterdata);
		
		$filterdata = array(
			'tags' => array(
				'all' => false,
				$tag_id => true
				)
			);

		$filter->clearFilterArray();
		
		if (NEnvironment::getVariable("GLOBAL_FILTER")) $filter->syncFilterArray($filterdata); else $session->filterdata=$filterdata;

		$filter->setFilterArray($filterdata);

		$this->redirect("Group:default");
	}


	/**
	 *	For the moderation of chat messages
	 *	@param int $message_id
	 *	@param int $group_id
	 *	@return
	*/
	public function handleRemoveMessage($message_id, $group_id)
	{
		if (Auth::isAuthorized(2, $group_id) < Auth::MODERATOR) die('no permission');

		// check if it is a message
		$resource_type = Resource::getResourceType($message_id);
		if ($resource_type != 8) {
			echo "false";
			$this->terminate();
		}

		if (Resource::removeMessage($message_id)) {
			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage);
			$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/chatwidget")));
			echo "true";
		} else {
			echo "false";
		}

		$this->terminate();	
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function handleSubmitGroupChat($message_text = '',$group_id)
	{

		if (Auth::isAuthorized(2, $group_id) < Auth::USER) die('no permission');

		$user = NEnvironment::getUser()->getIdentity();
		$group = new Group($group_id);
		
		if ($group_id == 0 || !$group->isMember($user->getUserId())) {
			die("false");
		}
		
		$resource                = Resource::create();
		$data                    = array();
		$data['resource_author'] = $user->getUserId();
		$data['resource_type']   = 8;
		$data['resource_data']   = json_encode(array(
			'message_text' => $message_text
		));
		$resource->setResourceData($data);
		$check = $resource->check_doublette($data, $user->getUserId(), 1);
		if ($check === true) {
			die("false");
		}
		$resource->save();
		
		$group->setLastActivity();
		
		Activity::addActivity(Activity::GROUP_CHAT, $group_id, 2);
		
		$resource->updateUser($user->getUserId(), array(
			'resource_user_group_access_level' => 1
		));
		$resource->updateGroup($group_id, array(
			'resource_user_group_access_level' => 1
		));

		### clear cache for $group_id
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		$cache->clean(array(NCache::TAGS => array("group_id/$group_id", "name/chatwidget")));

		die("true");
	}
	
}
