<?php
/**
 * mycitizen.net - Open source social networking for civil society
 *
 * @version 0.2 beta
 *
 * @author http://mycitizen.org
 *
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */
 

final class UserPresenter extends BasePresenter
{
	protected $user;
	protected $user_id;
	protected $control_key;
	public function startup()
	{
		parent::startup();
	}
	
	/**
	*		Prepare data for display in User Default and Detail template
	*/
	public function actionDefault($user_id = null)
	{
		$image_type = null;
		$data = null;
		
		if (!is_null($user_id)) {
			$this->template->load_js_css_tinymce = true;
			$this->setView('detail');
			$this->user = User::create($user_id);
			$d          = $this->user->getUserData();
			if (empty($d)) {
				$this->flashMessage(_("This user doesn't exist."), 'error');
				$this->redirect("User:default");
			}
			if (Auth::isAuthorized(Auth::TYPE_USER, $user_id) == 0) {
				$this->flashMessage(_("You are not allowed to view this user."), 'error');
				$this->redirect("User:default");
			}
			
			$this->template->last_activity = $this->user->getLastActivity();
			
			$this->visit(1, $user_id);
			$this->template->user_id = $user_id;
			$this->template->data    = $this->user->getUserData();
			if ($this->user->hasPosition()) {
				$this->template->showmap = true;
			}
			$session       = NEnvironment::getSession()->getNamespace($this->name);
			$session->data = array(
				'object_id' => $user_id
			);
			
			$this->template->access_level = $this->user->getAccessLevel();
			
			$this->template->img = User::getImage($user_id, 'img');
			$this->template->icon = User::getImage($user_id, 'icon');
			$this->template->large_icon = User::getImage($user_id, 'large_icon');
			
			$this->template->user_tags = $this->user->groupSortTags($this->user->getTags());
		}
		
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$data['object_data']            = User::create($session->data['object_id'])->getUserData();
			$data['object_data']['user_id'] = $session->data['object_id'];
			
			$friend_object = User::create($session->data['object_id']);
			$user = NEnvironment::getUser()->getIdentity();
			if (!empty($user) && !empty($friend_object) && $user->getUserId() != $friend_object->getUserId()) {
				$friend_user_relationship = $friend_object->friendsStatus($user->getUserId());
				$user_friend_relationship = $user->friendsStatus($friend_object->getUserId());
				
				$data['object_data']['user_friend_relationship'] = $user_friend_relationship;
				$data['object_data']['friend_user_relationship'] = $friend_user_relationship;
				$this->template->logged_user = $user->getUserId();
			} else {
				$data['object_data']['user_friend_relationship'] = -1;
			}
			$this->template->default_data = $data;
			
			if (!empty($this->user)) {
				if($this->user->thatsMe()) $this->template->thats_me=true;
			} elseif (!empty($friend_object)) {
				if($friend_object->thatsMe()) $this->template->thats_me=true;
			}
			
			$this->template->img = User::getImage($session->data['object_id'], 'img');
			$this->template->icon = User::getImage($session->data['object_id'], 'icon');
			$this->template->large_icon = User::getImage($session->data['object_id'], 'large_icon');
			
			$this->template->mime_type = $image_type;
		}
		
