<?php
/**
 * mycitizen.net - Open source social networking for civil society
 *
 * @version 0.3 beta
 *
 * @author http://mycitizen.org
 * @copyright  Copyright (c) 2013 Burma Center Prague (http://www.burma-center.org)
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */
 

final class GroupPresenter extends BasePresenter
{
	protected $group;
	
	public function startup()
	{
		parent::startup();
	}
	
	public function actionDefault($group_id = null)
	{
	
	
		$query = NEnvironment::getHttpRequest();
		
		if ($query->getQuery("do")=='invitation') return;
		
		$this->template->load_js_css_tinymce = true;
	
		$user = NEnvironment::getUser()->getIdentity();
		if (!is_null($group_id)) {
			$this->setView('detail');
			$this->group = Group::create($group_id);
			$d           = $this->group->getGroupData();
			if (empty($d)) {
				$this->flashMessage(_("Given group does not exist."), 'error');
				$this->redirect("Group:default");
			}
			if (Auth::isAuthorized(Auth::TYPE_GROUP, $group_id) == 0) {
				$this->flashMessage(_("You are not allowed to view this group."), 'error');
				$this->redirect("Group:default");
				
			}
			
			$this->template->last_activity = $this->group->getLastActivity();
			
			$this->visit(2, $group_id);
			
			
			if ($this->group->hasPosition()) {
				$this->template->showmap = true;
			}

			$this->template->img = Group::getImage($group_id, 'img');
			$this->template->icon = Group::getImage($group_id, 'icon');
			$this->template->large_icon = Group::getImage($group_id, 'large_icon');
			
			$this->template->group_id = $group_id;
			$this->template->data     = $this->group->getGroupData();

			$this->template->hash     = $this->group->getGroupHash();
			
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
		
		if (isset($data) && isset($data['object_data']['group_language'])) {
			$languages = Language::getArray();
			$this->template->object_language = $languages[$data['object_data']['group_language']];
		}
		
	}
	
	public function actionCreate()
	{
		$user = NEnvironment::getUser()->getIdentity();
		if (Auth::MODERATOR > $user->getAccessLevel()) {
			if (!$user->hasRightsToCreate()) {
				$this->flashMessage(_("You have no permission to create groups."), 'error');
				$this->redirect("Group:default");
			}
		}
		$group = Group::create();
		
		$this->group = $group;
	}
	
	public function actionEdit($group_id = null)
	{
		$this->template->load_js_css_jcrop = true;
		$this->template->load_js_css_tree = true;

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
		
		$data                = $this->group->getAvatar();
		
		$this->template->img_src = $data;
		$this->template->icon = Group::getImage($this->template->group_id, 'icon');
		$this->template->large_icon = Group::getImage($this->template->group_id, 'large_icon');
		
		$f                   = finfo_open();
		
		$size_x = 0;
		$size_y = 0;

		if(!empty($data)) {
			$img_r = imagecreatefromstring(base64_decode($data));
		
			$size_x = imagesx($img_r);
			$size_y = imagesy($img_r);
	
			if ($size_x == 0 || $size_y == 0) {
			
				unset($this->template->img_src);
				
				$group->removeAvatar();
			
			} elseif ($size_x < 80 || $size_y < 100) {
		
				$this->flashMessage(sprintf(_("The image is too small. Minimum size is %s."), "80px x 100px"), 'error');
				
				$group->removeAvatar();
				$group->removeIcons();
			
				unset($this->template->img_src);
			
			} elseif ($size_x > 160 || $size_y > 200 ) {

				$this->template->image_too_large = true;
				$this->flashMessage(_("Your image still needs to be resized before you can continue!"));
				$group->removeIcons();
			
			} elseif (abs(round($size_x/$size_y*500/4)-100) > 10) {
			// more than 10% deviation from ideal ratio
				
				$this->template->image_props_wrong = true;
				$this->flashMessage(_("Your image still needs to be cropped to the right dimensions before you can continue!"));
				$group->removeIcons();

			}
		}		
		
		$image_type = finfo_buffer($f, base64_decode($data), FILEINFO_MIME_TYPE);
		
		$this->template->mime_type = $image_type;	
	}
	
	protected function createComponentChatform()
	{
		$form = new NAppForm($this, 'chatform');
		$form->addTextarea('message_text', ''); // circumvented by TinyMCE ->addRule(NForm::FILLED, _('Please enter some text.'));
		$form['message_text']->getControlPrototype()->class('tinymce-small');
		$form->addProtection(_('Error submitting form.'));
		
		$form->addSubmit('send', _('Send'));
		
		$form->onSubmit[] = array(
			$this,
			'chatformSubmitted'
		);
		
		return $form;
	}
	
	public function chatformSubmitted(NAppForm $form)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$values                  = $form->getValues();
		$resource                = Resource::create();
		$data                    = array();
		$data['resource_author'] = $user->getUserId();
		$data['resource_type']   = 8;
		//      $data['resource_name'] = $values['resource_name'];
		$data['resource_data']   = json_encode(array(
			'message_text' => $values['message_text']
		));
		$resource->setResourceData($data);
		$resource->save();
		$this->group->setLastActivity();
		$resource->updateUser($user->getUserId(), array(
			'resource_user_group_access_level' => 1
		));
		$resource->updateGroup($this->group->getGroupId(), array(
			'resource_user_group_access_level' => 1
		));
		$this->redirect("Group:default", array(
			'group_id' => $this->group->getGroupId(),
			'chatlistergroup-page' => 1,
			'do' => 'chatlistergroup-changePage'
		));
		
	}
	
