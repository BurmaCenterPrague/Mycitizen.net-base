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

abstract class BasePresenter extends NPresenter
{
	// for Nette FW
	public $oldLayoutMode = FALSE;
	
	public function startup()
	{
		parent::startup();
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}

		$this->template->setTranslator(new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/messages.mo', $language));
		$this->template->language = $language;
		$language_id = Language::getId($language);
		$this->template->language_name = Language::getLanguageName($language_id);
		$this->template->language_code = Language::getLanguageCode($language_id);

		$this->template->intro = WWW_DIR."/files/".$language."/intro.phtml";		
		$this->template->footer = WWW_DIR."/files/".$language."/footer.phtml";
		


		// for gettext
		define('LOCALE_DIR', WWW_DIR . '/../locale');
		setlocale(LC_ALL, $language);

		$domain = "messages";
		bindtextdomain($domain, LOCALE_DIR );
		textdomain($domain);
		bind_textdomain_codeset($domain, 'UTF-8');
		textdomain($domain);
		
		$this->template->PROJECT_NAME = NEnvironment::getVariable("PROJECT_NAME");
		$this->template->PROJECT_DESCRIPTION = NEnvironment::getVariable("PROJECT_DESCRIPTION");
		$this->template->PROJECT_VERSION = PROJECT_VERSION;
		$this->template->URI = NEnvironment::getVariable("URI");
		$this->template->TOC_URL = NEnvironment::getVariable("TOC_URL");
		$this->template->PP_URL = NEnvironment::getVariable("PP_URL");
		$this->template->PIWIK_URL = NEnvironment::getVariable("PIWIK_URL");
		$this->template->PIWIK_ID = NEnvironment::getVariable("PIWIK_ID");
		$this->template->PIWIK_TOKEN = NEnvironment::getVariable("PIWIK_TOKEN");
		if (NEnvironment::getVariable("EXTERNAL_JS_CSS")) $this->template->load_external_js_css = true;
		if (NEnvironment::getVariable("USE_TINYMCE_COMPRESSOR")) $this->template->use_tinymce_compressor = true;

		
		$maintenance_mode = Settings::getVariable('maintenance_mode');
		if ($maintenance_mode) {
			if ($maintenance_mode > time()) {
				$this->flashMessage(sprintf(_("Stand by for scheduled maintenance in %s minutes and %s seconds. Please finish your activities."),date("i",$maintenance_mode-time()),date("s",$maintenance_mode-time())), 'error');
				if (!@file_exists(WWW_DIR.'/.maintenance.php')) {
					file_put_contents(WWW_DIR.'/.maintenance.php',$maintenance_mode);
				}
			} else {
				if (!@file_exists(WWW_DIR.'/.maintenance.php')) {
					Settings::setVariable('maintenance_mode',0);
				} else {
					$this->flashMessage(_("Maintenance mode active."), 'error');
					$user = NEnvironment::getUser()->getIdentity();
					if (isset($user)) {
						$access_level = $user->getAccessLevel();
					} else {
						$access_level = 0;
					}
					if ($access_level < 3) die("Scheduled maintenance. Please return later.");
				}
			}
		}

		
		$this->template->tooltip_position = 'bottom right';
		
		if (!empty($this->template->PIWIK_URL) && !empty($this->template->PIWIK_ID)) {
			$this->template->piwik_url_bare = preg_replace('/https?(:\/\/.*)/i','$1',$this->template->PIWIK_URL);
			if (substr($this->template->piwik_url_bare,-1,1)!='/'){
				$this->template->piwik_url_bare.='/';
			}
		}
		
		
		$user = NEnvironment::getUser();
//		if (!method_exists($user, 'sendConfirmationEmail')) $user->logout();
		if ($user->isLoggedIn()) {
			if (!$user->getIdentity()->isActive()) {
				if ($user->getIdentity()->isConfirmed()) {
					$this->flashMessage(sprintf(_("Your account has been deactivated. If you think that this is a mistake, please contact the support at %s."),NEnvironment::getVariable("SUPPORT_URL")), 'error');
					$user->logout();
					$this->redirect("User:login");
				} else {
					$this->flashMessage(sprintf(_("You first need to confirm your registration. Please check your email account and click on the link of the confirmation email."),NEnvironment::getVariable("SUPPORT_URL")), 'error');
					
					if ($user->sendConfirmationEmail()) {
						$this->flashMessage("We have resent your confirmation email - just in case you didn't get it before.");
					}

					$user->logout();
					$this->redirect("User:login");					
				}
			} else {
			
			}
			$user->getIdentity()->setLastActivity();
			$this->template->logged   = true;
			$userdata                 = $user->getIdentity()->getUserData();
			$this->template->username = $userdata['user_login'];
			$this->template->my_id	= $user->getIdentity()->getUserId();
			$this->template->fullname = trim($userdata['user_name'].' '.$userdata['user_surname']);
			$this->template->image = User::getImage($this->template->my_id,'icon',$this->template->username);

			$user_o = User::create($this->template->my_id);
			$number_tags = count($user_o->getTags());
			$first_login = $user_o->firstLogin();
			$has_position = $user_o->hasPosition();
			
			if ($first_login || (empty($this->template->fullname) && ($number_tags == 0) && (!$has_position))) $this->template->incomplete_profile = true;
		

			$userObject               = NEnvironment::getUser()->getIdentity();
			$access_level = $userObject->getAccessLevel();
			switch ($access_level) {
				case 1: break;
				case 2: $this->template->access_level_welcome =_('You are a moderator on this platform.');break;
				case 3: $this->template->access_level_welcome =_('You are an administrator on this platform.');break;
			}
			if ($access_level == 3 || $access_level == 2) {
				$this->template->admin = true;
			}
			
			$this->template->access_level = $access_level;
			$this->template->messages = Resource::getUnreadMessages();
			$this->template->messages = $this->template->messages ? '<b class="icon-message"></b>'._("New messages").': '.$this->template->messages : '<b class="icon-no-message"></b>'._("New messages").': 0';
			
		} else {
			if (!$this->isAccessible()) {
			
				$this->redirect("User:login");
			}
		}
		$this->registerHelpers();
	}
	
