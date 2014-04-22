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
 

final class WidgetPresenter extends BasePresenter
{
	private $object_id = null;


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function isAccessible()
	{
		if ($this->getAction() == "mobilecaptcha") {
			return true;
		}
		return false;
	}



/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function startup()
	{
		parent::startup();
	}


	/**
	 *	@todo Prepares the window content for the group chat to be loaded with AJAX.
	 *	@param string $name namespace
	 *	@return
	 */
	protected function createComponentChatwidget($name)
	{
		$request = NEnvironment::getHttpRequest();
		$last_modified_header = $request->getHeader('if-modified-since');
		$group_id = $request->getQuery('group_id');
		$page = $request->getQuery('page');
		$owner_name = $request->getQuery('owner');


		$group = Group::create($group_id);
		
		$user = NEnvironment::getUser()->getIdentity();
		$user_id = $user->getUserId();
		$user_data = $user->getUserData();
		
		if (!empty($owner_name)) {
			$owner_ids = User::getOwnerIdsFromLogin($owner_name);
		} else {
			$owner_ids = null;
		}
		
		$options = array(
                        'itemsPerPage'=>20,
                        'lister_type'=>array(ListerControlMain::LISTER_TYPE_RESOURCE),
                        'filter' => array(
                        		'type' => 8,
                        		'page' => $page,
								'template_filter' => '',
								'only_active' => true,
								'owner' => $owner_ids,
                        		'all_members_only'=>array(
                        			array(
                        				'type'=>2,
                        				'id'=>$group_id
                        			)
                        		),
								'status' => 1
                        ),
						'template_body'=>'ChatLister_ajax.phtml',
                        'refresh_path'=>'Group:default',
                        'refresh_path_params' => array(
								'group_id' => $group_id
							),
                        'template_variables' => array(
                        		'hide_filter' => 1,
                        		'user_login' => $user_data['user_login'],
                        		'is_member' => $group->isMember($user_id),
                        		'group_id' => $group_id
                        		)
                     );


		$control = new ListerControlMain($this, $name, $options);		
		
		// retrieve time of most recent post for http header
		$data=$control->getPageData($control->getFilterArray($options['filter']));
		$row=reset($data);
		$res = Resource::Create($row['id']);
		$r_data = $res->getResourceData();
		$httpResponse = NEnvironment::getHttpResponse();
		if (isset($r_data)) {
			$date=(array)$r_data['resource_creation_date'];
			date_default_timezone_set($date['timezone']);
			$timestamp=strtotime($date['date']);
			$date_formatted = gmstrftime('%a, %d %b %Y %T %Z',$timestamp);
			$httpResponse->setHeader('Last-Modified', $date_formatted);
		}

		if (isset($timestamp) && $timestamp <= strtotime($last_modified_header)) {
			$httpResponse->setHeader('Last-Modified', $date_formatted);
			$httpResponse->setHeader('Cache-Control', 'no-cache');
			die();
		}

		$httpResponse->setHeader('Cache-Control', 'no-cache');

		return $control;
	}


