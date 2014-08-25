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
 

final class UserPresenter extends BasePresenter
{
	protected $user;
	protected $user_id;
	protected $control_key;
	private $ajax = false;

	/**
	 *	runs startup in BasePresenter
	 *	@param void
	 *	@return void
	*/
	public function startup()
	{
		parent::startup();
	}


	/**
	 *	Checks if page is accessible without login.
	 *	@param void
	 *	@return boolean
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
		if ($this->getAction() == "changelostpassword") {
			return true;
		}
		if ($this->getAction() == "captcha") {
			return true;
		}
		return false;
	}


	/**
	 *	Prepares data for display in User Default and Detail template
	 *	@param int $user_id
	 *	@return void
	 */
	public function actionDefault($user_id = null)
	{
		$image_type = null;
		$data = null;
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';
				
		if (!is_null($user_id)) {
			$this->setView('detail');
			$this->user = User::create($user_id);
			$d          = $this->user->getUserData();
			if (empty($d)) {
				$this->flashMessage(_t("This user doesn't exist."), 'error');
				$this->redirect("User:default");
			}
			if (Auth::isAuthorized(Auth::TYPE_USER, $user_id) == 0) {
				$this->flashMessage(_t("You are not allowed to view this user."), 'error');
				$this->redirect("User:default");
			}
			
			$this->template->last_activity = $this->user->getLastActivity();
			$this->template->format_date = _t("j.n.Y");
			
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
			
			$image = Image::createimage($user_id, 1);
			if ($image !== false) {
				$this->template->img = $image->renderImg('img');
				$this->template->icon = $image->renderImg('icon');
				$this->template->large_icon = $image->renderImg('large_icon');
				$this->template->mime_type = $image->mime_type;
			} else {
				$this->template->img = Image::default_img('img');
				$this->template->icon = Image::default_img('icon');
				$this->template->large_icon = Image::default_img('large_icon');
			}
			$this->template->user_tags = $this->user->groupSortTags($this->user->getTags());
		}
		
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$viewed_user = User::create($session->data['object_id']);
			$data['object_data']            = $viewed_user->getUserData();
			$data['object_data']['user_id'] = $session->data['object_id'];
			
			$logged_user = NEnvironment::getUser()->getIdentity();
			if (!empty($logged_user) && !empty($viewed_user) && $logged_user->getUserId() != $data['object_data']['user_id']) {
				$data['object_data']['user_friend_relationship'] = $logged_user->friendsStatus($viewed_user->getUserId());
				$data['object_data']['friend_user_relationship'] = $viewed_user->friendsStatus($logged_user->getUserId());
				$this->template->logged_user = $logged_user->getUserId();
			} else {
				$data['object_data']['user_friend_relationship'] = -1;
			}
			$this->template->default_data = $data;
			
			if (!empty($this->user)) {
				if($this->user->thatsMe()) $this->template->thats_me=true;
			} elseif (!empty($viewed_user)) {
//				if($viewed_user->thatsMe()) $this->template->thats_me=true;
			}
			
			$image = Image::createimage($session->data['object_id'],1);
			if ($image !== false) {
				$this->template->img = $image->renderImg('img');
				$this->template->icon = $image->renderImg('icon');
				$this->template->large_icon = $image->renderImg('large_icon');
				$this->template->mime_type = $image->mime_type;
			} else {
				$this->template->img = Image::default_img('img');
				$this->template->icon = Image::default_img('icon');
				$this->template->large_icon = Image::default_img('large_icon');
			}
		}
		
		if (isset($data)) {
			$languages = Language::getArray();
			if (isset($data['object_data']['user_language']) && isset($languages[$data['object_data']['user_language']])) $this->template->object_language = $languages[$data['object_data']['user_language']];
		}