	protected function registerHelpers()
	{
		$this->template->registerHelper('htmlpurify', function ($dirty_html) {
			require_once LIBS_DIR.'/HTMLPurifier/HTMLPurifier.auto.php';
			$config = HTMLPurifier_Config::createDefault();
			$config->set('Attr.EnableID', true);
			$purifier = new HTMLPurifier($config);
			return $purifier->purify($dirty_html);
		});
	}
	
	/**
	 * Messages component factory.
	 * @return mixed
	 */
	protected function createComponentMessages()
	{
		$messages = new FlashMessageControl();
		return $messages;
	}
	
	protected function createComponentMenu($name)
	{
		$presenter = $this->name;
		$menu      = array();
		$menu[1]   = array(
			'title' => _('Users'),
			'presenter' => 'user',
			'action' => 'default',
			'parameters' => array(),
			'parent' => 0
		);
		$menu[2]   = array(
			'title' => _('Groups'),
			'presenter' => 'group',
			'action' => 'default',
			'parameters' => array(),
			'parent' => 0
		);
		$user      = NEnvironment::getUser();
		if ($user->isLoggedIn()) {
			$userObject = NEnvironment::getUser()->getIdentity();
			if ($userObject->hasRightsToCreate() || $userObject->getAccessLevel() >= 2) {
				$menu[3] = array(
					'title' => _('create'),
					'presenter' => 'group',
					'action' => 'create',
					'parent' => 2
				);
			}
		}
		$menu[4] = array(
			'title' => _('Resources'),
			'presenter' => 'resource',
			'action' => 'default',
			'parameters' => array(),
			'parent' => 0
		);
		if ($user->isLoggedIn()) {
			$userObject = NEnvironment::getUser()->getIdentity();
			if ($userObject->hasRightsToCreate() || $userObject->getAccessLevel() >= 2) {
				$menu[5] = array(
					'title' => _('create'),
					'presenter' => 'resource',
					'action' => 'create',
					'parent' => 4
				);
			}
		}
		
		/*
		$menu[6] = array('title'=>_('Help'),
		'presenter'=>'help',
		'action'=>'default',
		'parameters'=>array(),
		'parent'=>0
		);
		*/
		
		foreach ($menu as $key => $menu_data) {
			if (preg_match('/' . $presenter . '/i', $menu_data['presenter'])) {
				if ($menu_data['action'] == "default") {
					$menu[$key]['active'] = true;
				}
			}
		}
		$control = new MenuControl($this, $name, $menu);
		$control->setStyle(MenuCOntrol::MENU_STYLE_CLASSIC);
		$control->setOrientation(MenuControl::MENU_ORIENTATION_HORIZONTAL);
		return $control;
	}
	