		if (isset($data)) {
			$languages = Language::getArray();
			$this->template->object_language = $languages[$data['object_data']['user_language']];
		}
		
	}
	
	public function actionCreate()
	{
		
	}
	
	public function actionConfirm($user_id, $control_key)
	{
		if (User::finishRegistration($user_id, $control_key)) {
			$this->flashMessage(_("Registration has been succesfull. You can now sign in."));
			
			$this->redirect('User:login');
		} else {
			$this->flashMessage(_("Registration couldn't be finished! Link is not active anymore"), 'error');
			
			$this->redirect('Homepage:default');
		}
	}
	
	public function actionEmailchange($user_id, $control_key)
	{
		if (User::finishEmailChange($user_id, $control_key)) {
			$this->flashMessage(_("Email has been succesfully changed."));
			
			$this->redirect('Homepage:default');
		} else {
			$this->flashMessage(_("Email couldn't be changed! Link is not active anymore"), 'error');
			
			$this->redirect('Homepage:default');
		}
	}
	
	public function emailchangeAdmin($user_id,$user_email)
	{
		if (User::finishEmailChangeAdmin($user_id,$user_email)) {
			$this->flashMessage(_("Email has been succesfully changed."));
			
			$this->redirect("this");
		} else {
			$this->flashMessage(_("Email couldn't be changed!"), 'error');
			
			$this->redirect("this");
		}
	}
	
	public function actionMessages()
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		if (empty($user)) {
			$this->redirect('Homepage:default');
		}
		$friends = $user->getFriends();
		if (count($friends) < 1) {
			$this->template->nofriends = true;
		}
		$this->template->load_js_css_tinymce = true;
	}
	
	public function actionEdit($user_id = null)
	{
		$this->template->load_js_css_jcrop = true;
		$this->template->load_js_css_tree = true;
		
		$query = NEnvironment::getHttpRequest();
		$do = $query->getQuery("do");
		if ($do == 'makebigicon' || $do == 'makeicon' || $do == 'crop') return;
	
		$user = NEnvironment::getUser()->getIdentity();
		if (empty($user)) {
			$this->redirect("Homepage:default");
		}
		if (!is_null($user_id) ) {
			if (Auth::isAuthorized(1,$user->getUserId()) >= 2) {
			
				$this->user = User::create($user_id);
				$user       = $this->user;
				if (empty($user)) {
					$this->redirect("Homepage:default");
				}
			
				$this->template->administrated_user = $user->getUserId();
			} else {
				$this->redirect("User:default");
			}
		} else {
			$this->redirect("User:default");
		}
		
		if (!empty($this->user)) {
			$tags = $this->user->groupSortTags($user->getTags());
			$this->template->user_tags = $tags;
		}
		$this->template->user_id = $user->getUserId();
		$data = $user->getAvatar();
		
		$this->template->img_src = $data;

		$size_x = 0;
		$size_y = 0;

		if(!empty($data)) {
			$f = finfo_open();	

			$image_type = finfo_buffer($f, base64_decode($data), FILEINFO_MIME_TYPE);
		
			$this->template->mime_type = $image_type;	

			$img_r = imagecreatefromstring(base64_decode($data));
		
			$size_x = imagesx($img_r);
			$size_y = imagesy($img_r);

			if ($size_x == 0 || $size_y == 0) {
			
				unset($this->template->img_src);
				
				$user->removeAvatar();
				$user->removeIcons();
			
			} elseif ($size_x < 80 || $size_y < 100) {

				$this->flashMessage(_("The image is too small. Minimum size is 80px x 100px."), 'error');
				
				$user->removeAvatar();
				$user->removeIcons();
			
				unset($this->template->img_src);
			
			} elseif ($size_x > 160 || $size_y > 200 ) {

				$this->template->image_too_large = true;
				$this->flashMessage(_("Your image still needs to be resized before you can continue!"));
				$user->removeIcons();
			
			} elseif (abs(round($size_x/$size_y*500/4)-100) > 10) {
			// more than 10% deviation from ideal ratio
				
				$this->template->image_props_wrong = true;
				$this->flashMessage(_("Your image still needs to be cropped to the right dimensions before you can continue!"));
				$user->removeIcons();
			
			}
		
			$this->template->icon = User::getImage($this->template->user_id, 'icon');
			$this->template->large_icon = User::getImage($this->template->user_id, 'large_icon');
		
			imagedestroy($img_r);

		}
		
	}
	
	public function actionLogin()
	{
		
	}
	
	public function actionLogout()
	{
		$user = NEnvironment::getUser();
		if ($user->isLoggedIn()) {
			$user->logout();
			NEnvironment::getSession()->destroy();
		}
		$this->redirect('Homepage:default');
	}
	
	public function actionLostpassword()
	{
	}
	
	public function actionChangelostpassword($user_id, $control_key)
	{
		$this->user_id     = $user_id;
		$this->control_key = $control_key;
	}
	
	protected function createComponentLoginform()
	{
		$form = new NAppForm($this, 'loginform');
		$form->addText('user_login', _('Username:'));
		$form->addPassword('user_password', _('Password:'));
		$form->addCheckbox('remember_me', _('remember me'));
		$form->addSubmit('signin', _('Sign in'));
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'loginformSubmitted'
		);
		
		return $form;
	}
	
	protected function createComponentChangelostpasswordform()
	{
		$form = new NAppForm($this, 'changelostpasswordform');
		$form->addPassword('user_password', _('Password:'))->addRule(NForm::MIN_LENGTH, _("Your password must contain at least %s characters."), 8)->addRule($form::REGEXP, _("Your password must contain at least one small letter."), '/[a-z]+/')->addRule($form::REGEXP, _("Your password must contain at least one big letter."), '/[A-Z]+/')->addRule($form::REGEXP, _("Your password must contain at least one number."), '/\d+/');
		$form->addPassword('user_password_again', _('Password again:'))->addRule(NForm::EQUAL, _("Entered passwords are not the same."), $form['user_password']);
		$form->addSubmit('send', _('Change password'));
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'changelostpasswordformSubmitted'
		);
		
		return $form;
	}
	public function changelostpasswordformSubmitted(NAppForm $form)
	{
		$values = $form->getValues();
		if (User::finishPasswordchange($this->user_id, $this->control_key, $values['user_password'])) {
			$this->flashMessage(_("Your password has been successfully changed, you can now log in."));
			
			$this->redirect('User:login');
		} else {
			$this->flashMessage(_("Password couldn't be changed! Try again later."), 'error');
			
			$this->redirect('Homepage:default');
		}
	}
	
	protected function createComponentChangepasswordform()
	{
		$query = NEnvironment::getHttpRequest();
		$user_id = $query->getQuery("user_id");
		$form = new NAppForm($this, 'changepasswordform');
		$form->addPassword('user_password', _('Password:'))->addRule(NForm::MIN_LENGTH, _("Your password must contain at least %s characters."), 8)->addRule($form::REGEXP, _("Your password must contain at least one small letter."), '/[a-z]+/')->addRule($form::REGEXP, _("Your password must contain at least one big letter."), '/[A-Z]+/')->addRule($form::REGEXP, _("Your password must contain at least one number."), '/\d+/');
		$form->addPassword('user_password_again', _('Password again:'))->addRule(NForm::EQUAL, _("Entered passwords are not the same."), $form['user_password']);
		$form->addHidden('user_id',$user_id);
		$form->addSubmit('send', _('Change password'));
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'changepasswordformSubmitted'
		);
		
		return $form;
	}
	
	public function changepasswordformSubmitted(NAppForm $form)
	{
		$values = $form->getValues();
		if (User::changePassword($values['user_id'], $values['user_password'])) {
			$this->flashMessage(_("Your password has been successfully changed."));
			$this->redirect('User:edit', $values['user_id']);
		} else {
			$this->flashMessage(_("Password couldn't be changed! Try again later."), 'error');
			$this->redirect('User:edit', $values['user_id']);
		}
	}
	
	protected function createComponentReportform()
	{
		$types = array(
			'0' => _('This is not a real person but spam.'),
			'1' => _('This person was created by mistake.'),
			'2' => _('This person violates the rules of conduct.')
		);
		$form  = new NAppForm($this, 'reportform');
		$form->addRadioList('report_type', _('Reason:'), $types);
		$form->addTextarea('report_text', _('Tell us why you report this user, including examples:'))->addRule(NForm::FILLED, _('Please give us some details.'));
		$form->addSubmit('send', _('Send'));
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'reportformSubmitted'
		);
		
		return $form;
	}
	
	protected function createComponentMapedit($name)
	{
		if (!isset($this->user)) {
			$this->user = NEnvironment::getUser()->getIdentity();
		}
		
		$data    = array(
			array(
				'type' => 'user',
				'id' => $this->user->getUserId()
			)
		);
		
		$control = new MapControl($this, $name, $data, array(
			'type' => 'edit',
			'object' => array(
				'type' => 'user',
				'id' => $this->user->getUserId()
			)
		));
		
		return $control;
	}
	
	public function reportformSubmitted(NAppForm $form)
	{
		if (!empty($this->user)) {
			$user                    = NEnvironment::getUser()->getIdentity();
			$values                  = $form->getValues();
			$resource_data           = array(
				'report_type' => $values['report_type'],
				'reported_object' => 'user',
				'reported_id' => $this->user->getUserId()
			);
			
			$types = array(
				'0' => _('(spam)'),
				'1' => _('(error)'),
				'2' => _('(inappropriate language)')
			);
			
			$reported_user_data      = $this->user->getUserData();
			$data                    = array(
				'resource_name' => sprintf(_("Report about user %s, reason: %s"), $reported_user_data['user_login'], $types[$resource_data['report_type']]),
				'resource_type' => 7,
				'resource_visibility_level' => 3,
				'resource_description' => $values['report_text'],
				'resource_data' => json_encode($resource_data)
			);
			$data['resource_author'] = $user->getUserId();
			$resource                = Resource::Create();
			$resource->setResourceData($data);
			$resource->save();
			//$resource->updateUser($user->getUserId(),array('resource_user_group_access_level'=>1));
			$resource_id = $resource->getResourceId();
			$this->flashMessage(_("Your report has been received."));
		}
	}
	
	protected function createComponentLostpasswordform()
	{
		$form = new NAppForm($this, 'lostpasswordform');
		$form->addText('user_email', _('Your email:'));
		$form->addSubmit('send', _('Request new password'));
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'lostpasswordformSubmitted'
		);
		
		return $form;
	}
	
	public function lostpasswordformSubmitted(NAppForm $form)
	{
		$values = $form->getValues();
		$user   = User::getEmailOwner($values['user_email']);
		if (!empty($user)) {
			$user->sendLostpasswordEmail();
			$this->flashMessage(_("An email has been sent to you with further instructions."));
			
		} else {
			$this->flashMessage(_("This email is not registered in our system!"));
		}
	}
	
	public function loginformSubmitted(NAppForm $form)
	{
		$values = $form->getValues();
		$user   = NEnvironment::getUser();
		try {
			if (isset($values['remember_me']) && $values['remember_me'] == 1) {
				$_SESSION['remember'] = true;
				$user->setExpiration(0);
				// $user->setExpiration((NEnvironment::getConfig('variable')->sessionExpiration, FALSE);
			}
			$user->login($values['user_login'], $values['user_password']);
			
			if ($user->getIdentity()->firstLogin()) {
				$user->getIdentity()->registerFirstLogin();
				$user->getIdentity()->setLastActivity();
				$this->redirect("User:edit");
			} else {
				$user->getIdentity()->setLastActivity();
				$this->redirect("Homepage:default");
			}
		}
		catch (NAuthenticationException $e) {
			$this->flashMessage($e->getMessage(), 'error');
		}
	}
	
	protected function createComponentRegisterform()
	{
		$form = new NAppForm($this, 'registerform');
		$form->addText('user_login', _('Username:'))->addRule(NForm::FILLED, _('Username cannot be empty!'))->addRule(NForm::MIN_LENGTH, _('The minimal length of your username is %s characters.'), 3)->addRule($form::REGEXP, _('Your username can only contain letters and numbers without spaces!'), '/^[a-zA-Z0-9]+$/')->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('Please use only the English alphabet.'))->id("help-name"));
		$form->addText('user_email', _('Email:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('To this address we will send a request to confirm your registration.'))->id("help-name"))->addRule($form::REGEXP, _('Wrong email format!'), '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/');
		$form->addPassword('user_password', _('Password:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('Your password must be at least 8 characters long and contain at least one small letter, one big letter and one number.'))->id("help-name"))->addRule(NForm::MIN_LENGTH, _("Your password must contain at least %s characters."), 8)->addRule($form::REGEXP, _('Your password must contain at least one small letter.'), '/[a-z]+/')->addRule($form::REGEXP, _('Your password must contain at least one big letter.'), '/[A-Z]+/')->addRule($form::REGEXP, _("Your password must contain at least one number."), '/\d+/');
		$form->addPassword('password_again', _('Repeat password:'))->addRule(NForm::EQUAL, _('Your passwords are different.'), $form['user_password']);
		
		$form->addSubmit('register', _('Sign up'));
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'registerformSubmitted'
		);
		
		return $form;
	}
	
	public function registerformSubmitted(NAppForm $form)
	{
		$values = $form->getValues();
		$user   = NEnvironment::getUser();
		
		$login = $values['user_login'];
		if (User::loginExists($login)) {
			$this->flashMessage(_("User with the same name already exists."), 'error');
			$this->redirect('User:register');
		}
		
		if (User::emailExists($values['user_email'])) {
			$this->flashMessage(_("Email is already registered to another account."), 'error');
			$this->redirect('User:register');
		}
		
		if (StaticModel::isSpamSFS($values['user_email'], $_SERVER['REMOTE_ADDR'])) {
			$this->flashMessage(_("Your email or IP is known at www.stopforumspam.com as spam source and was blocked."), 'error');
			$this->redirect('User:register');
		}

		if (!StaticModel::validEmail($values['user_email'])) {
			$this->flashMessage(_("Email is not valid. Check it and try again."), 'error');
			$this->redirect('User:register');
		}
		
		$password = $values['user_password'];
		
		$new_user = User::create();
		
		$values['user_password'] = User::encodePassword($values['user_password']);
		unset($values['password_again']);
		$hash                = User::generateHash();
		$values['user_hash'] = $hash;
		$new_user->setUserData($values);
		$new_user->save();
		
		$new_user->setRegistrationDate();
		
		$link = $new_user->sendConfirmationEmail();
		$this->flashMessage(_("Registration has been successful. A message has been sent to your email with further instructions how to activate your account."));
		$this->redirect('Homepage:default');
	}
	
	protected function createComponentTagform()
	{
		$user = NEnvironment::getUser()->getIdentity();
		$form = new NAppForm($this, 'tagform');
		if (isset($this->user) && !is_null($this->user->getUserId()) && (Auth::isAuthorized(1, $user->getUserId()) >= 2)) {
		
			$form->addComponent(new AddTagComponent("user", $this->user->getUserId(), _("add new tag")), 'add_tag');
			
		} else {
			
			$form->addComponent(new AddTagComponent("user", null, _("add new tag")), 'add_tag');
			
		}
		return $form;
	}
	
	protected function createComponentChatform()
	{
		$form = new NAppForm($this, 'chatform');
		$form->addTextarea('message_text', '');
		$form['message_text']->getControlPrototype()->class('tinymce-small');
		$form->addSubmit('send', _('Send'));
		$form->addProtection(_('Error submitting form.'));
		
		$form->onSubmit[] = array(
			$this,
			'chatformSubmitted'
		);
		
		$this->template->message = true;
		return $form;
	}
	
	public function chatformSubmitted(NAppForm $form)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$values                            = $form->getValues();
		$resource                          = Resource::create();
		$data                              = array();
		$data['resource_author']           = $user->getUserId();
		$data['resource_type']             = 1;
		$data['resource_visibility_level'] = 3;
		$data['resource_name'] = '<chat>';//$values['resource_name'];
		$data['resource_data']             = json_encode(array(
			'message_text' => $values['message_text']
		));
		$resource->setResourceData($data);
		$resource->save();
		$resource->updateUser($this->user->getUserId(), array(
			'resource_user_group_access_level' => 1
		));
		
		$resource->updateUser($user->getUserId(), array(
			'resource_user_group_access_level' => 1,
			'resource_opened_by_user' => 1
		));
		$this->redirect("User:default", array(
			'user_id' => $this->user->getUserId()
		));
	}
	
	protected function createComponentUpdateform()
	{
		$visibility = Visibility::getArray();
		$language   = Language::getArray();
		
		$user = NEnvironment::getUser()->getIdentity();
				
		if (isset($this->user) && !is_null($this->user->getUserId())) {
			if (Auth::isAuthorized(1,$user->getUserId()) >= 2) {
				$user = $this->user;
			} else {
				$this->redirect("User:default");
			}
		}

		$user_data = $user->getUserData();
		$form      = new NAppForm($this, 'updateform');
		$form->addText('user_login', _('Username:'));
		
		if (NEnvironment::getUser()->getIdentity()->getAccessLevel()<2) {
			$form['user_login']->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('The username cannot be changed.'))->id("help-name"))->setDisabled();
		}
		$form->addText('user_name', _('Name:'));
		$form->addText('user_surname', _('Surname:'));
		$form->addText('user_phone', _('Phone:'));
		//$form->addText('user_phone_imei',_('Phone IMEI:'));
		$form->addText('user_email', _('Email:'));
		$form->addSelect('user_visibility_level', _('Visibility:'), $visibility)->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('Do you want be visible to everyone (world), one to registered users (registered) or only to your friends (friends)?'))->id("help-name"));
		$form->addSelect('user_language', _('Language:'), $language)->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('The main language you want to use. You will still be able to see other languages.'))->id("help-name"));
		$form->addTextArea('user_description', _('Description:'), 50, 10)->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('Write some lines about your life, your work and your interests.'))->id("help-name"));
		$form->addFile('user_avatar', _('Upload Avatar:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getHttpRequest()->uri->scriptPath . 'images/help.png')->class('help-icon')->title(_('Avatars are small images that will be visible with your name. Here you can upload your avatar (upload min. 120x150px, max. 1500x1500px). In the next step you can crop it.'))->id("help-name"))->addCondition(NForm::FILLED)->addRule(NForm::MIME_TYPE, _('Image must be in JPEG or PNG format.'), 'image/jpeg,image/png')->addRule(NForm::MAX_FILE_SIZE, _('Maximum image size is 512kB'), 512 * 1024);
		
		$form->addProtection(_('Error submitting form.'));
		 
		$img = $user->getAvatar();
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
		
		
		$form->onSubmit[] = array(
			$this,
			'updateformSubmitted'
		);
		$form->setDefaults($user_data);
		
		return $form;
	}
	
	public function updateformSubmitted(NAppForm $form)
	{
		$values = $form->getValues();
		$logged_user   = NEnvironment::getUser()->getIdentity();

		if (isset($this->user) && !is_null($this->user->getUserId()) && Auth::isAuthorized(1,$logged_user->getUserId()) >= 2) {
			$user = $this->user;
		} elseif (!is_null($logged_user)) { // !isset($user) && 
			$user = $logged_user;
		}
		if (!empty($user)) {
			$data = $user->getUserData();
			if ($data['user_email'] != $values['user_email'] && User::emailExists($values['user_email'])) {
				$this->flashMessage(_("This email is already used for another account."), 'error');
				$this->redirect("this");
			}
			if ($data['user_email'] != $values['user_email'] && !StaticModel::validEmail($values['user_email'])) {
				$this->flashMessage(_("This email is not valid. Please check it and try again."), 'error');
				$this->redirect("this");
			}
			if ($data['user_email'] != $values['user_email']) {
				$access = $user->getAccessLevel();
				
				// if user (of changed email) is mod or admin or if user (requesting change) has permission lower than moderator: verify old address
				if ($access > 1 || Auth::isAuthorized(Auth::TYPE_USER,$user->getUserId()) < Auth::MODERATOR) {
					$values['user_email_new'] = $values['user_email'];
					$values['user_email']     = $data['user_email'];
					
					$user->sendEmailchangeEmail();
					$this->flashMessage(_("You requested a change of your email address. A message with a link was sent to your old address. The new address will be activated once you confirmed the change."));
				} else { // mods and admins
					$this->emailchangeAdmin($user->getUserId(),$values['user_email']);
				}
			}
			if (empty($values['user_password'])) {
				unset($values['user_password']);
				unset($values['password_again']);
			} else {
				$values['user_password'] = User::encodePassword($values['user_password']);
				unset($values['password_again']);
			}
			if ($values['user_avatar']->getTemporaryFile() != "") {
			
				$size= getimagesize($values['user_avatar']->getTemporaryFile());
				
				if ($size[0]>1500 || $size[1]>1500) {
					$this->flashMessage(_("Image is too big! Max. size is for upload is 1500x1500"),'error');
				} elseif ($size[0]<120 || $size[1]<150) {
					$this->flashMessage(_("The image is too small! Min. size for upload is 120x150"),'error');
				} else {
					$values['user_portrait'] = base64_encode(file_get_contents($values['user_avatar']->getTemporaryFile()));
				}
			}
			unset($values['user_avatar']);
			$user->setUserData($values);
			if ($user->save()) $this->flashMessage(_("User updated"));;
			$this->redirect("this");
		}
	}
	
	public function handleUserAdministration($user_id, $values)
	{
		$user = User::create($user_id);
		
		$user->setUserData($values);
		$user->save();
		$this->terminate();
	}
	
	protected function createComponentDefaultusergrouplister($name)
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
			'refresh_path' => 'User:default'
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'user_id' => $session->data['object_id']
			);
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	protected function createComponentDefaultuserresourcelister($name)
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
			'refresh_path' => 'User:default'
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'user_id' => $session->data['object_id']
			);
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	protected function createComponentDetailusergrouplister($name)
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
			'refresh_path' => 'User:default',
			'refresh_path_params' => array(
				'user_id' => $this->user->getUserId()
			),
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'user_id' => $session->data['object_id']
			);
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	protected function createComponentDetailuserresourcelister($name)
	{
		$options = array(
			'itemsPerPage' => 10,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE_DETAIL
			),
			'template_body' => 'ListerControlMain_resources.phtml',
			'template_variables' => array(
				'show_extended_columns' => true,
				'connection_columns' => true
			),
			'refresh_path' => 'User:default',
			'refresh_path_params' => array(
				'user_id' => $this->user->getUserId()
			),
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'user_id' => $session->data['object_id']
			);
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	/**
	*	Default page, left column
	**/
	protected function createComponentUserlister($name)
	{
		$session      = NEnvironment::getSession()->getNamespace($this->name);
		$selected_row = 0;
		if (!empty($session->data)) {
			$selected_row = $session->data['object_id'];
		}
		
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'components' => 'global',
			'refresh_path' => 'User:default',
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
	
	protected function createComponentFriendlister($name)
	{
		$user    = NEnvironment::getUser()->getIdentity();
		$options = array(
			'itemsPerPage' => 20,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'filter' => array(
				'user_id' => $this->user->getUserId(), // $user->getUserId()
			),
			'refresh_path' => 'User:default',
			'template_variables' => array(
				'connection_columns' => true
				)
		);

		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	public function handleRemoveAvatar($user_id = null)
	{
		$user = NEnvironment::getUser()->getIdentity();
		if (!is_null($user_id) ) {
			if (Auth::isAuthorized(1,$user->getUserId()) >= 2) {
			
				$user = User::Create($user_id);

			} else {

				$this->redirect("User:default");

			}
		}
		
		if (empty($user)) {
			$this->terminate();
		}
		
		if (!empty($user)) {
			BasePresenter::removeImage($user_id,1);
			$user->removeAvatar();
			$user->removeIcons();
		}
		$this->redirect("this");
	}
	
	public function handleInsertTag($tag_id, $user_id = null)
	{
		$user = NEnvironment::getUser()->getIdentity();
		if (!is_null($user_id) ){
			if (Auth::isAuthorized(1,$user->getUserId()) >= 2) {

				$user = User::Create($user_id);
		
			} else {
				$this->redirect("User:default");
			}
		}
		
		if (empty($user)) {
			$this->terminate();
		}
		$user->insertTag($tag_id);
		$this->template->user_tags = $this->user->groupSortTags($user->getTags());
		$this->invalidateControl('tagHandle');
	}
	
	public function handleRemoveTag($tag_id, $user_id = null)
	{
		$user = NEnvironment::getUser()->getIdentity();
		if (!is_null($user_id)) {
			if (Auth::isAuthorized(1,$user->getUserId()) >= 2) {
			
				$user = User::Create($user_id);
			} else {
				$this->redirect("User:default");
			}
		}
		if (empty($user)) {
			$this->terminate();
		}
		$user->removeTag($tag_id);
		$this->template->user_tags = $this->user->groupSortTags($user->getTags());
		$this->invalidateControl('tagHandle');
	}
	
	public function handleDefaultPage($object_type, $object_id)
	{
		$this->user_id                = $object_id;
		$this->user                   = User::create($object_id);
		$data                         = $this->user->getUserData();
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
	}
	
	protected function createComponentMap($name)
	{
		$data    = array(
			array(
				'type' => 'user',
				'id' => $this->user->getUserId()
			)
		);
		$control = new MapControl($this, $name, $data, array(
			'object' => array(
				'type' => 'user',
				'id' => $this->user->getUserId()
			)
		));
		return $control;
	}
	
	public function handleUserFriendInsert($friend_id)
	{
		if (empty($friend_id)) {
			print "false";
			$this->terminate();
		} else {
			$user   = NEnvironment::getUser()->getIdentity();
			$friend = User::create($friend_id);
			
			if (!empty($friend)) {
				$friend_id = $friend->getUserId();
				if (!empty($friend_id)) {
					$user->updateFriend($friend_id, array());
					print "true";
				}
			}
		}
		
		$this->terminate();
	}
	
	public function handleUserFriendRemove($friend_id)
	{
		if (empty($friend_id)) {
			print "false";
			$this->terminate();
		} else {
			$user   = NEnvironment::getUser()->getIdentity();
			$friend = User::create($friend_id);
			
			if (!empty($friend)) {
				$friend_id = $friend->getUserId();
				if (!empty($friend_id)) {
					$user->removeFriend($friend_id);
					print "true";
				}
			}
		}
		
		$this->terminate();
	}
	
	/**
	*	chat on User detail page
	*/
	protected function createComponentChatlisteruser($name)
	{
		$logged_user_id = NEnvironment::getUser()->getIdentity()->getUserId();
		
		$options = array(
			'itemsPerPage' => 30,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'ListerControlMain_messages.phtml',
			'filter' => array(
				'type' => 1,
				'all_members_only' => array(
					array(
						'type' => 1,
						'id' => $logged_user_id
					),
					array(
						'type' => 1,
						'id' => $this->user->getUserId()
					)
				)
			),
			'refresh_path' => 'User:default',
			'refresh_path_params' => array(
				'user_id' => $this->user->getUserId()
			),
			'template_variables' => array(
				'trash_enabled' => true,
				'hide_apply' => true,
				'hide_reset' => true,
				'mark_read_enabled' => true,
                'reply_enabled'=>1,
				'messages' => true,
				'message_lister' => true,
				'logged_user_id' => $logged_user_id
                )
		);
		$session = NEnvironment::getSession()->getNamespace($name);
		
		if (!isset($session['filterdata']['trash'])) $session->filterdata = array_merge(array('trash' => 2, $session->filterdata));
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	protected function createComponentMessageform()
	{
		$user    = NEnvironment::getUser()->getIdentity();
		$friends = $user->getFriends();
		$form    = new NAppForm($this, 'messageform');
		$form->addSelect('friend_id', _('To:'), $friends);
		$form->addTextarea('message_text', '');
		$form['message_text']->getControlPrototype()->class('tinymce-small');
		$form->addSubmit('send', _('Send'));
		$form->addProtection(_('Error submitting form.'));
		
		$form->onSubmit[] = array(
			$this,
			'messageformSubmitted'
		);
		
		return $form;
	}
	
	public function messageformSubmitted(NAppForm $form)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$values                            = $form->getValues();
		$resource                          = Resource::create();
		$data                              = array();
		$data['resource_author']           = $user->getUserId();
		$data['resource_type']             = 1;
		$data['resource_visibility_level'] = 3;
		$data['resource_name']             = '<private message>';
		$data['resource_data']             = json_encode(array(
			'message_text' => $values['message_text']
		));
		if (strip_tags($values['message_text']) == '') {
			$this->flashMessage(_("Your message was empty."), 'error');
			$this->redirect("User:messages");
		}
		$resource->setResourceData($data);
		$resource->save();
		$resource->updateUser($values['friend_id'], array(
			'resource_user_group_access_level' => 1
		));
		
		$resource->updateUser($user->getUserId(), array(
			'resource_user_group_access_level' => 1,
			'resource_opened_by_user' => 1
		));
		$this->flashMessage(_("Your message has been sent."));
		$this->redirect("User:messages");
	}
	
	/**
	*	page for messages /user/messages/
	*/
	protected function createComponentMessagelisteruser($name)
	{
		$logged_user_id = NEnvironment::getUser()->getIdentity()->getUserId();
		
		$options = array(
			'itemsPerPage' => 30,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'ListerControlMain_messages.phtml',
			'filter' => array(
				'type' => array(
					1,
					9
				),
				'all_members_only' => array(
					array(
						'type' => 1,
						'id' => NEnvironment::getUser()->getIdentity()->getUserId()
					)
				)
			),
			'template_variables' => array(
				'trash_enabled' => true,
				'mark_read_enabled' => true,
                'reply_enabled'=>true,
				'messages' => true,
				'message_lister' => true,
				'hide_apply' => true,
				'hide_reset' => true,
				'logged_user_id' => $logged_user_id
			),
			'refresh_path' => 'User:messages'
		);

		$session = NEnvironment::getSession()->getNamespace($name);
		
		
		
		if (!isset($session['filterdata']['trash'])) $session->filterdata = array_merge(array('trash' => 2, $session->filterdata));
		
//		var_dump($session['filterdata']);
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	public function createComponentUseradministrator()
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$user_id = $session->data['object_id'];
		}
		
		$logged_user = NEnvironment::getUser()->getIdentity();
		
		$form         = new NAppForm($this, 'useradministrator');
		$user         = User::create($user_id);
		$user_data    = $user->getUserData();
		$access_level = array(
			'1' => _('Normal user'),
			'2' => _('Moderator'),
			'3' => _('Administrator')
		);
		if (!empty($logged_user) && $logged_user->getAccessLevel() == 3 && $user_id != 1) {
			$form->addSelect('access_level', null, $access_level);
		} elseif ($user_id == 1) {
			$form->addSelect('access_level', null, array('3' => _('Administrator')));
		}
		if (!empty($logged_user) && $logged_user->getAccessLevel() == 3 && $user_id != 1) {			
			$form->addCheckbox('status');
			$form->addCheckbox('creation_rights');
		} else if (!empty($logged_user) && $logged_user->getAccessLevel() == 2 && $user->getAccessLevel() < 2 && $user_id != 1) {
			$form->addCheckbox('status');
			$form->addCheckbox('creation_rights');
			
		}
		if (!isset($form['status']) && !isset($form['creation_rights']) && !isset($form['access_level'])) {
		} else {
			$form->addSubmit('send', _('Update'));
		}
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'adminUserFormSubmitted'
		);
		$form->setDefaults(array(
			'access_level' => $user_data['user_access_level'],
			'status' => $user_data['user_status'],
			'creation_rights' => $user_data['user_creation_rights']
		));
		return $form;
	}
	
	public function adminUserFormSubmitted(NAppForm $form)
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		
		if (!empty($session->data)) {
			$user_id = $session->data['object_id'];

			if ($user_id == 1) {
				$this->flashMessage(_("Administrator with id 1 cannot be changed"), 'error');
				$this->redirect("User:default");
			}
			$values = $form->getValues();
			$user   = User::create($user_id);
			
			foreach ($values as $key => $value) {
				$values["user_" . $key] = $value;
				unset($values[$key]);
			}
			$user->setUserData($values);
			$user->save();
			
			$this->flashMessage(_("User permissions changed."));
			
			$this->redirect("User:default");
		}
	}
	
	/**
	*	click on move-to-trash icon in message list
	*/
	public function handleMoveToTrash($resource_id)
	{
		$user     = NEnvironment::getUser()->getIdentity();
		$resource = Resource::create($resource_id);
		
		if (!empty($resource)) {
			if (!empty($user)) {
				if ($resource->userIsRegistered($user->getUserId())) {
					Resource::moveToTrash($resource_id);
				}
			}
		}
		$this->terminate();
	}
	
	/**
	*	click on restore-from-trash icon in message list
	*/
	public function handleMoveFromTrash($resource_id)
	{
		$user     = NEnvironment::getUser()->getIdentity();
		$resource = Resource::create($resource_id);

		if (!empty($resource)) {
			if ($resource->userIsRegistered($user->getUserId())) {
				Resource::moveFromTrash($resource_id);
			}
		}
		$this->terminate();
	}
	
	/**
	*		Marks message as read
	*		Receives message id via query ?do=
	*/
	public function handleMarkRead($resource_id)
	{
		$user     = NEnvironment::getUser()->getIdentity();
		$resource = Resource::create($resource_id);
		if (!empty($resource)) {
			if (!empty($user)) {
				if ($resource->userIsRegistered($user->getUserId())) {
					$resource->setOpened($user->getUserId(),$resource_id);
				}
			}
		}
		$this->terminate();
	}
	
	/**
	*		Marks message as unread
	*		Receives message id via query ?do=
	*/
	public function handleMarkUnread($resource_id)
	{
		$user     = NEnvironment::getUser()->getIdentity();
		$resource = Resource::create($resource_id);
		
		// cannot mark own messages as unread?
		
		if (!empty($resource)) {
			if ($resource->userIsRegistered($user->getUserId())) {
				$resource->setUnopened($user->getUserId(),$resource_id);
			}
		}
		$this->terminate();
	}
	