		StaticModel::logTime('Finished UserPresenter->actionDefault()');
	}


	/**
	 *	Prepares sign up (register) page
	 *	@param void
	 *	@return void
	*/
	public function actionRegister()
	{
		if (StaticModel::checkLoginFailures() === false) {
			$this->flashMessage(_t('Too many failed login attempts. Please try again later.', 'error'));
			$this->redirect('Homepage:default');
		}

		if (Settings::getVariable('sign_up_disabled')) {
			$this->template->sign_up_disabled = true;
		} else {
			$fb = $this->facebook();
			if ( $fb === true) {
				$this->redirect('Homepage:default');
			} else {
				$this->template->FB_LOGIN_URL = $fb;
			}
		}

		$user = NEnvironment::getUser();
		if ($user->isLoggedIn()) $this->template->logged = true;
	}


	/**
	 *	Finish up registration, processing the confirmation link
	 *
	 *	@param int $user_id
	 *	@param string $control_key Unique hash sent with confirmation link
	 *	@param string $device 'mobile' (or detected automatically) for display without web layout
	 *	@return void
	 */
	public function actionConfirm($user_id, $control_key, $device = NULL)
	{
		if (StaticModel::checkLoginFailures() === false) {
			$this->flashMessage(_t('Too many failed login attempts. Please try again later.', 'error'));
			$this->redirect('Homepage:default');
		}

		$user = NEnvironment::getUser();
		if ($user->isLoggedIn()) {
			$user->logout();
			NEnvironment::getSession()->destroy();
		}
		
		$user = User::create($user_id);
			
		$question = Settings::getVariable('signup_question');
		if ($question && $user->getCaptchaOk() == false) {
			if (isset($device) && $device=="mobile") {
				$this->redirect("Widget:mobilecaptcha", array('control_key' => $control_key, 'user_id' => $user_id));
			} else {
				$this->redirect("User:register", array('user_id' => $user_id));
			}
		}

		if ($device == NULL) {
			// detecting mobile devices
			require_once LIBS_DIR.'/Mobile-Detect/Mobile_Detect.php';
			$detect = new Mobile_Detect;
			if ($detect->isMobile()) $device = 'mobile';
			unset($detect);
		}

		if (User::finishRegistration($user_id, $control_key)) {
		
			// update registration date for correct determination of creation rights
			$user->setRegistrationDate();
			
			if (isset($device) && $device=="mobile") {
				echo _t("The registration has been successful. You can now sign in.");
				Activity::addActivity(Activity::USER_JOINED, $user_id, 1);
				$this->terminate();
			} else {
				$this->flashMessage(_t("The registration has been successful. You can now sign in."));
				Activity::addActivity(Activity::USER_JOINED, $user_id, 1);
				$this->redirect("User:login", array('registration' => 'form'));
			}
		} else {
		
			if (isset($device) && $device=="mobile") {
				echo _t("The registration couldn't be finished! The link is not active anymore.");
				
				$this->terminate();
			} else {
				$this->flashMessage(_t("The registration couldn't be finished! The link is not active anymore."), 'error');
			
				$this->redirect('Homepage:default');
			}
		}
	}


	/**
	 *	Landing page after clicking the confirmation link to change the email.
	 *	@param int $user_id
	 * 	@param int $control_key
	 	@param string $device
	 *	@return void
	 */
	public function actionEmailchange($user_id, $control_key, $device=NULL)
	{
		if ($device == NULL) {
			// detecting mobile devices
			require_once LIBS_DIR.'/Mobile-Detect/Mobile_Detect.php';
			$detect = new Mobile_Detect;
			if ($detect->isMobile()) $device = 'mobile';
			unset($detect);
		}
		
		if (User::finishEmailChange($user_id, $control_key)) {
			if (isset($device) && $device=="mobile") {
				echo _t("Email has been successfully changed.");
				
				$this->terminate();
			} else {
				$this->flashMessage(_t("The email address has been succesfully changed."));
			
				$this->redirect('Homepage:default');
			}
		} else {
			if (isset($device) && $device=="mobile") {
				echo _t("The email address couldn't be changed! The link is not active anymore.");
				
				$this->terminate();
			} else {
				$this->flashMessage(_t("The email address couldn't be changed! The link is not active anymore."), 'error');
			
				$this->redirect('Homepage:default');
			}
		}
	}


	/**
	 *	Effects the change of a user's email without need to confirm the new email. Operated by admins and mods.
	 *	@param int $user_id
	 *	@param int $user_email
	 *	@return void
	 */
	public function emailchangeAdmin($user_id, $user_email)
	{
		if (NEnvironment::getUser()->getIdentity()->getAccessLevel()<2) {
			$this-redirect("this");
		}
		
		if (User::finishEmailChangeAdmin($user_id,$user_email)) {
			$this->flashMessage(_t("The email address has been succesfully changed."));			
			$this->redirect("this");
		} else {
			$this->flashMessage(_t("The email couldn't be changed!"), 'error');
			$this->redirect("this");
		}
	}


	/**
	 *	Prepares the general private message page /messages.
	 *	@param void
	 *	@return void
	 */
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
	}


	/**
	 *	Prepares the page to edit a user profile. If $user_id is null, the currently logged in user will be used.
	 *	@param int $user_id
	 *	@return void
	 */
	public function actionEdit($user_id = null)
	{
		$this->template->load_js_css_jcrop = true;
		$this->template->load_js_css_tree = true;
		
		$query = NEnvironment::getHttpRequest();
		$do = $query->getQuery("do");
		if ($do == 'crop') return;
	
		$user = NEnvironment::getUser()->getIdentity();
		if (empty($user)) {
			$this->redirect("Homepage:default");
		}
		if (!is_null($user_id) ) {
			if (Auth::isAuthorized(1,$user->getUserId()) >= Auth::MODERATOR) {
				$this->user = User::create($user_id);
				$user       = $this->user;
				if (empty($user)) {
					$this->redirect("Homepage:default");
				}
			
				$this->template->administrated_user = $user->getUserId();
			} else {
				$user = NEnvironment::getUser()->getIdentity();
				if (empty($user)) {
					$this->redirect("Homepage:default");
				}
			}
		} else {
			$this->redirect("User:default");
		}
		
		if (!empty($this->user)) {
			$tags = $this->user->groupSortTags($user->getTags());
			$this->template->user_tags = $tags;
		}
		$this->template->user_id = $user->getUserId();
		$size_x = 0;
		$size_y = 0;
		$image = Image::createimage($user->getUserId(), 1);
		if ($image !== false) {
			$this->template->icon = $image->renderImg('icon');
			$this->template->large_icon = $image->renderImg('large_icon');
			$this->template->mime_type = $image->mime_type;
			$size_x = $image->width;
			$size_y = $image->height;
			$this->template->factor = 1;
			$this->template->min_size_x = 120;
			$this->template->min_size_y = 150;
			if ($size_x == 0 || $size_y == 0) {
				$this->flashMessage(_t("There is a problem with your avatar."),"error");
				$user->removeAvatar();
				$user->removeIcons();
		
			} elseif ($size_x < 120 || $size_y < 150) {
				$this->flashMessage(sprintf(_t("The image is too small. Minimum size is %s."), "80px x 100px"), 'error');
				$user->removeIcons();
			} elseif ($size_x > 160 || $size_y > 200 ) {
				$this->template->image_too_large = true;
				$this->flashMessage(_t("Your image still needs to be resized before you can continue!"));
				$user->removeIcons();
			
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
				$user->removeIcons();
			} else {
				$this->template->img_src = $image->src();
			}
		} else {
			$user->removeIcons();
		}
		
	}


	/**
	 *	Prepares sign in (log in) page.
	 *	@param void
	 *	@return void
	 */
	public function actionLogin()
	{
		if (StaticModel::checkLoginFailures() === false) {
			$this->flashMessage(_t('Too many failed login attempts. Please try again later.', 'error'));
			$this->redirect('Homepage:default');
		}

		$session = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = Language::getId($session->language);
		if ($language == 0) {
			$language = 1;
		}
		
		$user = NEnvironment::getUser();
		if ($user->isLoggedIn()) {
			$user->logout();
			NEnvironment::getSession()->destroy();
			$this->redirect('this');
		}
		
		if (Settings::getVariable('sign_in_disabled')) {
			$this->template->sign_in_disabled = true;
			$this->redirect('Homepage:default');
		}
		
		$fb = $this->facebook();
		if ( $fb === true) {
			$session = NEnvironment::getSession()->getNamespace("GLOBAL");
			$language = Language::getId($session->language);
			if ($language == 0) {
				$language = 1;
			}
			$this->redirect('Homepage:default',  array('language' => $language));
		} else {
			$this->template->FB_LOGIN_URL = $fb;
		}
	}


	/**
	 *	Prepares sign out (log out) page
	 *	@param void
	 *	@return void
	 */
	public function actionLogout()
	{
		$session = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = Language::getId($session->language);
		if ($language == 0) {
			$language = 1;
		}
		
		$user = NEnvironment::getUser();
		if ($user->isLoggedIn()) {
			$user->logout();
			NEnvironment::getSession()->destroy();
		}
		
		$this->redirect('Homepage:default', array('language' => $language));
	}


	/**
	 *	Prepares password recovery page.
	 *	@param void
	 *	@return void
	 */
	public function actionLostpassword()
	{
		if (StaticModel::checkLoginFailures() === false) {
			$this->flashMessage(_t('Too many failed login attempts. Please try again later.', 'error'));
			$this->redirect('Homepage:default');
		}
	}


	/**
	 *	Prepares landing page for confirmation link after email to reset password.
	 *	@param int $user_id
	 *	@param int $control_key
	 *	@return void
	 */
	public function actionChangelostpassword($user_id, $control_key)
	{
		if (StaticModel::checkLoginFailures() === false) {
			$this->flashMessage(_t('Too many failed login attempts. Please try again later.', 'error'));
			$this->redirect('Homepage:default');
		}

		$this->user_id     = $user_id;
		$this->control_key = $control_key;
	}


	/**
	 *	Creates login form.
	 *	@param void
	 *	@return array
	 */
	protected function createComponentLoginform()
	{
	
		$request = NEnvironment::getHttpRequest();
		$redirect = $request->getQuery("redirect");
		
		
		$form = new NAppForm($this, 'loginform');
		$form->addText('user_login', _t('Username:'));
		$form->addPassword('user_password', _t('Password:'));
		$form->addCheckbox('remember_me', _t('Remember me'));
		$form->addSubmit('signin', _t('Sign in'));
		$form->addProtection(_t('Error submitting form.'));
		$form->addHidden('redirect', $redirect);
		$form->onSubmit[] = array(
			$this,
			'loginformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	Processes return values of signin / login.
	 *	@param object $form
	 *	@return void
	 */
	public function loginformSubmitted(NAppForm $form)
	{
		if (StaticModel::checkLoginFailures() === false) {
			die(_t('Too many failed login attempts. Please try again later.'));
		}

		$values = $form->getValues();
		
		if (strlen($values['user_password']) > 72) { die('Password must be 72 characters or less'); }
		
		if (Settings::getVariable('sign_in_disabled') && User::getAccessLevelFromLogin($values['user_login']) < 2) {
			$this->flashMessage(_t("Sign in is disabled. Please try again later."), 'error');
			$this->redirect("Homepage:default");
		}

		$user_e = NEnvironment::getUser();
		
		try {
			if (isset($values['remember_me']) && $values['remember_me'] == 1) {
				$_SESSION['remember'] = true;
				$user_e->setExpiration(0); // 0 = Session expires when user closes the browser.
			}

			if (Settings::getVariable('email_for_username')) {
				// allow email address
				if (filter_var($values['user_login'], FILTER_VALIDATE_EMAIL)) {
					$values['user_login'] = User::userloginFromEmail($values['user_login']);
				}
			}

			$user_e->login($values['user_login'], $values['user_password']);
			$user = $user_e->getIdentity();
			$user_id = $user->getUserId();
			if ($user->firstLogin()) {
				$user->registerFirstLogin();
				$user->setLastActivity();
				$this->redirect("User:edit", $user_id);
			} else {
				$user->setLastActivity();
				$session = NEnvironment::getSession()->getNamespace("GLOBAL");
				$language_code = Language::getFlag($user->getLanguage());
				if (!empty($language_code)) {
					$session->language = $language_code;
				}
				

				if (!empty($values['redirect'])) {
					header( "Location: ".NEnvironment::getVariable("URI") . urldecode($values['redirect']));
					exit;
				}
				$this->redirect("Homepage:default");
			}
		}
		catch (NAuthenticationException $e) {
			$this->flashMessage($e->getMessage(), 'error');
		}
	}


	/**
	 *	Creates form for settings whether to be notified on unread messages. (overlay)
	 *	@param void
	 *	@return array
	 */
	protected function createComponentNotificationsform()
	{
		$user = NEnvironment::getUser()->getIdentity();
		$notification_setting = $user->getNotificationSetting();
	
		$form = new NAppForm($this, 'notificationsform');
		$form->addSelect('user_send_notifications', _t('Emails on unread messages:'), array('0'=>_t('off'), '1'=>_t('max. once per hour'), '24'=>_t('max. once per day'), '168'=>_t('max. once per week')))->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('You can receive an email when you have unread messages in your inbox.'))->id("help-name"));
		$form->addProtection(_t('Error submitting form.'));
		$form->addSubmit('send', _t('Update'));

		$form->onSubmit[] = array(
			$this,
			'notificationsformSubmitted'
		);
		$form->setDefaults(array('user_send_notifications' => $notification_setting));
		return $form;
	}


	/**
	 *	Handles submitted notification form.
	 *	@param object $form
	 *	@return void
	 */
	public function notificationsformSubmitted(NAppForm $form)
	{
		$values = $form->getValues();
		$logged_user   = NEnvironment::getUser()->getIdentity();
		if (isset($this->user) && !is_null($this->user->getUserId()) && Auth::isAuthorized(1,$logged_user->getUserId()) >= 2) {
			$user = $this->user;
		} elseif (!is_null($logged_user)) {
			$user = $logged_user;
		} else {
			die('no permission');
		}
		$user->setNotificationSetting($values['user_send_notifications']);
		$this->redirect("this");
	}


	/**
	 *	Prepares form to request link on email for password change
	 *	@param void
	 *	@return object
	 */
	protected function createComponentLostpasswordform()
	{
		$form = new NAppForm($this, 'lostpasswordform');
		if (Settings::getVariable('email_for_username')) {
			$form->addText('user_email', _t('Your email or username:'));
		} else {
			$form->addText('user_email', _t('Your email:'));
		}
		$form->addSubmit('send', _t('Request new password'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'lostpasswordformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	Processes request for link to change password.
	 *	@param object $form
	 *	@return void
	 */
	public function lostpasswordformSubmitted(NAppForm $form)
	{
		if (StaticModel::checkLoginFailures() === false) {
			die(_t('Too many failed login attempts. Please try again later.'));
		}

		// no authentication because user doesn't need to be logged in
		$values = $form->getValues();
		if (Settings::getVariable('email_for_username') && !filter_var($values['user_email'], FILTER_VALIDATE_EMAIL)) {
			// allow username && no valid email entered
			$user_ids = User::getOwnerIdsFromLogin($values['user_email']);
			if (isset($user_ids) && is_array($user_ids) && count($user_ids)==1) {
				$user_id = $user_ids[0];
				$user = User::create($user_id);
			} else {
				$this->flashMessage(_t("This username is not registered in our system!"), 'error');
				$this->redirect('this');
			}
		} else {
			$user = User::getEmailOwner($values['user_email']);
		}

		if (!empty($user)) {
			$user->sendLostpasswordEmail();
			$this->flashMessage(_t("An email has been sent to you with further instructions."));
			$this->redirect("Homepage:default");
		} else {
			$this->flashMessage(_t("This email address is not registered in our system!"), 'error');
			$this->redirect('this');
		}
	}


	/**
	 *	Prepares form where users can set a new password without having to be logged in. (via link)
	 *	@param void
	 *	@return $object
	 */
	protected function createComponentChangelostpasswordform()
	{
		$form = new NAppForm($this, 'changelostpasswordform');
		$form->addPassword('user_password', _t('Password:'))->addRule(NForm::MIN_LENGTH, _t("Your password must contain at least %s characters."), 8)->addRule($form::REGEXP, _t("Your password must contain at least one lower-case letter."), '/[a-z]+/')->addRule($form::REGEXP, _t("Your password must contain at least one upper-case letter."), '/[A-Z]+/')->addRule($form::REGEXP, _t("Your password must contain at least one number."), '/\d+/');
		$form->addPassword('user_password_again', _t('Password again:'))->addRule(NForm::EQUAL, _t("Entered passwords are not the same."), $form['user_password']);
		$form->addSubmit('send', _t('Change password'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'changelostpasswordformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	Processes submitted new password (after clicking link).
	 *	@param object $form
	 *	@return void
	*/
	public function changelostpasswordformSubmitted(NAppForm $form)
	{
		if (StaticModel::checkLoginFailures() === false) {
			die(_t('Too many failed login attempts. Please try again later.'));
		}

		// no authentication because user is not logged in
		$values = $form->getValues();
		if (User::finishPasswordchange($this->user_id, $this->control_key, $values['user_password'])) {
			Activity::addActivity(Activity::USER_PW_CHANGE, $this->user_id, 1);
			$this->flashMessage(_t("Your password has been successfully changed, you can now log in."));
			
			$this->redirect('User:login');
		} else {
			$this->flashMessage(_t("The password couldn't be changed! Try again later."), 'error');
			
			$this->redirect('Homepage:default');
		}
	}


	/**
	 *	Prepares form where logged-in users can change their password.
	 *	@param void
	 *	@return object $form
	 */
	protected function createComponentChangepasswordform()
	{
		$query = NEnvironment::getHttpRequest();
		$user_id = $query->getQuery("user_id");
		$form = new NAppForm($this, 'changepasswordform');
		$form->addPassword('user_password', _t('Password:'))->addRule(NForm::MIN_LENGTH, _t("Your password must contain at least %s characters."), 8)->addRule($form::REGEXP, _t("Your password must contain at least one lower-case letter."), '/[a-z]+/')->addRule($form::REGEXP, _t("Your password must contain at least one upper-case letter."), '/[A-Z]+/')->addRule($form::REGEXP, _t("Your password must contain at least one number."), '/\d+/');
		$form->addPassword('user_password_again', _t('Password again:'))->addRule(NForm::EQUAL, _t("Entered passwords are not the same."), $form['user_password']);
		$form->addHidden('user_id',$user_id);
		$form->addSubmit('send', _t('Change password'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'changepasswordformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	Processes submitted new password (logged-in user)
	 *	@param object $form
	 *	@return void
	 */
	public function changepasswordformSubmitted(NAppForm $form)
	{
		// authentication because user is supposed to be logged in
		if (Auth::isAuthorized(1, $values['user_id']) < 2) {
			die('no permission');
		}

		$values = $form->getValues();
		if (User::changePassword($values['user_id'], $values['user_password'])) {
			Activity::addActivity(Activity::USER_PW_CHANGE, $values['user_id'], 1);
			if ($this->user_id == $values['user_id']) {
				$this->flashMessage(_t("The password has been successfully changed."));
			} else { // different message for change by mod?
				$this->flashMessage(_t("The password has been successfully changed."));
			}
			$this->redirect('User:edit', $values['user_id']);
		} else {
			$this->flashMessage(_t("The password couldn't be changed! Please try again later."), 'error');
			$this->redirect('User:edit', $values['user_id']);
		}
	}


	/**
	 *	Creates the form where users can be reported.
	 *	@param void
	 *	@return object $form
	 */
	protected function createComponentReportform()
	{
		$types = array(
			'0' => _t('This is not a real person but spam.'),
			'1' => _t('This person was created by mistake.'),
			'2' => _t('This person violates the rules of conduct.')
		);
		$form  = new NAppForm($this, 'reportform');
		$form->addRadioList('report_type', _t('Reason:'), $types);
		$form->addTextarea('report_text', _t('Tell us why you report this user, including examples:'))->addRule(NForm::FILLED, _t('Please give us some details.'));
		$form->addSubmit('send', _t('Send'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'reportformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	Processes reports about users.
	 *	@param object $form
	 *	@return void
	 */
	public function reportformSubmitted(NAppForm $form)
	{
		if (!empty($this->user)) {
			if (Auth::isAuthorized(1, $this->user->getUserId()) < 2) {
				die('error');
			}

			$values                  = $form->getValues();
			$resource_data           = array(
				'report_type' => $values['report_type'],
				'reported_object' => 'user',
				'reported_id' => $this->user->getUserId()
			);
			
			$types = array(
				'0' => _t('(spam)'),
				'1' => _t('(error)'),
				'2' => _t('(inappropriate language)')
			);
			
			$reported_user_data      = $this->user->getUserData();
			$data                    = array(
				'resource_name' => sprintf(_t("Report about user %s, reason: %s"), $reported_user_data['user_login'], $types[$resource_data['report_type']]),
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
	 *	Prepares map for Edit User page
	 *	@param string $name
	 *	@return object
	 */
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


	/**
	 *	Creates the form where new users can sign up.
	 *	@param void
	 *	@return object $form
	 */
	protected function createComponentRegisterform()
	{
		$form = new NAppForm($this, 'registerform');
		$form->addText('user_login', _t('Username:'))->addRule(NForm::FILLED, _t('Username cannot be empty!'))->addRule(NForm::MIN_LENGTH, _t('The minimal length of your username is %s characters.'), 3)->addRule($form::REGEXP, _t('Your username can only contain letters, numbers, space, dash and underscore!'), '/^[a-zA-Z0-9 \-_]+$/')->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Please use only the English alphabet.').' '._t('Your username can only contain letters, numbers, space, dash and underscore!'))->id("help-name"));
		$form->addText('user_email', _t('Email:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('To this address we will send a request to confirm your registration.'))->id("help-name"))->addRule($form::REGEXP, _t('Wrong email format!'), '/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/');
		$form->addPassword('user_password', _t('Password:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Your password must be at least 8 characters long and contain at least one lower-case letter, one upper-case letter and one number.'))->id("help-name"))->addRule(NForm::MIN_LENGTH, _t("Your password must contain at least %s characters."), 8)->addRule($form::REGEXP, _t('Your password must contain at least one small letter.'), '/[a-z]+/')->addRule($form::REGEXP, _t('Your password must contain at least one upper-case letter.'), '/[A-Z]+/')->addRule($form::REGEXP, _t("Your password must contain at least one number."), '/\d+/');
		$form->addPassword('password_again', _t('Repeat password:'))->addRule(NForm::EQUAL, _t('Your passwords are different.'), $form['user_password']);
		
		$question = Settings::getVariable('signup_question');
		if ($question) {
			$form->addText('text', _t($question))->addRule(NForm::FILLED, _t('Please enter the text!'));
		}
		$form->addSubmit('register', _t('Sign up'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'registerformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	Processes the return values from the sign up form.
	 *	@param object $form
	 *	@return void
	 */
	public function registerformSubmitted(NAppForm $form)
	{
		if (StaticModel::checkLoginFailures() === false) {
			die(_t('Too many failed login attempts. Please try again later.'));
		}

		$values = $form->getValues();
		
		if (strlen($values['user_password']) > 72) { die('Password must be 72 characters or less'); }

		if (Settings::getVariable('sign_up_disabled')) {
			$this->flashMessage(_t("Sign up is disabled. Please try again later."), 'error');
			$this->redirect("Homepage:default");
		}

		$answer = Settings::getVariable('signup_answer');
		
		if ($answer && isset($values['text'])) {
			if ($answer != $values['text']) {
				sleep(5);
				$this->flashMessage(_t("You entered the wrong captcha."), 'error');
				NEnvironment::getUser()->logout();
				$this->redirect('User:register');
			} else {
				$values['user_captcha_ok'] = 1; //$new_user->setCaptchaOk(true);
			}
		} else {
			$values['user_captcha_ok'] = 1;//$new_user->setCaptchaOk(true);
		}
		if (isset($values['text'])) unset($values['text']);
		
		$login = $values['user_login'];
		if (User::loginExists($login)) {
			$this->flashMessage(_t("User with the same name already exists."), 'error');
			$this->redirect('User:register');
		}
		
		if (User::emailExists($values['user_email'])) {
			$this->flashMessage(_t("This email address is already registered with another account."), 'error');
			$this->redirect('User:register');
		}
		
		$ip = StaticModel::getIpAddress();
		
		if (!StaticModel::validEmail($values['user_email'])) {
			$this->flashMessage(_t("The email address is not valid. Please check it and try again."), 'error');
			$this->redirect('User:register');
		}
		
		if (StaticModel::isSpamSFS($values['user_email'], $ip)) {
			$this->flashMessage(_t("Your email or IP is known at www.stopforumspam.com as spam source and was blocked."), 'error');
			$this->redirect('User:register');
		}

		$password = $values['user_password'];
		
		$new_user = User::create();

		$values['user_password'] = User::encodePassword($values['user_password']);
		unset($values['password_again']);
		$hash                = User::generateHash();
		$values['user_hash'] = $hash;
		
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$values['user_language'] = Language::getId($session->language);
		if (!$values['user_language']) $values['user_language'] = 1;

		$new_user->setUserData($values);
		$new_user->save();
		
		$new_user->setRegistrationDate();
		
		$result = $new_user->sendConfirmationEmail();
		if ($result ) {
			$this->flashMessage(_t("Registration has been successful. A message has been sent to your email with further instructions how to activate your account."));
		} else {
			$this->flashMessage(_t("There has been an error sending the confirmation email. Please try again in a while."), 'error');
		}
		$this->redirect('Homepage:default');
	}


	/**
	 *	Creates form where to answer the security question (captcha). Needed for sign up via mobile or Facebook.
	 *	@param void
	 *	@return object
	 */
	protected function createComponentSecurityquestionform()
	{
		$question = Settings::getVariable('signup_question');
		if (!$question) {
			$this->redirect('Homepage:default');
		}
		$form = new NAppForm($this, 'securityquestionform');
		$form->addText('text', _t($question))->addRule(NForm::FILLED, _t('Please enter the text!'));
		$form->addSubmit('register', _t('Continue'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'securityquestionformSubmitted'
		);
		
		return $form;	
	}


	/**
	 *	Processes the return values of the security question.
	 *	@param object $form
	 *	@return void
	 */
	public function securityquestionformSubmitted(NAppForm $form)
	{
		if (StaticModel::checkLoginFailures() === false) {
			die(_t('Too many failed login attempts. Please try again later.'));
		}

		$values = $form->getValues();
		
		if (Settings::getVariable('sign_up_disabled')) {
			$this->flashMessage(_t("Sign up is disabled. Please try again later."), 'error');
			$this->redirect("Homepage:default");
		}

		$answer = Settings::getVariable('signup_answer');
		
		if ($answer) {
			if ($answer != $values['text']) {
				sleep(5);
				$this->flashMessage(_t("You entered the wrong captcha."), 'error');
				$this->redirect('User:register');
			}
		} else {
			// answer not required
			$this->redirect('User:register');
		}

		$fb = $this->facebook(true);
		if ( $fb === true) {
			$session = NEnvironment::getSession()->getNamespace("GLOBAL");
			$language = Language::getId($session->language);
			if ($language == 0) {
				$language = 1;
			}
			$this->redirect('Homepage:default',  array('language' => $language));
		} else {
			$this->template->FB_LOGIN_URL = $fb;
		}

	}


	/**
	 *	Creates the form to add tags to a user profile.
	 *	@param void
	 *	@return object
	 */
	protected function createComponentTagform()
	{
		$user = NEnvironment::getUser()->getIdentity();
		$form = new NAppForm($this, 'tagform');
		if (isset($this->user) && !is_null($this->user->getUserId()) && (Auth::isAuthorized(1, $user->getUserId()) >= 2)) {
		
			$form->addComponent(new AddTagComponent("user", $this->user->getUserId(), _t("add new tag")), 'add_tag');
			
		} else {
			
			$form->addComponent(new AddTagComponent("user", null, _t("add new tag")), 'add_tag');
			
		}
		return $form;
	}


	/**
	 *	Creates the form to update user information.
	 *	@param void
	 *	@return object
	 */
	protected function createComponentUpdateform()
	{
		$visibility = array(
						1 => 'world',
						2 => 'registered',
						3 => 'friends'
					); // Visibility::getArray();
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
		$form->addText('user_login', _t('Username:'));
		
		if (NEnvironment::getUser()->getIdentity()->getAccessLevel()<2) {
			$form['user_login']->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('The username cannot be changed.'))->id("help-name"))->setDisabled();
		}
		$form->addText('user_name', _t('Name:'));
		$form->addText('user_surname', _t('Surname:'));
		$form->addText('user_phone', _t('Phone:'));
		$form->addText('user_email', _t('Email:'));
		$form->addSelect('user_visibility_level', _t('Visibility:'), $visibility)->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Do you want be visible to everyone (world), only to registered users (registered) or only to your friends (friends)?'))->id("help-name"));
		$form->addSelect('user_send_notifications', _t('Emails on unread messages:'), array('0'=>_t('no'), '1'=>_t('max. once per hour'), '24'=>_t('max. once per day'), '168'=>_t('max. once per week')))->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('You can receive an email when you have unread messages in your inbox.'))->id("help-name"));
		$form->addSelect('user_language', _t('Language:'), $language)->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('The main language you want to use. You will still be able to see other languages.'))->id("help-name"));
		$form->addTextArea('user_description', _t('Description:'), 50, 10)->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Write some lines about your life, your work and your interests.'))->id("help-name"));
		$form->addText('user_url', _t('Homepage:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(_t('Your website, blog or Facebook profile.'))->id("help-name"))->addCondition(~NForm::EQUAL, "")->addRule($form::REGEXP, _t("URL must start with http:// or https://!"), '/^http[s]?:\/\/.+/');
		$form->addFile('user_avatar', _t('Upload Avatar:'))->setOption('description', NHtml::el('img')->src(NEnvironment::getVariable("URI") . '/'  . 'images/help.png')->class('help-icon')->title(sprintf(_t('Avatars are small images that will be visible with your name. Here you can upload your avatar (min. %s, max. %s). In the next step you can crop it.'),"120x150px","2500x2500px") )->id("help-name"))->addCondition(NForm::FILLED)->addRule(NForm::MIME_TYPE, _t('Image must be in JPEG or PNG format.'), 'image/jpeg,image/png')->addRule(NForm::MAX_FILE_SIZE, sprintf(_t('Maximum image size is %s'), "2MB"), 2 * 1024 * 1024);
		
		$form->addProtection(_t('Error submitting form.'));
		 
		$img = $user->getAvatar();
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
		
		
		$form->onSubmit[] = array(
			$this,
			'updateformSubmitted'
		);
		$form->setDefaults($user_data);
		
		return $form;
	}


	/**
	 *	Processes the return values after editing the user profile.
	 *	@param object $form
	 *	@return
	 */
	public function updateformSubmitted(NAppForm $form)
	{
		$values = $form->getValues();
		$logged_user   = NEnvironment::getUser()->getIdentity();

		if (isset($this->user) && !is_null($this->user->getUserId()) && Auth::isAuthorized(1,$logged_user->getUserId()) >= 2) {
			$user = $this->user;
		} elseif (!is_null($logged_user)) {
			$user = $logged_user;
		}
		if (!empty($user)) {
			$data = $user->getUserData();
			if ($data['user_email'] != $values['user_email'] && User::emailExists($values['user_email'])) {
				$this->flashMessage(_t("This email address is already used for another account."), 'error');
				$this->redirect("this");
			}
			if ($data['user_email'] != $values['user_email'] && !StaticModel::validEmail($values['user_email'])) {
				$this->flashMessage(_t("This email address is not valid. Please check it and try again."), 'error');
				$this->redirect("this");
			}
			if ($data['user_email'] != $values['user_email']) {
				$access = $user->getAccessLevel();
				
				// if user (of changed email) is mod or admin or if user (requesting change) has permission lower than moderator: verify old address
				if ($access > 1 || Auth::isAuthorized(Auth::TYPE_USER,$user->getUserId()) < Auth::MODERATOR) {
					$values['user_email_new'] = $values['user_email'];
					$values['user_email']     = $data['user_email'];
					
					$user->sendEmailchangeEmail($values['user_email_new']);
					$this->flashMessage(_t("You requested a change of your email address. A message with a link was sent to your old address. The new address will be activated once you confirmed the change."));
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
				
				if ($size[0]>2500 || $size[1]>2500) {
					$this->flashMessage(sprintf(_t("Image is too big! Max. size is for upload is %s"), "2500x2500"),'error');
				} elseif ($size[0]<80 || $size[1]<100) {
					$this->flashMessage(sprintf(_t("The image is too small! Min. size for upload is %s"), "80x100"),'error');
				} else {
					$user->removeAvatar();
					$user->removeIcons();
					$values['user_portrait'] = base64_encode(file_get_contents($values['user_avatar']->getTemporaryFile()));
				}
			}
			unset($values['user_avatar']);
			$user->setUserData($values);
			if ($user->save()) {
				$this->flashMessage(_t("User updated"));
				Activity::addActivity(Activity::USER_UPDATED, $user->getUserId(), 1);
			}
			
			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage);
			$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId())));
			
			$this->redirect("this");
		}
	}


	/**
	 *	@todo ### Description ???
	 *	@param
	 *	@return
	 */
	public function handleUserAdministration($user_id, $values)
	{
		if (Auth::isAuthorized(1, $user_id) < Auth::MODERATOR) die('no permission');
		$user = User::create($user_id);
		
		$user->setUserData($values);
		$user->save();
		$this->terminate();
	}


	/**
	 *	Lists all joined groups on the User Default page.
	 *	@param string $name
	 *	@return object
	 */
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
			$options['cache_tags'] = array("user_id/".$session->data['object_id']);
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	 *	Lister of subscribed resources on User Default page.
	 *	@param string $name
	 *	@return object
	 */
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
			$options['cache_tags'] = array("user_id/".$session->data['object_id']);
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	 *	Lister of joined groups on User Default page.
	 *	@param string $name
	 *	@return object
	 */
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
			$options['cache_tags'] = array("user_id/".$session->data['object_id']);
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	 *	Lister of joined groups on User Detail page.
	 *	@param string $name
	 *	@return object
	 */
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
			'refresh_path' => 'User:default'
		);
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$options['filter'] = array(
				'user_id' => $session->data['object_id']
			);
			$options['cache_tags'] = array("user_id/".$session->data['object_id']);
			$options['refresh_path_params'] = array('user_id' => $session->data['object_id']);
		}
		
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	 *	Lister of users on User Default page, left column; filtered
	 *	@param string $name
	 *	@return object
	 */
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
				'user_group_resource_page' => true,
				'show_online_status' => true,
				'show_roles' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);		
		return $control;
	}


	/**
	 *	List of friends
	 *	@param string @name
	 *	@return object
	 */
	protected function createComponentFriendlister($name)
	{
		$user    = NEnvironment::getUser()->getIdentity();
		$options = array(
			'itemsPerPage' => 10,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'filter' => array(
				'user_id' => $this->user->getUserId(),
				'sort_by_activity' => true
			),
			'refresh_path' => 'User:default',
			'refresh_path_params' => array(
				'user_id' => $this->user->getUserId()
			),
			'template_variables' => array(
				'connection_columns' => true,
				'show_extended_columns' => true,
				'show_last_activity' => true,
				'format_date_time' => _t("j.n.Y")
				),
			'cache_tags' => array("user_id/".$this->user->getUserId())
		);

		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	 *	Removes a user's avatar, including cached versions.
	 *	@param int $user_id
	 *	@return void
	 */
	public function handleRemoveAvatar($user_id = null)
	{
		$user = NEnvironment::getUser()->getIdentity();
		if (!is_null($user_id) ) {
			if (Auth::isAuthorized(1,$user->getUserId()) >= 2) {
				$user = User::Create($user_id);
			} else {
				die('no permission');
			}
		}
		
		if (empty($user)) {
			$this->terminate();
		}
		
		if (!empty($user)) {
			$image = Image::createimage($user_id,1);
			$image->remove_cache();
			
			$user->removeAvatar();
			$user->removeIcons();
			Activity::addActivity(Activity::USER_UPDATED, $user->getUserId(), 1);
		}
		
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId())));
			
		$this->redirect("this");
	}


	/**
	 *	Adds a tag to a user profile.
	 *	@param int $tag_id
	 *	@param int $user_id
	 *	@return void
	 */
	public function handleInsertTag($tag_id, $user_id = null)
	{
		$user = NEnvironment::getUser()->getIdentity();
		if (!is_null($user_id) ){
			if (Auth::isAuthorized(1,$user->getUserId()) >= 2) {
				$user = User::Create($user_id);
			} else {
				die('no permission');
			}
		}
		
		if (empty($user)) {
			$this->terminate();
		}
		$user->insertTag($tag_id);
		Activity::addActivity(Activity::USER_UPDATED, $user->getUserId(), 1);
		$this->template->user_tags = $this->user->groupSortTags($user->getTags());
		$this->invalidateControl('tagHandle');
	}


	/**
	 *	Removes a tag from a user profile.
	 *	@param int $tag_id
	 *	@param int $user_id
	 *	@return void
	 */
	public function handleRemoveTag($tag_id, $user_id = null)
	{
		$user = NEnvironment::getUser()->getIdentity();
		if (!is_null($user_id)) {
			if (Auth::isAuthorized(1,$user->getUserId()) >= 2) {
				$user = User::Create($user_id);
			} else {
				die('no permission');
			}
		}
		if (empty($user)) {
			$this->terminate();
		}
		$user->removeTag($tag_id);
		Activity::addActivity(Activity::USER_UPDATED, $user->getUserId(), 1);
		$this->template->user_tags = $this->user->groupSortTags($user->getTags());
		$this->invalidateControl('tagHandle');
	}


	/**
	 *	Removes messages with friendship requests
	 *	@param int $message_id
	 *	@return void
	 */
	public function handleRemoveMessage($message_id)
	{
		if (Auth::isAuthorized(3,$message_id) < Auth::MODERATOR) {
			die('no permission');
		}

		// check if it is a message
		$resource_type = Resource::getResourceType($message_id);
		if ($resource_type != 1 && $resource_type != 9 && $resource_type != 10) {
			echo "false";
			$this->terminate();
		}

		$user_id = NEnvironment::getUser()->getIdentity()->getUserId();

		if (Resource::removeMessage($message_id)) {
			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage);
			$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/messagelisteruser")));
			echo "true";
		} else {
			echo "false";
		}

		$this->terminate();	
	}


	/**
	 *	Change to a User Default page via Ajax
	 *	@param int $object_type #### needed?
	 *	@param int $object_id
	 *	@return void
	*/
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
		
		if ($this->isAjax()) {
			$this->actionDefault();
            $this->invalidateControl('mainContent');
            $this->invalidateControl('mainMenu');
		} else {
		  	$this->redirect('this');
		}
	}


	/**
	 *	Change to a User Detail page via Ajax
	 *	@param int $object_type #### needed?
	 *	@param int $object_id
	 *	@return void
	*/
	public function handleDetailPage($object_type, $object_id)
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
		
		if ($this->isAjax()) {
			$this->actionDefault($object_id);
            $this->invalidateControl('mainContent');
            $this->invalidateControl('mainMenu');
		} else {
		  	$this->redirect('this');
		}
	}


	/**
	 *	Create the component for the small map on User details.
	 *	@param string $name
	 *	@return array
	 */
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


	/**
	 *	Cancel a friendship
	 *	@param int $friend_id
	 *	@return
	 */
	public function handleUserFriendRemove($friend_id)
	{
		if (Auth::isAuthorized(1, $user_id) < 1) die('no permission');
		
		if (empty($friend_id)) {
			print "false";
			$this->terminate();
		} else {
			$user   = NEnvironment::getUser()->getIdentity();
			$user_id = $user->getUserId();
			$friend = User::create($friend_id);
			
			if (!empty($friend)) {
				$friend_id = $friend->getUserId();
				if (!empty($friend_id)) {
					$user->removeFriend($friend_id);

					$storage = new NFileStorage(TEMP_DIR);
					$cache = new NCache($storage);
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/friendlister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagefriendlister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagerecommendedfriendlister")));
					$cache->clean(array(NCache::TAGS => array("friend_id/$friend_id", "name/friendlister")));
					$cache->clean(array(NCache::TAGS => array("friend_id/$friend_id", "name/homepagefriendlister")));
					$cache->clean(array(NCache::TAGS => array("friend_id/$friend_id", "name/homepagerecommendedfriendlister")));

					print "true";
				}
			}
		}
		
		$this->terminate();
	}


	/**
	 *	Create the form to send off messages; no processig of results, submitted by own AJAX
	 *	@param
	 *	@return
	 */
	protected function createComponentMessageform()
	{
		$user    = NEnvironment::getUser()->getIdentity();
		$friends = $user->getFriends();
		$friends = array(0=>_t('Please select a recipient'))+$friends;
		$form    = new NAppForm($this, 'messageform');
		$form->addSelect('friend_id', _t('To:'), $friends);
		$form->addTextarea('message_text', '');
		$form->addSubmit('send', _t('Send'));
		$form->addProtection(_t('Error submitting form.'));
		$form->setDefaults(array('friend_id' => 0));		
		$form->onSubmit[] = array(
			$this,
			'messageformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	Form where the user role can be changed
	 *	@param void
	 *	@return array
	 */
	public function createComponentUseradministrator()
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (!empty($session->data)) {
			$user_id = $session->data['object_id'];
		} else {
			return;
		}
		
		$logged_user = NEnvironment::getUser()->getIdentity();
		
		$form         = new NAppForm($this, 'useradministrator');
		$user         = User::create($user_id);
		$user_data    = $user->getUserData();
		$access_level = array(
			'1' => _t('Normal user'),
			'2' => _t('Moderator'),
			'3' => _t('Administrator')
		);
		if (!empty($logged_user) && $logged_user->getAccessLevel() == 3 && $user_id != 1) {
			$form->addSelect('access_level', null, $access_level);
		} elseif ($user_id == 1) {
			$form->addSelect('access_level', null, array('3' => _t('Administrator')));
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
			$form->addSubmit('send', _t('Update'));
		}
		$form->addProtection(_t('Error submitting form.'));
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


	/**
	 *	Process return values of adminUserForm
	 *	@param array $form
	 *	@return void
	 */
	public function adminUserFormSubmitted(NAppForm $form)
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		
		if (!empty($session->data)) {
			$user_id = $session->data['object_id'];

			if ($user_id == 1) {
				$this->flashMessage(_t("Administrator with id 1 cannot be changed"), 'error');
				$this->redirect("User:default");
			}
			$values = $form->getValues();
			
			if (Auth::isAuthorized(1, $user_id) < Auth::MODERATOR) {
				die('error');
			}			
			$user   = User::create($user_id);
			// authentication because user is supposed to be logged in

			foreach ($values as $key => $value) {
				$values["user_" . $key] = $value;
				unset($values[$key]);
			}
			$user->setUserData($values);
			$user->save();
			
			
			$this->flashMessage(_t("User permissions changed."));
			
			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage);
			$cache->clean(array(NCache::TAGS => array("user_id/$user_id")));
				
			$this->redirect("User:default");
		}
	}


	/**
	 *	Does the actual cropping of the user avatar.
	 *	@param int $x
	 *	@param int $y
	 *	@param int $w
	 *	@param int $h
	 *	@param int $user_id
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
		$user_id = $request->getQuery("user_id");

		if (empty($user_id)) {
			$this->flashMessage("Internal error", 'error');
			$this->redirect("User:default");
		}
		
		if (Auth::isAuthorized(1, $user_id) < Auth::MODERATOR) die('no permission');

		if ($user_id == 0 || Auth::isAuthorized(Auth::TYPE_USER, $user_id) < 2) {
			$this->flashMessage(_t("You are not allowed to edit this user."), 'error');
			$this->redirect("User:default",$user_id);
		}
		
		// remove from cache
		$image = Image::createimage($user_id, 1);
		if (!empty($image)) {
			$image->remove_cache();
			$image->crop($x, $y, $w, $h);
			$image->save_data();
			$result = $image->create_cache();
			if ($result !== true) $this->flashMessage($result, 'error');
			$this->flashMessage(_t("Finished cropping and resizing."));
			Activity::addActivity(Activity::USER_UPDATED, $user_id, 1);
		
			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage);
			$cache->clean(array(NCache::TAGS => array("user_id/$user_id")));
		}

		$this->redirect("User:edit",$user_id);
	}


	/**
	 *	Triggered by clicking on a tag; sets filter to this tag
	 *	@param int $tag_id
	 *	@return void
	 */
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
		
		$filter->clearFilterArray();
		
		if (NEnvironment::getVariable("GLOBAL_FILTER")) $filter->syncFilterArray($filterdata); else $session->filterdata=$filterdata;

		$this->redirect("User:default");
	}


	/**
	 *	Tries to get coordinates from human-readable address via Google API
	 *	@param string $string address
	 *	@return array
	*/
	public function lookup_address($string) {
 
	   $string = str_replace (" ", "+", urlencode($string));
	   $details_url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$string."&sensor=false";
 
	   $ch = curl_init();
	   curl_setopt($ch, CURLOPT_URL, $details_url);
	   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	   $response = json_decode(curl_exec($ch), true);
 
	   // If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
	   if ($response['status'] != 'OK') {
			return null;
	   }
 
	   $geometry = $response['results'][0]['geometry'];
 
		$array = array(
			'latitude' => $geometry['location']['lat'],
			'longitude' => $geometry['location']['lng'],
			'location_type' => $geometry['location_type'],
		);
 
		return $array;
 
	}


	/**
	 *	Receives a sent PM
	 *	@param string $message_text
	 *	@param string $recipient_id
	 *	@return
	*/
	public function handleSubmitPMChat($message_text = '', $recipient_id)
	{
		$logged_user = NEnvironment::getUser()->getIdentity();
		
		if ($recipient_id == 0 || !$logged_user->isFriendOf($recipient_id) || empty($message_text)) {
			echo json_encode(_t("Error sending message.")); die();
		}

		$resource                          = Resource::create();
		$data                              = array();
		$data['resource_author']           = $logged_user->getUserId();
		$data['resource_type']             = 1;
		$data['resource_visibility_level'] = 3;
		$data['resource_name'] = '<PM>';
		$data['resource_data']             = json_encode(array(
			'message_text' => $message_text
		));
		$resource->setResourceData($data);
		$check = $resource->check_doublette($data, $recipient_id, 1);
		if ($check === true) {
			echo json_encode(_t("You have just said that.")); die();
		}		
		$resource->save();
		$resource->updateUser($recipient_id, array(
			'resource_user_group_access_level' => 1
		));
		
		$resource->updateUser($logged_user->getUserId(), array(
			'resource_user_group_access_level' => 1,
			'resource_opened_by_user' => 1
		));

		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		$cache->clean(array(NCache::TAGS => array("user_id/".$logged_user->getUserId(), "name/pmwidget")));
		$cache->clean(array(NCache::TAGS => array("user_id/$recipient_id", "name/pmwidget")));
		$cache->clean(array(NCache::TAGS => array("user_id/".$logged_user->getUserId(), "name/pmwidgetslim")));
		$cache->clean(array(NCache::TAGS => array("user_id/$recipient_id", "name/pmwidgetslim")));
		$cache->clean(array(NCache::TAGS => array("user_id/".$logged_user->getUserId(), "name/messagelisteruser")));
		$cache->clean(array(NCache::TAGS => array("user_id/$recipient_id", "name/messagelisteruser")));
		$cache->clean(array(NCache::TAGS => array("user_id/".$logged_user->getUserId(), "name/pmabstract")));
		$cache->clean(array(NCache::TAGS => array("user_id/$recipient_id", "name/pmabstract")));

		echo json_encode("true");
		die();
	}


	/**
	 *	Authentication via Facebook API. Returns $url to FB API for login, true if login succeeded or false if login not yet completed.
	 *	@param bool $captcha TRUE: captcha has been answered correctly, FALSE: no captcha has been answered
	 *	@return string | bool
	 */
	public function facebook($captcha = false) {
  
		$facebook_app_id = NEnvironment::getVariable("FACEBOOK_APP_ID");
		$facebook_app_secret = NEnvironment::getVariable("FACEBOOK_APP_SECRET");
		$facebook_app_url = NEnvironment::getVariable("URI");
		$fb_logout_url = '';
		$fb_login_url = '';
			
		if (!empty($facebook_app_id) && !empty($facebook_app_secret) && !empty($facebook_app_url)) {
			
			include_once LIBS_DIR."/Facebook/facebook.php";
			
			$facebook = new Facebook( array(
				'appId'		=> $facebook_app_id,
				'secret'	=> $facebook_app_secret,
				)
			);

			$fb_login_url = $facebook->getLoginUrl(array(
				'scope'		=> 'email',
				'redirect_uri'	=> $facebook_app_url.'/signin/'
			));

			$fb_user = $facebook->getUser();

			// check if app is authorized
			if ($fb_user) {
				try{
					// Proceed knowing you have a logged in user who's authenticated.
					$user_profile = $facebook->api('/me');
				} catch(FacebookApiException $e) {
					$fb_user = '';
				}
			}
			
			if ($fb_user) {
				$fb_logout_url = $facebook->getLogoutUrl(array(
					'next'	=> $facebook_app_url
				));
				define('FB_LOGOUT_URL', $fb_logout_url);

				// FB user needs email address (no phone-only signups admitted)
				if (empty($user_profile['email'])) {
					$this->flashMessage(_t("Your Facebook account doesn't have an email address."), 'error');
					$this->redirect('Homepage:default');
				}

				// check if username is known (we use same username as on Facebook)
				if (User::loginExists($user_profile['username'])) {
				
					// check if email is known
					if ($user_profile['username'] == User::userloginFromEmail($user_profile['email'])) {
						// name and email match -> let's assume that user is registered (In Facebook we trust.)
						if (Settings::getVariable('sign_in_disabled')) {
							$this->flashMessage(_t("Sign in is disabled. Please try again later."), 'error');
							$this->redirect("Homepage:default");
						}
						$user_e = NEnvironment::getUser();
						$user = User::getEmailOwner($user_profile['email']);
						if (isset($user) && $user->isActive() && $user->isConfirmed()) {
							$user_e->login($user_profile['username'], NULL, 'facebook');	
							$user->setLastActivity();
							return true;
						} else {
							// user not yet confirmed
							$question = Settings::getVariable('signup_question');
					
							if ($question && !$captcha) {
								$this->flashMessage(_t("The administrator asks you to answer a security question before you can enter."));
								$this->redirect('User:captcha');
							} else {
								$user->finishExternalRegistration();
								$user->registerFirstLogin();
								$user_e->login($user_profile['username'], '', 'facebook');	
								$user->setLastActivity();
								return true;
							}
						}
					} else {
						// username exists, but email doesn't match
						$this->flashMessage(_t("User with the same name already exists."), 'error');
						$this->redirect('Homepage:default');
					}
				
				} else {
					// user is new
					// check email (same user cannot log in with same email with two different methods)
					if (User::emailExists($user_profile['email'])) {
						$this->flashMessage(_t("This email address is already registered with another account."), 'error');
						$this->redirect('Homepage:default');
					}
					
					// spam check
					if (StaticModel::isSpamSFS($user_profile['email'], '')) {
						$this->flashMessage(_t("Your email address or IP is known at www.stopforumspam.com as spam source and was blocked."), 'error');
						$this->redirect('Homepage:default');
					}

					// check the security question - skipped if already answered
					if (!$captcha) {
						$question = Settings::getVariable('signup_question');
					
						if ($question) {
							$this->flashMessage(_t("The administrator asks you to answer a security question before you can enter."));
							$this->redirect('User:captcha');
						}
					}
					
					if (Settings::getVariable('sign_up_disabled')) {
						$this->flashMessage(_t("Sign up is disabled. Please try again later."), 'error');
						$this->redirect("Homepage:default");
					} 
					$user = User::create();
					$values['user_login'] = $user_profile['username'];
					$values['user_email'] = $user_profile['email'];
					$values['user_name'] = $user_profile['first_name'];
					$values['user_surname'] = $user_profile['last_name'];
					$values['user_password'] = User::encodePassword(md5(rand())); // create dummy password with sufficient security
					$values['user_hash'] = User::generateHash();
					$values['user_url'] = $user_profile['link'];
					$values['user_visibility_level'] = 2; // by default all signups from Facebook are hidden from the world
					
					// find location
					if (isset($user_profile['location']['name']) && !empty($user_profile['location']['name']))
						$fb_location = $user_profile['location']['name'];					
					elseif (isset($user_profile['hometown']['name']) && !empty($user_profile['hometown']['name']))
						$fb_location = $user_profile['hometown']['name'];
					
					if (isset($fb_location)) {
						$location = $this->lookup_address($fb_location);
						$values['user_position_y'] = $location['longitude'];
						$values['user_position_x'] = $location['latitude'];
					}
										
					// retrieve image
					$fb_img = file_get_contents( "https://graph.facebook.com/".$user_profile['username']."/picture?type=large&height=200&width=160" );
					$avatar_w = 160;
					$avatar_h = 200;
					$avatar = base64_encode(NImage::fromString($fb_img)->resize($avatar_w, $avatar_h)->sharpen()->toString(IMAGETYPE_JPEG,90));

					// make icon and large_icon
					$large_icon_w = 40;
					$large_icon_h = 50;
					$icon_w = 20;
					$icon_h = 25;
					$large_icon = base64_encode(NImage::fromString($fb_img)->resize($large_icon_w, $large_icon_h)->sharpen()->toString(IMAGETYPE_JPEG,90));
					$icon = base64_encode(NImage::fromString($fb_img)->resize($icon_w, $icon_h)->sharpen()->toString(IMAGETYPE_JPEG,90));
					$values['user_portrait'] = $avatar;
					$values['user_largeicon'] = $large_icon;
					$values['user_icon'] = $icon;
					$user->setUserData($values);
					$user->save();
					$user_id = $user->getUserId();
					// generate cache
					$image = Image::createimage($user_id,1);
					$image->create_cache();
			
					$user->setRegistrationDate();
					$user->finishExternalRegistration();
					$user_e = NEnvironment::getUser();
					$user_e->login($user_profile['username'], '', 'facebook');
					Activity::addActivity(Activity::USER_JOINED, $user_id, 1);
					$user->registerFirstLogin();
					$user->setLastActivity();
					
					$answer = Settings::getVariable('signup_answer');
					if ($answer) {
						$user->setCaptchaOk(true);
					} else {
						$user->setCaptchaOk(true);
					}

					$this->flashMessage(_t("Success!"));
					$this->flashMessage(_t("Please check now your profile and enter a description and tags."));
					
					$this->redirect("User:edit", array('user_id' => $user_id, 'registration' => 'facebook'));
				}
				
			} else {
				// user has not authorized Facebook, or is not logged in
				if ($captcha) {
					// If after the captcha screen the Facebook permission got lost
					NEnvironment::getSession()->destroy();
					$this->redirect("Homepage:default");
				}
				return $fb_login_url;
			}
		}
	}

}