	protected function createComponentTagform()
	{
		$group_id = $this->group->getGroupId();
		$form     = new NAppForm($this, 'tagform');
		$form->addComponent(new AddTagComponent("group", $group_id, _("add new tag")), 'add_tag');
		return $form;
	}
	
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
                'membership_detail'=>true
			),
			'refresh_path' => 'Group:default'
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'group_id' => $session->data['object_id'],
				'purpose' => 'members'
			);
		}
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
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
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
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
                'detail_connections' => true 
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
		}
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
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
                'detail_connections' => true 
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
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
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
				'group_id' => $group_id
			),
			'refresh_path' => 'Group:edit',
			'refresh_path_params' => array(
				'group_id' => $group_id
			),
			'template_variables' => array(
				'administration' => true,
                'hide_filter'=>true
			)
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
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	protected function createComponentUpdateform()
	{
		$visibility = Visibility::getArray();
		$language   = Language::getArray();
		if (!empty($this->group)) {
			$group_data = $this->group->getGroupData();
			$group_id   = $this->group->getGroupId();
		}
		$form = new NAppForm($this, 'updateform');
		$form->addText('group_name', _('Name:'))->addRule(NForm::FILLED, _('Group name cannot be empty!'))->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('Enter a name for the group.'))->id("help-name"));
		$form->addSelect('group_visibility_level', _('Visibility:'), $visibility)->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('Make the group visible to everyone (world), only users of this website (registered) or to members of this group (members).'))->id("help-name"));
		$form->addSelect('group_language', _('Language:'), $language)->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('Select a language that will be used in this group for communication.'))->id("help-name"));
		$form->addTextArea('group_description', _('Description:'), 50, 10)->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('Describe in few sentences what this group is about.'))->id("help-name"));
		$form->addFile('group_avatar', _('Upload group image:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(sprintf(_('Avatars are small images that will be visible with your group. Here you can upload an avatar for your group (min. %s, max. %s). In the next step you can crop it.'), "120x150px","1500x1500px"))->id("help-name"))->addCondition(NForm::FILLED)->addRule(NForm::MIME_TYPE, _('Image must be in JPEG or PNG format.'), 'image/jpeg,image/png')->addRule(NForm::MAX_FILE_SIZE, sprintf(_('Maximum image size is %s'),"512kB"), 512 * 1024);
		
		if ($group_data['group_visibility_level'] == 3) {
			$form->addText('group_hash', _('Group key:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('Enter a key that will be used for inviting members into this group. Use letters, numbers and "-", with a minimum lenght of 5.'))->id("help-name"))->addRule($form::REGEXP, _("Only letters, numbers and '-', with a minimum lenght of 5."), '/^[a-zA-Z0-9\-]{5,}$/');
		}
		
		if (empty($group_id)) {
			$form->addSubmit('register', _('Create new group'));
			
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
		
				$form->addSubmit('send', _('Update'));
			}
		}
		
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'updateformSubmitted'
		);
		if (!empty($group_data)) {
			$form->setDefaults($group_data);
		}
		return $form;
	}
	
	public function updateformSubmitted(NAppForm $form)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$values = $form->getValues();
		
		if (isset($form['register']) && $form['register']->isSubmittedBy()) {
			if (Auth::MODERATOR > $user->getAccessLevel()) {
				if (!$user->hasRightsToCreate()) {
					$this->flashMessage(_("You have no permission to create groups."), 'error');
					$this->redirect("Group:default");
				}
			}
			$values['group_author'] = $user->getUserId();	
		}
		
		if ($values['group_avatar']->getTemporaryFile() != "") {
		
			$size= getimagesize($values['group_avatar']->getTemporaryFile());
			
			if ($size[0]>1500 || $size[1]>1500) {
				$this->flashMessage(sprintf(_("Image is too big! Max. size is for upload is %s"), "1500x1500"),'error');
			} elseif ($size[0]<80 || $size[1]<100) {
				$this->flashMessage(sprintf(_("The image is too small! Min. size for upload is %s"), "80x100"),'error');
			} else {
				$values['group_portrait'] = base64_encode(file_get_contents($values['group_avatar']->getTemporaryFile()));
			}
		}
		unset($values['group_avatar']);

		$this->group->setGroupData($values);
		if ($this->group->save()) $this->flashMessage(_("Group updated"));
		$this->group->setLastActivity();

		if (isset($form['register']) && $form['register']->isSubmittedBy()) {
			$this->group->updateUser($user->getUserId(), array(
				'group_user_access_level' => 3
			));
		}
		$group_id = $this->group->getGroupId();

		$this->redirect("Group:edit", array(
			'group_id' => $group_id
		));
	}
	
	public function handleGroupAdministration($group_id, $values)
	{
		$group = Group::create($group_id);
		
		$group->setGroupData($values);
		$group->save();
		$this->terminate();
	}

	
	/**
	*	Default page, left column
	**/
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
	
	public function handleInsertTag($group_id, $tag_id)
	{
		$this->group = Group::create($group_id);
		if (!empty($this->group)) {
			$group_id = $this->group->getGroupId();
			if (!empty($group_id)) {
				
				$this->group->insertTag($tag_id);
				$this->group->setLastActivity();
				$this->template->group_tags = $this->group->groupSortTags($this->group->getTags());
				$this->invalidateControl('tagHandle');
			}
		}
	}
	
	public function handleRemoveTag($group_id, $tag_id)
	{
		$this->group = Group::create($group_id);
		if (!empty($this->group)) {
			$group_id = $this->group->getGroupId();
			if (!empty($group_id)) {
				$this->group->removeTag($tag_id);
				$this->group->setLastActivity();
				$this->template->group_tags = $this->group->groupSortTags($this->group->getTags());
				$this->invalidateControl('tagHandle');
			}
		}
	}
	
	public function handleGroupUserInsert($group_id, $user_id)
	{
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
					print "true";
				}
			}
		}
		
		$this->terminate();
	}
	
	public function handleGroupUserRemove($group_id, $user_id)
	{
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
					print "true";
				}
			}
		}
		
		$this->terminate();
	}
	
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
		
		$this->redirect("this");
		//$this->presenter->terminate();
	}
	
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
				)
			),
			'template_body' => 'ChatLister_body.phtml',
			'refresh_path' => 'Group:default',
			'refresh_path_params' => array(
				'group_id' => $this->group->getGroupId()
			),
            'template_variables' => array(
                    'hide_filter'=>1,
                    'reply_enabled'=>1
                    )
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
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
	