	public function handleReloadStatusBar()
	{
		$messages = Resource::getUnreadMessages();
		print $messages ? '<b class="icon-message"></b>'._("New messages").': '.$this->translate_number($messages) : '<b class="icon-no-message"></b>'._("New messages").': '._("0");
		$this->terminate();
	}
	
		
	public function handleReloadTitle()
	{
		$messages = Resource::getUnreadMessages();
		if ($messages > 1) print sprintf(_("%s new messages"),$this->translate_number($messages)).' | ';
		if ($messages == 1) print _("1 new message").' | ';

		$this->terminate();
	}
	
	private function translate_number($in) {
		function translate_array( $matches ) {
			$number = '';
			foreach ((array)array_shift($matches) as $match) {
				$number .= _($match);
			}
			return $number;
		}
		return preg_replace_callback("/(\d)/u","translate_array",strval($in));
	}
	
	
	public function handleReloadChat($group_id)
	{
		$this->terminate();
	}
	
	public function handleSelectLanguage($language)
	{
		$session = NEnvironment::getSession()->getNamespace("GLOBAL");
		$flag    = Language::getFlag($language);
		if (!empty($flag)) {
			$session->language = $flag;
		}
		$this->redirect("this");
	}
	
	public function visit($type_id, $object_id)
	{
		$ip_address = $_SERVER['REMOTE_ADDR'];
		if ($type_id == 1) {
			$user = User::create($object_id);
			if (!empty($user)) {
				if (StaticModel::ipIsRegistered($type_id, $object_id, $ip_address)) {
					$user->incrementVisitor();
				}
			}
		}
		if ($type_id == 2) {
			$group = Group::create($object_id);
			if (!empty($group)) {
				if (StaticModel::ipIsRegistered($type_id, $object_id, $ip_address)) {
					$group->incrementVisitor();
				}
			}
		}
		if ($type_id == 3) {
			$resource = Resource::create($object_id);
			if (!empty($resource)) {
				$user = NEnvironment::getUser()->getIdentity();
				if (!empty($user)) {
					$user_id = $user->getUserId();
					$resource->setOpened($user_id);
				}
				if (StaticModel::ipIsRegistered($type_id, $object_id, $ip_address)) {
					$resource->incrementVisitor();
				}
			}
		}
		
	}
	
	public function removeImage($id,$type) {
		if ($type == 1 ) {
			$object = User::create($id);
		} elseif ($type == 2 ) {
			$object = Group::create($id);
		} else {
			echo _("Error deleting image from cache.");
		}

		if (isset($object)) {
			$sizes = array('img', 'icon', 'large_icon');
		
			foreach ( $sizes as $size) {
				switch ($size) {
					case 'img': $src = $object->getAvatar(); break;
					case 'icon': $src = $object->getIcon(); break;
					case 'large_icon': $src = $object->getBigIcon(); break;
					default: $this->terminate(); break;
				}

				if (!empty($src)) {
					$hash=md5($src);
		
					if ($type == 1 ) {
						$link = WWW_DIR.'/images/cache/user/'.$id.'-'.$size.'-'.$hash.'.jpg';
					} elseif ($type == 2 ) {
						$link = WWW_DIR.'/images/cache/group/'.$id.'-'.$size.'-'.$hash.'.jpg';
					} else $this->terminate();	

					if(file_exists($link)) {
						if (!unlink($link)) {
							echo _("Error deleting image from cache: ").$link;
						}
					}
				}
			}
		}
	}