	/**
	 *	@todo Prepares the window content for the PM chat on /user/messages/ and /user detail to be loaded with AJAX.
	 *	@param string $name namespace
	 *	@return
	 */
	protected function createComponentPmwidget($name)
	{
		$request = NEnvironment::getHttpRequest();
		$last_modified_header = $request->getHeader('if-modified-since');
		$user_id = $request->getQuery('user_id');
		$owner_name = $request->getQuery('owner');
		$page = $request->getQuery('page');
		$trash = $request->getQuery('trash');

		$logged_user_id = NEnvironment::getUser()->getIdentity()->getUserId();
		
		if (!empty($owner_name)) {
			$owner_ids = User::getOwnerIdsFromLogin($owner_name);
			$owner_ids_with_logged = $owner_ids;
			$owner_ids_with_logged[] = $logged_user_id;
		} else {
			$owner_ids = null;
			$owner_ids_with_logged = null;
		}
		
		$options = array(
			'itemsPerPage' => 20,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'PMLister_ajax.phtml',
			'refresh_path'=>'User:messages',
			'filter' => array(
				'page' => $page,
				'owner' => $owner_ids_with_logged,
				'status' => 1
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
		
		
		if ($user_id == NULL ) {
			if ($owner_ids != NULL) {
				$options['filter']['all_members_only'] = array(
						array(
							'type' => 1,
							'id' => NEnvironment::getUser()->getIdentity()->getUserId()
						),
						array(
							'type' => 1,
							'id' => $owner_ids
						)
					);
			} else {
				$options['filter']['all_members_only'] = array(
						array(
							'type' => 1,
							'id' => NEnvironment::getUser()->getIdentity()->getUserId()
						)
					);			
			}
			$options['filter']['type'] = array(
					1, // private messages
					9, // system messages
					10 // friendship requests
				);
		} else {
			// User detail page
			$options['filter']['all_members_only'] = array(
					array(
						'type' => 1,
						'id' => NEnvironment::getUser()->getIdentity()->getUserId()
					),
					array(
						'type' => 1,
						'id' => $user_id
					)
				);
			$options['filter']['type'] = array(
					1, // private messages
					10 // friendship requests
				);
		}

		$session = NEnvironment::getSession()->getNamespace($name);
		
		if ($trash == NULL) {
			if (!isset($session['filterdata']['trash'])) {
				if (is_array($session->filterdata)) {
					$session->filterdata = array_merge(array('trash' => 2), $session->filterdata);
				} else {
					$session->filterdata = array('trash' => 2);
				}
			}
		} else {
			if (is_array($session->filterdata)) {
				$session->filterdata = array_merge($session->filterdata, array('trash' => $trash));
			} else {
				$session->filterdata = array('trash' => $trash);
			}
		}
		
		$control = new ListerControlMain($this, $name, $options);
			
		
		// retrieve time of most recent post for http header
		$data = $control->getPageData($control->getFilterArray($options['filter']));
		$row = reset($data);
		$res = Resource::Create($row['id']);
		$r_data = $res->getResourceData();
		$httpResponse = NEnvironment::getHttpResponse();
		if (isset($r_data)) {
			$date=(array)$r_data['resource_creation_date'];
			date_default_timezone_set($date['timezone']);
			$timestamp = strtotime($date['date']);
			$date_formatted = gmstrftime('%a, %d %b %Y %T %Z',$timestamp);
			$httpResponse->setHeader('Last-Modified', $date_formatted);
		}
		
		if (isset($timestamp) && $timestamp <= strtotime($last_modified_header)) {
			$httpResponse->setHeader('Last-Modified', $date_formatted);
			$httpResponse->setHeader('Cache-Control', 'no-cache');
			die();
		}

		$httpResponse->setHeader('Cache-Control', 'no-cache');

		return $control;
	}


	/**
	 *	@todo Prepares the window content for image browser (called by ckeditor).
	 *	@param void
	 *	@return void
	 */
	public function actionBrowse()
	{
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';
		$query = NEnvironment::getHttpRequest();
		$CKEditorFuncNum = $query->getQuery("CKEditorFuncNum");
		$this->template->CKEditorFuncNum = (int)$CKEditorFuncNum;
		$user = NEnvironment::getUser()->getIdentity();
		if ($user && $user->getAccessLevel() > 0) {
			$user_id = $user->getUserId();
		} else {
			$this->flashMessage('Access denied. Did you sign in?');
			$this->terminate();
		}

		if (NEnvironment::getVariable("EXTERNAL_JS_CSS")) {
			$this->template->load_external_js_css = true;
		}
		$this->template->user_id = $user_id;
		$this->template->user_name = User::getFullName($user_id);
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';

//		$allowed_extensions = array('jpg', 'jpeg', 'gif', 'png');
//		$allowed_types = array('image/jpeg', 'image/gif', 'image/png');
		$image_types = array('image/jpeg', 'image/gif', 'image/png');
		$path = WWW_DIR.'/images/uploads/user-'.$user_id;

		if(!file_exists($path) || !is_dir($path)) {
			mkdir($path);
		}

		// retrieve files
		$file_names = array_diff(scandir($path), array('.','..'));

		$data = array();
		if (count($file_names)) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			foreach ($file_names as $file_name) {
				$file_path = $path.'/'.$file_name;
				$mime_type = finfo_file($finfo, $file_path);
				$modified_date = date(_t("d.m.Y H:i:s"),filemtime($file_path));
				if (in_array($mime_type, $image_types)===true) {
					$image = NImage::fromFile($file_path);
					$data[] = array(
						'file_name' => $file_name,
						'web_path' => NEnvironment::getVariable("URI") . '/images/uploads/user-'.$user_id.'/'.$file_name,
						'width' => $image->width,
						'height' => $image->height,
						'modified_date' => $modified_date,
						'img_b64' => base64_encode($image->resize(110, 100)->toString(IMAGETYPE_JPEG,90)) // no sharpen for CMYK
					);
				} else {
					$extension = pathinfo($file_path, PATHINFO_EXTENSION);
					$icon_web_path = NEnvironment::getVariable("URI") . '/images/mime-types/'.$extension.'.png';
					$data[] = array(
						'file_name' => $file_name,
						'web_path' => NEnvironment::getVariable("URI") . '/images/uploads/user-'.$user_id.'/'.$file_name,
						'modified_date' => $modified_date,
						'src' => $icon_web_path
					);
				}
			}
		}
		$this->template->data = $data;

	}


	/**
	 *	@todo Prepares the window content for the security question
	 *	@param void
	 *	@return
	 */
	public function actionMobilecaptcha()
	{
		$request = NEnvironment::getHttpRequest();
		$control_key = $request->getQuery("control_key");
		$user_id = $request->getQuery("user_id");
		if (empty($control_key) || empty($user_id)) {
			die('Error: missing data');
		}
	}
	

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentSecurityquestionform()
	{
		$question = Settings::getVariable('signup_question');
		if (!$question) {
			$this->redirect('Homepage:default');
		}
		$form = new NAppForm($this, 'securityquestionform');
		$form->addText('text', _t($question))->addRule(NForm::FILLED, _t('Please enter the text!'));
		$request = NEnvironment::getHttpRequest();
		$control_key = $request->getQuery("control_key");
		$form->addHidden('control_key', $control_key);
		$user_id = $request->getQuery("user_id");
		$form->addHidden('user_id', $user_id);
		$form->addSubmit('register', _t('Continue'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'securityquestionformSubmitted'
		);
		
		return $form;	
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function securityquestionformSubmitted(NAppForm $form)
	{
		if (Settings::getVariable('sign_up_disabled')) {
			$this->flashMessage(_t("Sign up is disabled. Please try again later."), 'error');
			$this->redirect("Homepage:default");
		}

		$values = $form->getValues();
		$user_id = $values['user_id'];
		$control_key = $values['control_key'];
		
		$answer = Settings::getVariable('signup_answer');
		
		if ($answer) {
			if ($answer != $values['text']) {
				sleep(5);
				$this->flashMessage(_t("You entered the wrong captcha."), 'error');
				$this->redirect('Widget:mobilecaptcha', array('control_key' => $control_key, 'user_id' => $user_id));
			}
		} else {
			// answer not set
			$this->redirect('User:register');
		}


		if (User::finishRegistration($user_id, $control_key)) {

			// detecting mobile devices
			require_once LIBS_DIR.'/Mobile-Detect/Mobile_Detect.php';
			$detect = new Mobile_Detect;
			if ($detect->isMobile()) $device = 'mobile';
			unset($detect);
			
			// update registration date for correct determination of creation rights
			$user = User::create($user_id);
			$user->setRegistrationDate();
			$user->setCaptchaOk(true);
			
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
				echo _t("The registration couldn't be finished! Link is not active anymore.");
				$this->terminate();
			} else {
				$this->flashMessage(_t("The registration couldn't be finished! Link is not active anymore."), 'error');
				$this->redirect('Homepage:default');
			}
		}

	}

}