/*
	public function emptytrashformSubmitted(NAppForm $form)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		Resource::emptyTrash();
		$this->flashMessage(_("Trash emptied."));
		$this->redirect("User:messages");
	}
*/
	
	public function isAccessible()
	{
		if ($this->getAction() == "default") {
			return true;
		}
		if ($this->getAction() == "login") {
			return true;
		}
		if ($this->getAction() == "register") {
			return true;
		}
		if ($this->getAction() == "confirm") {
			return true;
		}
		if ($this->getAction() == "lostpassword") {
			return true;
		}
		if ($this->getAction() == "changepassword") {
			return true;
		}
		if ($this->getAction() == "changelostpassword") {
			return true;
		}
		return false;
	}

	public function handleCrop() {

		$query = NEnvironment::getHttpRequest();

		$x = $query->getQuery("x");
		$y = $query->getQuery("y");
		$w = $query->getQuery("w");
		$h = $query->getQuery("h");
		$user_id = $query->getQuery("user_id");


		if ($user_id == 0 || Auth::isAuthorized(Auth::TYPE_USER, $user_id) < 2) {
			
			$this->redirect("User:edit",$user_id);
		}
		
		BasePresenter::removeImage($user_id,1);

		$targ_w = 160;
		$targ_h = 200;

		$user = new User($user_id);

		if (!empty($user)) {

			$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );

			$data = base64_decode($user->getAvatar());
		
			if (isset($data)) {
		
				$img_r = imagecreatefromstring($data);
		
				imagecopyresampled($dst_r, $img_r, 0, 0, $x, $y, $targ_w, $targ_h, $w, $h);

				ob_start();
					header('Content-type: image/jpeg');
					imagejpeg($dst_r,NULL,80);
					$image_data = ob_get_contents(); 
				ob_end_clean(); 
			
				$cropped_image = base64_encode($image_data);
		
				$values = array ('user_portrait' => $cropped_image);


				$user->setUserData($values);
				$user->save();
			}
		}

		$this->redirect("User:edit",array('user_id'=>$user_id,'do'=>'makeicon'));
	}	

	public function handleMakeicon($user_id) {

//		$query = NEnvironment::getHttpRequest();	
//		$user_id = $query->getQuery("user_id");

		
		if ($user_id == 0 || Auth::isAuthorized(Auth::TYPE_USER, $user_id) < 2) {
			
			$this->redirect("User:edit",$user_id);
		}

		$targ_w = 20;
		$targ_h = 25;
		$x=0;
		$y=0;
		$w = 160;
		$h = 200;


		$user = new User($user_id);

		if (!empty($user)) {

			$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );

			$data = base64_decode($user->getAvatar());
		
			if (isset($data)) {
		
				$img_r = imagecreatefromstring($data);
		
				imagecopyresampled($dst_r, $img_r, 0, 0, $x, $y, $targ_w, $targ_h, $w, $h);

				ob_start();
					header('Content-type: image/jpeg');
					imagejpeg($dst_r,NULL,90);
					$image_data = ob_get_contents(); 
				ob_end_clean(); 

				$cropped_image = base64_encode($image_data);
		
				$values = array ('user_icon' => $cropped_image);


				$user->setUserData($values);
				$user->save();
			}
		}

		$this->redirect("User:edit",array('user_id'=>$user_id,'do'=>'makebigicon'));

	}
	
	public function handleMakebigicon($user_id) {
	
//		$query = NEnvironment::getHttpRequest();		
//		$user_id = $query->getQuery("user_id");
		
		if ($user_id == 0 || Auth::isAuthorized(Auth::TYPE_USER, $user_id) < 2) {
			
			$this->redirect("User:edit",$user_id);
		}

		$targ_w = 40;
		$targ_h = 50;
		$x=0;
		$y=0;
		$w = 160;
		$h = 200;

		$user = new User($user_id);

		if (!empty($user)) {

			$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );

			$data = base64_decode($user->getAvatar());
		
			if (isset($data)) {
		
				$img_r = imagecreatefromstring($data);
		
				imagecopyresampled($dst_r, $img_r, 0, 0, $x, $y, $targ_w, $targ_h, $w, $h);

				ob_start();
					header('Content-type: image/jpeg');
					imagejpeg($dst_r,NULL,90);
					$image_data = ob_get_contents();
				ob_end_clean(); 

				$cropped_image = base64_encode($image_data);
		
				$values = array ('user_largeicon' => $cropped_image);


				$user->setUserData($values);
				$user->save();
			}
		}

		$this->flashMessage(_("Finished cropping and resizing."));
		$user->saveImage($user_id);
		$this->redirect("User:edit",$user_id);

	}


	public function handleSearchTag($tag_id)
	{
		if (NEnvironment::getVariable("GLOBAL_FILTER")) $name='defaultresourceresourcelister' ; else $name='userlister';
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

		$this->redirect("User:default");
	}

}