	public function handleImage($id,$type,$redirect=true) {
	
		if ($type == 1 ) {
			$object = User::create($id);
		} elseif ($type == 2 ) {
			$object = Group::create($id);
		} else {
			$this->flashMessage(_("Error recreating image."), 'error');
		}
		
		if (isset($object)) {
		
			$sizes = array('img', 'icon', 'large_icon');
		
			foreach ( $sizes as $size) {
		
				switch ($size) {
					case 'img': $src = $object->getAvatar(); break;
					case 'icon': $src = $object->getIcon(); break;
					case 'large_icon': $src = $object->getBigIcon(); break;
				}
	
				if (!empty($src)) {
					$hash=md5($src);
		
					if ($type == 1 ) {
						$link = WWW_DIR.'/images/cache/user/'.$id.'-'.$size.'-'.$hash.'.jpg';
					} elseif ($type == 2 ) {
						$link = WWW_DIR.'/images/cache/group/'.$id.'-'.$size.'-'.$hash.'.jpg';
					} else $this->terminate();			
		
					if(!file_exists($link)) {
						$img_r = imagecreatefromstring(base64_decode($src));
						if (!imagejpeg($img_r, $link)) {
							$this->flashMessage(_("Error writing image: ").$link, 'error');
						};
					}
				}

			}

		}
		
		if ($redirect) {
			if ($type == 1 ) {
				$this->redirect("User:default", $id);
			} elseif ($type == 2 ) {
				$this->redirect("Group:default", $id);
			}
		}
	}
	
	protected function isAccessible()
	{
		return false;
	}