### needed?
	public function createComponentGroupadministrator($data_row)
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$group_id = $session->data['object_id'];
		}
		$form = new NAppForm($this, 'groupadministrator');
		
		$group      = Group::create($group_id);
		$group_data = $group->getGroupData();
		$form->addCheckbox('status');
		$form->addSubmit('send', _('Update'));
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'adminGroupFormSubmitted'
		);
		$form->setDefaults(array(
			'status' => $group_data['group_status']
		));
		return $form;
	}
	
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
	
	protected function createComponentReportform()
	{
		$types = array(
			'0' => _('This is not a real group but spam.'),
			'1' => _('This group contains wrong information.'),
			'2' => _('This group violates the rules of conduct.')
		);
		$form  = new NAppForm($this, 'reportform');
		$form->addRadioList('report_type', _('Reason:'), $types);
		$form->addTextarea('report_text', _('Tell us why you report this group, including examples:'))->addRule(NForm::FILLED, _('Please give us some details.'));
		$form->addSubmit('send', _('Send'));
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'reportformSubmitted'
		);
		
		return $form;
	}
	
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
			'0' => _('(spam)'),
			'1' => _('(error)'),
			'2' => _('(inappropriate language)')
			);
			
			$data                    = array(
				'resource_name' => sprintf(_("Report about group %s, reason: %s"), $reported_group_data['group_name'], $types[$resource_data['report_type']]),
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
			$this->flashMessage(_("Your report has been received."));
		}
	}
	
	protected function createComponentNotifyform()
	{
		$types = array(
			'local' => _('Send them a local message.'),
			'email' => _('Send them an email.')
		);
		$form  = new NAppForm($this, 'notifyform');
		$form->addRadioList('notification_type', _('How to contact them').':', $types);
		$form->addTextarea('notification_text', _('Text').':')->addRule(NForm::FILLED, _('Please enter some text.'));
		$form->addSubmit('send', _('Send'));
		$form->addProtection(_('Error submitting form.'));
		$form->setDefaults(array('notification_type'=>'local'));
		$form->onSubmit[] = array(
			$this,
			'notifyformSubmitted'
		);
		
		return $form;
	}
	
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
				StaticModel::addCron(time() + 60, 1, $user_a['user_id'], sprintf(_('A message from your group %s'),'"'.$group_name.'" ('.$URI.'/group/?group_id='.$this->group->getGroupId().')').":\r\n\r\n".$values['notification_text'], 2, $this->group->getGroupId());
			} else {
				$resource                          = Resource::create();
				$data                              = array();
				$data['resource_author']           = $user->getUserId();
				$data['resource_type']             = 1;
				$data['resource_visibility_level'] = 3;
				$data['resource_name'] = '<group message>';
				$data['resource_data']             = json_encode(array(
					'message_text' => '<p><b>'.sprintf(_('A message from your group %s'),'<a href="'.$URI.'/group/?group_id='.$this->group->getGroupId().'">"'.$group_name.'"</a>').":</b></p>\n<p>".nl2br($values['notification_text']).'</p>'
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
				unset($resource);
//				unset($user_o);
			}	
			
		}
		$this->flashMessage(_("Your message has been sent to the members of this group."));
		$this->redirect("this");
	}
	
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
			$form->addMultiSelect('resource_id', '', array('0'=>"Not subscribed to any resource."));
			return;
		}
		
		$form->addMultiSelect('resource_id', '', $resource_selection);
		$form->addSubmit('send', _('Unsubscribe'));
		$form->addProtection(_('Error submitting form.'));
		
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

		$this->redirect("Group:default", array(
				'group_id' => $this->group->getGroupId()
			));
		
	}
	
	/**
	*	Doing the actual unsubscription from resource for unsubscriberesourceformSubmitted().
	*
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
			StaticModel::removeCron(2, $group_id, 3, $resource_id);
			
			$this->flashMessage(sprintf(_("Group %s unsubscribed from resource."),$group_name));
		} else {
			$this->flashMessage(sprintf(_("Group %s has not been subscribed to resource."),$group_name), 'error');
		}
		
		unset($resource);
	
	}
	
	public function handleRemoveAvatar($group_id = null)
	{
		if (Auth::isAuthorized(Auth::TYPE_GROUP, $group_id) < 2) {
			$this->terminate();
		}
		
		$group = Group::Create($group_id);

		if (empty($group)) {
			$this->terminate();
		}
		if (!empty($group)) {
			BasePresenter::removeImage($group_id,2);
			$group->removeAvatar();
			$group->removeIcons();
		}
		$this->redirect("this");
	}


	public function handleCrop() {
		$query = NEnvironment::getHttpRequest();

		$x = $query->getQuery("x");
		$y = $query->getQuery("y");
		$w = $query->getQuery("w");
		$h = $query->getQuery("h");
		$group_id = $query->getQuery("group_id");


		if ($group_id == 0 || Auth::isAuthorized(Auth::TYPE_GROUP, $group_id) < 2) {
			
			$this->flashMessage(_("You are not allowed to edit this group."), 'error');
			$this->redirect("Group:default",$group_id);
		}
				
		// remove from cache
		BasePresenter::removeImage($group_id,2);

		$group = new Group($group_id);

		if (!empty($group)) {

			$data = base64_decode($group->getAvatar());
		
			if (isset($data)) {
		
				// target sizes
				$avatar_w = 160;
				$avatar_h = 200;
				$large_icon_w = 40;
				$large_icon_h = 50;
				$icon_w = 20;
				$icon_h = 25;

				$avatar = base64_encode(NImage::fromString($data)->crop($x, $y, $w, $h)->resize($avatar_w, $avatar_h)->sharpen()->toString(IMAGETYPE_JPEG,80));
				$large_icon = base64_encode(NImage::fromString($data)->crop($x, $y, $w, $h)->resize($large_icon_w, $large_icon_h)->sharpen()->toString(IMAGETYPE_JPEG,90));
				$icon = base64_encode(NImage::fromString($data)->crop($x, $y, $w, $h)->resize($icon_w, $icon_h)->sharpen()->toString(IMAGETYPE_JPEG,90));
				
				$values = array (
					'group_portrait' => $avatar,
					'group_largeicon' => $large_icon,
					'group_icon' => $icon,
					);
				
				$group->setGroupData($values);
				$group->save();
				
			}
		}
		
		// save to cache
		$group->saveImage($group_id);
		$this->flashMessage(_("Finished cropping and resizing."));
		$this->redirect("Group:edit",$group_id);
		
		$this->redirect("Group:edit",$group_id);
	}


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
			
			$this->flashMessage(_("You are already a member."), 'error');
			
			$this->redirect("Group:default",$group_id);
			
		}
		

		$hash = $group->getGroupHash();
		
		if (empty($hash)) {
		
			$this->flashMessage(_("This group doesn't have a key."), 'error');
			
			$this->redirect("Group:default");
		
		} elseif ($key != $hash ) {
			
			$this->flashMessage(_("You have entered a wrong key."), 'error');
			
			sleep(5);
		
			$this->redirect("Group:default");
		
		} else {
		
			$group->setMember($user_id);
		
			$this->flashMessage(_("You have joined the group."));
		
			$this->redirect("Group:default",$group_id);
		
		}
	}

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

		$filter->clearFilterArray($filterdata);
		
		if (NEnvironment::getVariable("GLOBAL_FILTER")) $filter->syncFilterArray($filterdata); else $session->filterdata=$filterdata;

		$filter->setFilterArray($filterdata);

		$this->redirect("Group:default");
	}


	/**
	*	For the moderation of chat messages
	*/
	public function handleRemoveMessage($message_id,$group_id)
	{
		//if (Auth::MODERATOR<=Auth::isAuthorized($object_type,$object_id)) $this->terminate();
		//(NEnvironment::getUser()->getIdentity()->getAccessLevel()<2) $this->terminate();
		

		$resource = Resource::create($message_id);
		if (!empty($resource)) {
			$resource->remove_message($group_id);
		}

		
		$this->terminate();	
	}
	
}