	/**
	*	callback called by GrabzIt server to retrieve the thumbnail;
	*	cannot be in ResourcePresenter.php because of permissions for visibility-restricted resources
	*/
	public function handleSaveScreenshot($id, $resource_id = null, $md5 = null) {
		if (isset($md5) && isset($resource_id)) {
			$resource_id = (int) $resource_id;
			$md5 = filter_var($md5, FILTER_SANITIZE_STRING);

			$app_key = NEnvironment::getVariable("GRABZIT_KEY");
			$app_secret = NEnvironment::getVariable("GRABZIT_SECRET");
		
			if (!empty($app_key) && !empty($app_secret)) {
				include(LIBS_DIR.'/GrabzIt/GrabzItClient.class.php');
				$grabzIt = new GrabzItClient($app_key, $app_secret);
			}
			
			$result = $grabzIt->GetResult($id);
			if ($result) {
				$filepath = WWW_DIR.'/images/cache/resource/'.$resource_id.'-screenshot-'.$md5.'.jpg';
				file_put_contents($filepath, $result);
			}

			$this->terminate();
		}
	}
	
	
	/**
	*	processes scheduled tasks; sends email notifications;
	*	needs to be called with /?do=cron&token=xyz
	*	token is set in config.ini
	* 	adding &verbose=1 will output some more info
	*/
	public function handleCron($token, $verbose = null) {
	
		// check permission to execute cron
		$token_config = NEnvironment::getVariable("CRON_TOKEN");
		if ($token != $token_config) {
			echo 'Access denied.';
			$this->terminate();
		}

		$sender_name = NEnvironment::getVariable("PROJECT_NAME");
		$uri = NEnvironment::getVariable("URI");
		
		$options = 'From: '.$sender_name.' <' . Settings::getVariable("from_email") . '>' . "\n" . "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit";
			
		$mail_subject = '=?UTF-8?B?' . base64_encode(sprintf(_('Notification from %s'), $sender_name)) . '?=';
		
		$result = dibi::fetchAll("SELECT `cron_id`, `time`, `recipient_type`, `recipient_id`, `text`, `object_type`, `object_id`, `executed_time` FROM `cron` WHERE `time` < %i AND `executed_time` = 0", time());
		
		foreach ($result as $task) {


			switch ($task['recipient_type']) {
		
			case '1': // user
				$email = dibi::fetchSingle("SELECT `user_email` FROM `user` WHERE `user_id` = %i", $task['recipient_id']);

				switch ($task['object_type']) {
					case 0: $link = $uri.'/user/messages/'; break;
					case 1: $link = $uri.'/user/?user_id='.$task['object_id']; break;
					case 2: $link = $uri.'/group/?group_id='.$task['object_id']; break;
					case 3: $link = $uri.'/resource/?resource_id='.$task['object_id']; break;
					default: $link = $uri; break;
				}
			
				$mail_body = $task['text'];
				$mail_body .= "\r\n\n"._('Find more information at:')."\r\n";
				$mail_body .= $link;
				$mail_body .= "\r\nYours,\r\n".$sender_name."\r\n\r\n";
			
				if (mail($email, $mail_subject, $mail_body, $options)) {
			
					if (isset($verbose)) {
						echo 'Cron task #'.$task['cron_id'].': Email sent to '.$email.'<br/>';
					}

					dibi::query("UPDATE `cron` SET `executed_time` = %i WHERE `cron_id` = %i", time(), $task['cron_id']);
				
				} elseif (isset($verbose)) {
					echo 'Cron task #'.$task['cron_id'].': Problem sending email to '.$email.'<br/>';
				}
			break;
			case '2': // group
				// get all members
				$group = Group::create($task['recipient_id']);
				$group_name = Group::getName($task['recipient_id']);
				$filter = array(
						'enabled' => 1
					);
				$users_a = $group->getAllUsers($filter);

				switch ($task['object_type']) {
					case 0: $link = $uri.'/user/messages/'; break;
					case 1: $link = $uri.'/user/?user_id='.$task['object_id']; break;
					case 2: $link = $uri.'/group/?group_id='.$task['object_id']; break;
					case 3: $link = $uri.'/resource/?resource_id='.$task['object_id']; break;
					default: $link = $uri; break;
				}

				$mail_body = $task['text'];
				$mail_body .= "\r\n\r\n".sprintf(_('You receive this message as a member of the group "%s".'),$group_name);
				$mail_body .= "\r\n\r\n"._('Find more information at:')."\r\n";
				$mail_body .= $link;
				$mail_body .= "\r\n\r\nYours,\r\n\r\n".$sender_name."\r\n\r\n";

				// send emails				
				foreach ($users_a as $user_a) {
				
					$email = $user_a['user_email'];

					if (mail($email, $mail_subject, $mail_body, $options)) {
			
						if (isset($verbose)) {
							echo 'Cron task #'.$task['cron_id'].': Email sent to '.$email.' (member of group '.$group_name.')<br/>';
						}

						dibi::query("UPDATE `cron` SET `executed_time` = %i WHERE `cron_id` = %i", time(), $task['cron_id']);
				
					} elseif (isset($verbose)) {
						echo 'Cron task #'.$task['cron_id'].': Problem sending email to '.$email.' (member of group '.$group_name.')<br/>';
					}
					
				}
								
			break;
			}
			
		}


		if (isset($verbose)) {
			echo 'Queuing notifications to be sent ...<br/>';
		}

		$users_a = User::getAllUsersForCron();
		if (is_array($users_a)) {
			foreach ($users_a as $user_a) {
				if (User::getUnreadMessages($user_a['user_id'])) {
					$email_text = sprintf(_("Dear %s"), $user_a['user_login']). ",\n\n";
					$email_text .= _("You have unread messages!");
//					$email_text .= "\n\n";
//					$email_text .= "("._("You can change your notification settings in your profile.").")";
					StaticModel::addCron(time(), 1, $user_a['user_id'], $email_text, 0, 0);
					User::setUserCronSent($user_a['user_id']);
					if (isset($verbose)) {
						echo 'User with id '.$user_a['user_id'].' will receive a notification about unread messages.<br/>';
					}
				}
			}
		}
		
		if (isset($verbose)) {
			echo 'Done.<br/>';
		}
		$this->terminate();
	}

}
