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

abstract class BasePresenter extends NPresenter
{
	// needed by Nette FW
	public $oldLayoutMode = FALSE;

	/**
	 *	loads basic parameters from settings, prepares general template variables and checks if user is allowed to view page
	 *	@param void
	 *	@return void
	 */
	public function startup()
	{
		parent::startup();
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$query = NEnvironment::getHttpRequest();
		$lang = $query->getQuery("language");
		if (isset($lang) && !empty($lang)) {
			$flag = Language::getFlag($lang);
			if (!empty($flag)) $language = $flag;
			$session->language = $language;
		}

		if (empty($language)) $language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}

		$this->template->setTranslator(new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/messages.mo', $language));
		$this->template->language = $language;
		$language_id = Language::getId($language);
		$this->template->language_name = Language::getLanguageName($language_id);
		$this->template->language_code = Language::getLanguageCode($language_id);

		if (file_exists(WWW_DIR."/files/".$language."/intro.phtml")) {
			$this->template->intro = WWW_DIR."/files/".$language."/intro.phtml";
		} else {
			$this->template->intro = '';
		}
		
		if (file_exists(WWW_DIR."/files/".$language."/footer.phtml")) {
			$this->template->footer = WWW_DIR."/files/".$language."/footer.phtml";
		} else {
			$this->template->footer = '';
		}
		
		setlocale(LC_ALL, $language);

		$this->template->PROJECT_NAME = NEnvironment::getVariable("PROJECT_NAME");
		$this->template->PROJECT_DESCRIPTION = NEnvironment::getVariable("PROJECT_DESCRIPTION");
		$this->template->PROJECT_VERSION = PROJECT_VERSION;
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';
		$this->template->TC_URL = NEnvironment::getVariable("TC_URL");
		$this->template->PP_URL = NEnvironment::getVariable("PP_URL");
		$this->template->PIWIK_URL = NEnvironment::getVariable("PIWIK_URL");
		$this->template->PIWIK_ID = NEnvironment::getVariable("PIWIK_ID");
		$this->template->PIWIK_TOKEN = NEnvironment::getVariable("PIWIK_TOKEN");
		if (NEnvironment::getVariable("EXTERNAL_JS_CSS")) $this->template->load_external_js_css = true;
		if (NEnvironment::getVariable("USE_TINYMCE_COMPRESSOR")) $this->template->use_tinymce_compressor = true;

		
		$maintenance_mode = Settings::getVariable('maintenance_mode');
		if ($maintenance_mode) {
			if ($maintenance_mode > time()) {
				$this->flashMessage(sprintf(_t("Stand by for scheduled maintenance in %s minutes and %s seconds. Please finish your activities."),date("i",$maintenance_mode-time()),date("s",$maintenance_mode-time())), 'error');
				if (!@file_exists(WWW_DIR.'/.maintenance.php')) {
					file_put_contents(WWW_DIR.'/.maintenance.php',$maintenance_mode);
				}
			} else {
				if (!@file_exists(WWW_DIR.'/.maintenance.php')) {
					Settings::setVariable('maintenance_mode',0);
				} else {
					$this->flashMessage(_t("Maintenance mode active."), 'error');
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
					$this->flashMessage(sprintf(_t("Your account has been deactivated. If you think that this is a mistake, please contact the support at %s."),NEnvironment::getVariable("SUPPORT_URL")), 'error');
					$user->logout();
					$this->redirect("User:login");
				} else {
					$this->flashMessage(sprintf(_t("You first need to confirm your registration. Please check your email account and click on the link of the confirmation email."),NEnvironment::getVariable("SUPPORT_URL")), 'error');
					
					if ($user->getIdentity()->sendConfirmationEmail()) {
						$this->flashMessage(_t("We have resent your confirmation email - just in case you didn't get it before."));
					}

					$user->logout();
					$this->redirect("User:login");					
				}
			} else {
				// nothing
			}
			$user->getIdentity()->setLastActivity();
			$this->template->logged   = true;
			$userdata                 = $user->getIdentity()->getUserData();
			$this->template->username = $userdata['user_login'];
			$this->template->my_id	= $user->getIdentity()->getUserId();
			$this->template->fullname = trim($userdata['user_name'].' '.$userdata['user_surname']);
			$this->template->image = User::getImage($this->template->my_id,'icon'); // don't use 2nd param because tooltip interferes with mouseover to keep drawer open

			$user_o = User::create($this->template->my_id);
			$number_tags = count($user_o->getTags());
			$first_login = $user_o->firstLogin();
			$has_position = $user_o->hasPosition();
			
			if ($first_login || (empty($this->template->fullname) && ($number_tags == 0) && (!$has_position))) $this->template->incomplete_profile = true;
		

			$this->template->messages = Resource::getUnreadMessages();
			$this->template->messages = $this->template->messages ? '<b class="icon-message"></b>'._t("New messages").': '.$this->template->messages : '<b class="icon-no-message"></b>'._t("New messages").': 0';
			
		} else {
			if (!$this->isAccessible()) {
				$this->flashMessage("Please sign in first.");
				$redirect = urlencode($_SERVER['REQUEST_URI']);
				if (preg_match('/^\/(signin)|(signup)|(user\/logout)\//i', $redirect) === true) {
					$redirect = '';
				}
				$this->redirect("User:login", array("redirect" => $redirect));
			}
		}
		$this->registerHelpers();
	}


	/**
	 *	@Helpers for Latte templates
	 *	+ htmlpurify: cleans code before output based on whitelist
	 *	+ autoformat: adds basic html formatting into plain text and applies simplified htmlpurify
	 *	@param void
	 *	@return void
	 */
	protected function registerHelpers()
	{
		require_once LIBS_DIR . '/HTMLPurifier/HTMLPurifier.auto.php';
			
		$this->template->registerHelper('htmlpurify', function ($dirty_html) {
			$config = HTMLPurifier_Config::createDefault();
			$config->set('Attr.EnableID', true);
			$config->set('Attr.IDBlacklistRegexp', '/^(?!((quoting_\d+)|(reply\d+))).*/'); // blacklisting all id attributes that don't start with "quoting_" followed by a number
			$config->set('HTML.Nofollow', true);
			$config->set('HTML.Allowed', 'h2,h3,h4,a[href|target|rel],strong,b,div,br,img[src|alt|height|width|style],dir,span[style],blockquote[id],ol,ul,li[type],pre,u,hr,code,strike,sub,sup,p[style],table,tr,td[colspan],th,iframe[src|width|height|frameborder]');
			$config->set('Attr.AllowedFrameTargets', array('_blank', '_top'));
			$config->set('HTML.SafeIframe', true);
			$config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www.youtube.com/embed/.*)|(player.vimeo.com/video/)%'); //allow YouTube and Vimeo
			$config->set('Filter.YouTube', true);
			$purifier = new HTMLPurifier($config);
			return $purifier->purify($dirty_html);
		});
		
		$this->template->registerHelper('autoformat', function ($input) {
			// make links clickable
			// credits: http://stackoverflow.com/a/1188652 (modified)
			$output = '';
			$validTlds = array_fill_keys(explode(" ", ".aero .asia .biz .cat .com .coop .edu .gov .info .int .jobs .mil .mobi .museum .name .net .org .pro .tel .travel .ac .ad .ae .af .ag .ai .al .am .an .ao .aq .ar .as .at .au .aw .ax .az .ba .bb .bd .be .bf .bg .bh .bi .bj .bm .bn .bo .br .bs .bt .bv .bw .by .bz .ca .cc .cd .cf .cg .ch .ci .ck .cl .cm .cn .co .cr .cu .cv .cx .cy .cz .de .dj .dk .dm .do .dz .ec .ee .eg .er .es .et .eu .fi .fj .fk .fm .fo .fr .ga .gb .gd .ge .gf .gg .gh .gi .gl .gm .gn .gp .gq .gr .gs .gt .gu .gw .gy .hk .hm .hn .hr .ht .hu .id .ie .il .im .in .io .iq .ir .is .it .je .jm .jo .jp .ke .kg .kh .ki .km .kn .kp .kr .kw .ky .kz .la .lb .lc .li .lk .lr .ls .lt .lu .lv .ly .ma .mc .md .me .mg .mh .mk .ml .mm .mn .mo .mp .mq .mr .ms .mt .mu .mv .mw .mx .my .mz .na .nc .ne .nf .ng .ni .nl .no .np .nr .nu .nz .om .pa .pe .pf .pg .ph .pk .pl .pm .pn .pr .ps .pt .pw .py .qa .re .ro .rs .ru .rw .sa .sb .sc .sd .se .sg .sh .si .sj .sk .sl .sm .sn .so .sr .st .su .sv .sy .sz .tc .td .tf .tg .th .tj .tk .tl .tm .tn .to .tp .tr .tt .tv .tw .tz .ua .ug .uk .us .uy .uz .va .vc .ve .vg .vi .vn .vu .wf .ws .ye .yt .yu .za .zm .zw .xn--0zwm56d .xn--11b5bs3a9aj6g .xn--80akhbyknj4f .xn--9t4b11yi5a .xn--deba0ad .xn--g6w251d .xn--hgbk6aj7f53bba .xn--hlcj6aya9esc7a .xn--jxalpdlp .xn--kgbechtv .xn--zckzah .arpa"), true);

			$position = 0;
			$rexProtocol = '(https?://)?';
			$rexDomain   = '((?:[-a-zA-Z0-9]{1,63}\.)+[-a-zA-Z0-9]{2,63}|(?:[0-9]{1,3}\.){3}[0-9]{1,3})';
			$rexPort     = '(:[0-9]{1,5})?';
			$rexPath     = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
			$rexQuery    = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
			$rexFragment = '(#[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
			while (preg_match("{\\b$rexProtocol$rexDomain$rexPort$rexPath$rexQuery$rexFragment(?=[?.!,;:\"]?(\s|$))}i", $input, &$match, PREG_OFFSET_CAPTURE, $position))
			{
				list($url, $urlPosition) = $match[0];

				$output .= substr($input, $position, $urlPosition - $position);

				$domain = $match[2][0];
				$port   = $match[3][0];
				$path   = $match[4][0];
				$query	= $match[5][0];
				
				$tld = strtolower(strrchr($domain, '.'));
				if (preg_match('{\.[0-9]{1,3}}', $tld) || isset($validTlds[$tld]))
				{
					$completeUrl = $match[1][0] ? $url : "http://$url";
					$output .= sprintf('<a href="%s" target="_blank">%s</a>', htmlspecialchars($completeUrl), htmlspecialchars("$domain$port$path$query"));
				}
				else
				{
					$output .= htmlspecialchars($url);
				}
				$position = $urlPosition + strlen($url);
			}
			$output .= substr($input, $position);

			// convert tabs to &nbsp;
			$output = preg_replace("/\t/", " &nbsp;&nbsp;&nbsp;&nbsp;", $output);

			// convert spaces at beginning of newlines to &nbsp;
			// $output = preg_replace("/\n(\s*)\s+/", "\n$1&nbsp;", $output);

			// convert newlines to br tags
			$output = nl2br($output);

			// purify with simple options
			$config = HTMLPurifier_Config::createDefault();
			$config->set('HTML.Allowed', 'h2,h3,h4,a[href|target|rel],strong,b,br,dir,span[style],ol,ul,li[type],pre,u,hr,strike,sub,sup');
			$config->set('Attr.AllowedFrameTargets', array('_blank', '_top'));
			$purifier = new HTMLPurifier($config);
			$output = $purifier->purify($output);

			return $output;
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


	/**
	 *	Creates main menu
	 *	@param string $name
	 *	@return object
	 */
	protected function createComponentMenu($name)
	{
		$presenter = $this->name;
		$menu      = array();
		$menu[1]   = array(
			'title' => _t('Users'),
			'presenter' => 'user',
			'action' => 'default',
			'parameters' => array(),
			'parent' => 0
		);
		$menu[2]   = array(
			'title' => _t('Groups'),
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
					'title' => _t('create'),
					'presenter' => 'group',
					'action' => 'create',
					'parent' => 2
				);
			}
		}
		$menu[4] = array(
			'title' => _t('Resources'),
			'presenter' => 'resource',
			'action' => 'default',
			'parameters' => array(),
			'parent' => 0
		);
		if ($user->isLoggedIn()) {
			$userObject = NEnvironment::getUser()->getIdentity();
			if ($userObject->hasRightsToCreate() || $userObject->getAccessLevel() >= 2) {
				$menu[5] = array(
					'title' => _t('create'),
					'presenter' => 'resource',
					'action' => 'create',
					'parent' => 4
				);
			}
		}
		
		if ($user->isLoggedIn()) {
			$userObject = NEnvironment::getUser()->getIdentity();
			$access_level = $userObject->getAccessLevel();
			if ($access_level == 3 || $access_level == 2) {
				$menu[6] = array('title'=>_t('Administration'),
				'presenter'=>'administration',
				'action'=>'default',
				'parameters'=>array(),
				'parent'=>0
				);
			}
		}
		
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


	/**
	 *	Returns data on AJAX request for new message indicator and for html <title> element
	 *	@param void
	 *	@return JSON array/object
	 */
	public function handleReloadStatusBar()
	{
		$data = array();
		$messages = Resource::getUnreadMessages();
		$data['message_indicator'] = $messages ? '<b class="icon-message"></b>'._t("New messages").': '.$this->translate_number($messages) : '<b class="icon-no-message"></b>'._t("New messages").': '._t("0");
		if ($messages > 1) {
			$data['title'] = sprintf(_t("%s new messages"),$this->translate_number($messages)).' | ';
		} elseif ($messages == 1) {
			$data['title'] = _t("1 new message").' | ';
		} else {
			$data['title'] = '';
		}
		echo json_encode($data);
		$this->terminate();
	}


	/**
	 *	Helper for Burmese (Myanmar) numbers where digits need to be translated one-by-one
	 *	@param int $in
	 *	@return int
	 */
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


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
/*
	public function handleReloadChat($group_id)
	{
### USED?
		$this->terminate();
	}
*/

	/**
	 *	Sets the language used on the UI (saved in session)
	 *	@param int $language
	 *	@return void
	 */
	public function handleSelectLanguage($language)
	{
		$session = NEnvironment::getSession()->getNamespace("GLOBAL");
		$flag    = Language::getFlag($language);
		if (!empty($flag)) {
			$session->language = $flag;
		}
		$this->redirect("this");
	}


	/**
	 *	Visitor counter
	 *	@param int $type_id
	 *	@param int $object_id
	 *	@return void
	 */
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


	/**
	 *	Creates cached versions of images on the server.
	 *	@param int $id
	 *	@param int $type
	 *	@param bool $redirect
	 *	@return
	*/
	public function handleImage($id, $type, $redirect=true) {
		$image = new Image($id,$type);
		$result = $image->remove_cache();
		if ($result !== true) $this->flashMessage($result,'error');
		$result = $image->create_cache();
		if ($result !== true) $this->flashMessage($result,'error');

		if ($redirect) {
			if ($type == 1 ) {
				$this->redirect("User:default", $id);
			} elseif ($type == 2 ) {
				$this->redirect("Group:default", $id);
			}
		}
	}


	/**
	 *	Default for accessibility of pages: only with login
	 *	@param void
	 *	@return boolean
	 */
	protected function isAccessible()
	{
		return false;
	}


	/**
	 *	Callback called by GrabzIt server to retrieve the thumbnail;
	 *	(Cannot be in ResourcePresenter.php because of permissions for visibility-restricted resources.)
	 *	@param string $id identifier of Grabz.it
	 *	@param int $resource_id
	 *	@param string $md5 own identifier
	 *	@return void
	 */
	public function handleSaveScreenshot($id = null, $resource_id = null, $md5 = null) {
		if (isset($id)) {
			if (isset($md5) && isset($resource_id)) {
				$resource_id = (int) $resource_id;
				$md5 = filter_var($md5, FILTER_SANITIZE_STRING);

				$app_key = NEnvironment::getVariable("GRABZIT_KEY");
				$app_secret = NEnvironment::getVariable("GRABZIT_SECRET");
		
				if (!empty($app_key) && !empty($app_secret)) {
					include(LIBS_DIR.'/GrabzIt/GrabzItClient.class.php');
					$grabzIt = new GrabzItClient($app_key, $app_secret);
			
					$result = $grabzIt->GetResult($id);
					if ($result) {
						$filepath = WWW_DIR.'/images/cache/resource/'.$resource_id.'-screenshot-'.$md5.'.jpg';
						file_put_contents($filepath, $result);
						echo "true";
					}
				} else {
					echo "Please enter app key and app secret.";
				}
			} else {
				echo "Number of parameters is wrong.";
			}
		}
		else {
			echo "No id for GetResult().";
		}

		$this->terminate();
	}
	
	
	/**
	*	Processes scheduled tasks; sends email notifications;
	*	needs to be called with /?do=cron&token=xyz
	*	token is set in config.ini
	* 	adding &verbose=1 will output some more info
	 *	@param
	 *	@return
	*/
	public function handleCron($token, $verbose = null) {
	
		// check permission to execute cron
		$token_config = NEnvironment::getVariable("CRON_TOKEN");
		if ($token != $token_config) {
			echo 'Access denied.';
			$this->terminate();
		}

		$cron = new Cron;
		$cron->verbose = $verbose;
		$cron->run();
		$this->terminate();
	}


	/**
	*	uploads images to user's folder on the server, called by ckeditor
	 *	@param void
	 *	@return void
	*/
	public function handleUpload() {
		$user = NEnvironment::getUser();
		if (!$user->isLoggedIn()) die('You are not logged in.');
		
		$allowed_extensions = array('jpg','jpeg','gif','png', 'pdf', 'odt', 'doc', 'docx', 'xls', 'ods', 'txt', 'rtf', 'ppt', 'pptx', 'odp');
		$allowed_types = array('image/jpeg', 'image/gif', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.presentation', 'application/vnd.oasis.opendocument.spreadsheet');
		$image_types = array('image/jpeg', 'image/gif', 'image/png');
		$path = '/images/uploads';
		$max_size = 3000000;
		$max_width = 800;
		$max_height = 2500;

		$query = NEnvironment::getHttpRequest();
		$file_info = $query->getFile('upload');
		$file_name = $file_info->getName();
		$funcNum = $query->getQuery('CKEditorFuncNum');
		if (!$funcNum) die();
		

		$user = NEnvironment::getUser()->getIdentity();
		if ($user) {
			$user_id = $user->getUserId();
			$path .= '/user-'.$user_id;
		} else {
			die('Did you sign in?');
		}

		if(!file_exists(WWW_DIR . $path)) {
			mkdir(WWW_DIR . $path);
		}

		$name = pathinfo($file_name, PATHINFO_FILENAME);
		$ext = pathinfo($file_name, PATHINFO_EXTENSION);
		if (in_array($ext, $allowed_extensions)===false) {
			$message = 'ERROR: wrong extension';
		} elseif (in_array($file_info->getContentType(), $allowed_types)===false) {
			$message = 'ERROR: wrong file type';
		} elseif ($file_info->getSize()==0) {
			$message = 'ERROR: The image is too small!';
		} elseif ($file_info->getSize()>$max_size) {
			$message = 'ERROR: The image is too big!';
		} elseif (!is_uploaded_file($file_info->getTemporaryFile())) {
			$message = 'ERROR: security check';
		} else {
			$rel_url = sprintf( "%s/%s.%s", $path, $name, $ext);

			// check if file exists
			$appendix = 0;
			while (file_exists( WWW_DIR . $rel_url)) {
				$appendix++;
				$rel_url = sprintf( "%s/%s-%s.%s", $path, $name, $appendix, $ext);
			}

			if (move_uploaded_file($file_info->getTemporaryFile(), WWW_DIR . $rel_url)) {
		
				if (in_array($file_info->getContentType(), $image_types)===true) {
					// resize
					$image = NImage::fromFile(WWW_DIR . $rel_url);
					$width = $image->width;
					$height = $image->height;
					if ($width > $max_width || $height > $max_height) {
						$image->resize($max_width, $max_height);
						$image->save(WWW_DIR . $rel_url);
					}
				}
				$url = NEnvironment::getVariable("URI") . $rel_url;
				echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', '$message');</script>";
			} else {
				$message = 'ERROR: cannot move file';
			}
		}

		die();
	}


	/**
	*	deletes images in user's folder on the server, called from image browser
	 *	@param string $file_name
	 *	@param int $user_id
	 *	@return void
	*/
	public function handleDeleteFile($file_name, $user_id) {
		$user_env = NEnvironment::getUser();		
		if (!$user_env->isLoggedIn()) die('You are not logged in.');
		$user = $user_env->getIdentity();
		if ($user->getUserId() != $user_id && $user->getAccessLevel() < 2) die('No permission');
		$allowed_extensions = array('jpg','jpeg','gif','png', 'pdf', 'odt', 'doc', 'docx', 'xls', 'ods', 'txt', 'rtf', 'ppt', 'pptx', 'odp');
		$ext = pathinfo($file_name, PATHINFO_EXTENSION);
		if (in_array($ext, $allowed_extensions)===false) die('Wrong extension.');
				
		$file_path = WWW_DIR . '/images/uploads/user-'.$user_id.'/'.$file_name;

		unlink($file_path);
		echo "true";
		die();
	}


	/**
	 *	Translates recent dates into "today" and "yesterday".
	 *	@param int $timestamp Unix timestamp
	 *	@return string
	 */
	private function relativeTime($timestamp) {
	
		if (date('Ymd') == date('Ymd', $timestamp)) {
			return _t('Today');
		}

		if (date('Ymd', strtotime('yesterday')) == date('Ymd', $timestamp)) {
			return _t('Yesterday');
		}
		
		return date('j M Y', $timestamp);
	}


	/**
	 *	Creates HTML output of recent activity
	 *
	 *	@param int $id describes time range: today, yesterday, week, month
	 *	@return void
	 */
	public function handleActivity($id = 2, $latest = 0) {
		$user = NEnvironment::getUser()->getIdentity();
		if (!isset($user)) $this->terminate();
		
		$user_id = $user->getUserId();

		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Activity.stream");
		$cache->clean();
		$cache_key = $user_id.'-'.$id.'-'.$latest;
		if ($cache->offsetExists($cache_key)) {
			$output = $cache->offsetGet($cache_key);
			echo $output;
			$this->terminate();
		}
		
		$header_time = '';		
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
		
		$now = time();
		// get timeframe from id
		switch ($id) {
			case 2: $header_time = _t("Today"); $from = strtotime('today midnight'); $to = null; break;
			case 3: $header_time = _t("Yesterday"); $from = strtotime('yesterday midnight'); $to = strtotime('today midnight'); break;
			case 4: $header_time = _t("Week"); $from = strtotime('7 days ago midnight'); $to = strtotime('yesterday midnight'); break;
			default: $header_time = _t("Month"); $from = strtotime('1 month ago midnight'); $to = strtotime('7 days ago midnight'); break;
		}

		ob_start();
		// for scrolling
		echo '<div id="activity-scroll-target-'.$id.'"></div>';
		
		echo "<h3>".$header_time."</h3>";

		$activities = Activity::getActivities($user_id, $from, $to, $latest);

		if (isset($activities) && count($activities)) {

			foreach ($activities as $activity) {
			
				if ($activity['activity'] != Activity::NOTICEBOARD_MESSAGE) {
					switch ($activity['object_type']) {
						case 1:
							if (!empty($activity['affected_user_id']) && $activity['object_id'] == $user_id) $object_id = $activity['affected_user_id']; else $object_id = $activity['object_id'];
							$object_link = NEnvironment::getVariable("URI").'/user/?user_id='.$object_id;
							$object_icon = User::getImage($object_id, 'icon');
							$object_name = User::getUserLogin($object_id);
							$time = $this->relativeTime($activity['timestamp']);
						break;
						case 2:
							$object_link = NEnvironment::getVariable("URI").'/group/?group_id='.$activity['object_id'];
							$object_icon = Group::getImage($activity['object_id'], 'icon');
							$object_name = Group::getName($activity['object_id']);
							$time = $this->relativeTime($activity['timestamp']);
						break;
						case 3:
							$object_link = NEnvironment::getVariable("URI").'/resource/?resource_id='.$activity['object_id'];
							$object_icon = '<b class="icon-' . $resource_name[Resource::getResourceType($activity['object_id'])] . '"></b>';
							$object_name = Resource::getName($activity['object_id']);;
							$time = $this->relativeTime($activity['timestamp']);
						break;
					}
			
					switch ($activity['activity']) {
						case Activity::USER_JOINED:
							if ($activity['object_id'] == $user_id) {
								$description = _t('You signed up.');
							} else {
								$description = _t('A new user has signed up.');
							}
						break;
						case Activity::FRIENDSHIP_REQUEST:
							if ($activity['object_id'] != $user_id) {
								$description = sprintf(_t('User %s requested your friendship.'),$object_name);
							} else {
								$description = sprintf(_t('You requested %s\'s friendship.'),$object_name);
							}
						break;
						case Activity::FRIENDSHIP_YES:
							if ($activity['object_id'] != $user_id) {
								$description = sprintf(_t('User %s accepted your friendship.'),$object_name);
							} else {
								$description = sprintf(_t('You accepted %s\'s friendship.'),$object_name);
							}
						break;
						case Activity::FRIENDSHIP_NO:
							if ($activity['object_id'] != $user_id) {
								$description = sprintf(_t('User %s rejected your friendship.'),$object_name);
							} else {
								$description = sprintf(_t('You rejected %s\'s friendship.'),$object_name);
							}
						break;
						case Activity::FRIENDSHIP_END:
							if ($activity['object_id'] != $user_id) {
								$description = sprintf(_t('User %s canceled your friendship.'),$object_name);
							} else {
								$description = sprintf(_t('You canceled the friendship with %s.'),$object_name);
							}
						break;

						case Activity::GROUP_JOINED: $description = _t('You joined the group.'); break;
						case Activity::RESOURCE_SUBSCRIBED: $description = _t('You subscribed to the resource.'); break;
						case Activity::GROUP_LEFT: $description = _t('You left the group.'); break;
						case Activity::RESOURCE_UNSUBSCRIBED: $description = _t('You unsubscribed from the resource.'); break;
						case Activity::GROUP_RESOURCE_ADDED: $description = _t('The group added a resource.'); break;
						case Activity::GROUP_CHAT: $description = _t('A new chat message was posted in the group.'); break;
						case Activity::RESOURCE_COMMENT: $description = _t('The resource has new comments.'); break;
						case Activity::GROUP_CREATED: $description = _t('A new group was created.'); break;
						case Activity::RESOURCE_CREATED: $description = _t('A new resource was created.'); break;
						case Activity::USER_UPDATED: $description = _t('You updated your profile.'); break;
						case Activity::GROUP_RESOURCE_REMOVED: $description = _t('The group unsubscribed from a resource.'); break;
						case Activity::GROUP_UPDATED: $description = _t('The group was updated.'); break;
						case Activity::RESOURCE_UPDATED: $description = _t('The resource was updated.'); break;
						case Activity::LOGIN_FAILED: $description = _t('Somebody tried to login with your name and a wrong password.'); break;
						case Activity::USER_PW_CHANGE: $description = _t('Your password was changed.'); break;
						case Activity::GROUP_PERMISSION_CHANGE: $description = _t('Your permissions of the group were changed.'); break;
						case Activity::RESOURCE_PERMISSION_CHANGE: $description = _t('Your permissions of the resource were changed.'); break;
						default: $description = 'Unspecified activity'; break;
					}
				}
				if ($activity['activity'] == Activity::NOTICEBOARD_MESSAGE) {
					$message_o = Resource::create($activity['object_id']);
					if (!empty($message_o) && $message_o->isActive()) {
						$data = $message_o->getResourceData();
						$config = HTMLPurifier_Config::createDefault();
						$config->set('Attr.EnableID', true);
						$config->set('Attr.IDBlacklistRegexp', '/^(?!((quoting_\d+)|(reply\d+))).*/'); // blacklisting all id attributes that don't start with "quoting_" followed by a number
						$config->set('HTML.Nofollow', true);
						$config->set('HTML.Allowed', 'h2,h3,h4,a[href|target|rel],strong,b,div,br,img[src|alt|height|width|style],dir,span[style],blockquote[id],ol,ul,li[type],pre,u,hr,code,strike,sub,sup,p[style],table,tr,td[colspan],th,iframe[src|width|height|frameborder]');
						$config->set('Attr.AllowedFrameTargets', array('_blank', '_top'));
						$config->set('HTML.SafeIframe', true);
						$config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www.youtube.com/embed/.*)|(player.vimeo.com/video/)%'); //allow YouTube and Vimeo
						$config->set('Filter.YouTube', true);
						$purifier = new HTMLPurifier($config);
						$message_text = $purifier->purify($data['message_text']);
						$time = $this->relativeTime($activity['timestamp']);
						printf('<div class="activity-item" style="cursor:default;"><div class="activity-time"><h4>%s</h4></div><div class="activity-description"><h4>'._t("Message to all users").'</h4></div><div style="clear:both;"></div><div class="activity-time"></div><div class="activity-description" style="max-width:80%%;">%s</div></div>', $time, $message_text);
					}
				} else {
					printf('<div class="activity-item" onclick="window.location=\'%s\'"><div class="activity-time"><h4>%s</h4></div><div class="activity-link"><h4><a href="%s">%s %s</a></h4></div><div class="activity-description"><h4>%s</h4></div></div>', $object_link, $time, $object_link, $object_icon, $object_name, $description);
				}
			}
		} else {
			echo '<div class="activity-item" style="cursor:default;"><h4>'._t("Nothing to display").'</h4></div>';
		}
		
		if ($id < 5) {
			$id_inc = $id +1;
			echo '
	<div id="load-more-'.$id .'">
		<p><a href="javascript:void(0);" id="load_more" class="button">'._t("load more...").'</a></p>
	</div>';
			echo '
	<script>
		$("#load_more").click(function(){
			loadActivity("#load-more-'.$id.'", '.$id_inc.', '.$latest.');
		});
	</script>
			';
		}
		$output = ob_get_contents();
		ob_end_clean();
		echo $output;
		if ($id == 2) $time = time()+120; else $time = time()+3600;
		$cache->save($cache_key, $output, array(NCache::EXPIRE => $time));
		$this->terminate();
	}


	/**
	 *	Echoes JSON-encoded array of events for the logged-in user
	 *
	 *	@param int $start UNIX timestamp for begin of time frame
	 *	@param int $end UNIX timestamp for end of time frame
	 *	@return void
	 */
	public function handleGetevents($start=null,$end=null) {
		$user_env = NEnvironment::getUser();		
		if (!$user_env->isLoggedIn()) die('You are not logged in.');
		$user_id = $user_env->getIdentity()->getUserId();
		
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Calendar.events");
		$cache->clean();
		$cache_key = $user_id.'-'.$start.'-'.$end;
		if ($cache->offsetExists($cache_key)) {
			$array_feed_items = $cache->offsetGet($cache_key);
			echo json_encode($array_feed_items);
			die();
		}

		$filter = array('user_id'  => $user_id, 'type' => array(2));
  		$events = Administration::getData(array(ListerControlMain::LISTER_TYPE_RESOURCE), $filter);
  		
  		$array_feed_items = array();
		foreach ($events as $event) {
			$event_start = strtotime($event["resource_data"]['event_timestamp']);
			$event_end = strtotime($event["resource_data"]['event_timestamp_end']);
  			if (!isset($start) || ($event_start > $start && $event_end < $end)) {
				$array_feed_item['id'] = $event['id'];
				$array_feed_item['title'] = $event['name'];
				
				if (isset($event['resource_data']['event_allday']) && $event["resource_data"]['event_allday']) {
					$array_feed_item['start'] = strtotime("midnight",$event_start);
					$array_feed_item['end'] = $event_end;
				} else {
					$array_feed_item['start'] = $event_start;
					$array_feed_item['end'] = $event_end;
				}
				
				$array_feed_item['allDay'] = (bool) $event["resource_data"]['event_allday'];
				
				if ($user_id == $event['author']) {
					$array_feed_item['color'] = '#E13C20';
					$array_feed_item['textColor'] = '#fff';
				} else {
					$array_feed_item['color'] = '#4680B3';
					$array_feed_item['textColor'] = '#fff';
				}
				
				if ($event_start-$event['resource_data']['event_alert'] < time()) {
					$array_feed_item['borderColor'] = '#000';
				} else {
					$array_feed_item['borderColor'] = $array_feed_item['color'];
				}
				$array_feed_item['url'] = NEnvironment::getVariable("URI") . '/resource/?resource_id=' . $event['id'];
				if (Auth::MODERATOR <= Auth::isAuthorized(3, $event['id'])) {
					$array_feed_item['editable'] = true;
				}

				if ( isset($event['resource_data']['event_allday']) && $event['resource_data']['event_allday']) {
					$start_h_m = _t('All-day event');
					$end_h_m = '';
					$timezone = '';				
				} else {
					$start_h_m = date(_t('g:ia'),$event_start);
					$timezone = date("e",$event_start);
					if ($event["resource_data"]['event_timestamp'] != $event["resource_data"]['event_timestamp_end']) {
						$end_h_m = date(_t('g:ia'),$event_end);
					} else {
						$end_h_m = '';
					}
				}
				
				$separator = ($end_h_m) ? "-" : "";
				$class = array(1=>'world',2=>'registered',3=>'person');
				$visibility_icon = '<b class="icon-'.$class[$event['visibility_level']].'"></b>';
				$resource = Resource::create($event['id']);
				if ($resource->hasPosition()) {
					$location_icon = '<img src="{$baseUri}/images/icon-pin.png"/>';
				} else {
					$location_icon = '';
				}
				$array_feed_item['description'] = "<b>".$event['name']."</b><br/><i>".$start_h_m." ".$separator." ".$end_h_m." ".$timezone."</i><br/><br/>".$event['description'].'<br/><br/><span style="float:right">'.User::getImage($event['author'],'icon').$visibility_icon.$location_icon."</span>";
				$array_feed_items[] = $array_feed_item;
				unset($resource);
			}
		}
		echo json_encode($array_feed_items);
		$cache->save($cache_key, $array_feed_items, array(NCache::EXPIRE => time()+120));
		die();
	}


	/**
	 *	Handler for changes to events by drag and drop on FullCalendar (home page)
	 *
	 *	@param string $changed
	 * 	@param int $resource_id
	 *  @param int $day_delta
	 *  @param int $minute_delte
	 *  @param bool $allday
	 *	@return void
	 */
	public function handleChangeevent($changed,$resource_id,$day_delta=0,$minute_delta=0,$allday=null) {
		if (Auth::isAuthorized(3,$resource_id)<Auth::MODERATOR) die('You are not authorized.');
		$resource = Resource::create($resource_id);
		$resource_data = $resource->getResourceData();

		if ($resource_data['resource_type']!=2) die('This is not an event.');
		
		switch ($changed) {
			case 'start':
				if (isset($allday)) {
					if ($allday=='true') {
						$resource_data['event_allday'] = 1;
					} elseif ($allday=='false') {
						$resource_data['event_allday'] = 0;
					}
				}
				$timestamp = strtotime($resource_data['event_timestamp']);
				$timestamp_end = strtotime($resource_data['event_timestamp_end']);
				If ($timestamp_end == 0) $timestamp_end = strtotime($resource_data['event_timestamp']);
				$string = sprintf("%s day %s minutes", $day_delta, $minute_delta);
				$resource_data['event_timestamp'] = date("r",strtotime($string, $timestamp));
				$resource_data['event_timestamp_end'] = date("r",strtotime($string, $timestamp_end));

				// schedule alarm
				if ($timestamp + 3600 > time()) { // back-schedule max 60 mins.
					// get all subscribers
					$members = $resource->getAllMembers(array('enabled'=>1));
					if (count($members)) foreach ($members as $member) {
						Cron::addCron($timestamp - $resource_data['event_alert'], $member['member_type'], $member['member_id'], $resource_data['resource_name']."\r\n\n".$resource_data['resource_description'], 3, $resource_id);
					}
				} else {
					// event has been changed with time in the past: don't send alerts
					Cron::removeCron(0, 0, 3, $resource_id);
				}
			break;
			case 'end':
				$timestamp_end = strtotime($resource_data['event_timestamp_end']);
				If ($timestamp_end == 0) $timestamp_end = strtotime($resource_data['event_timestamp']);
				$string = sprintf("%s day %s minutes", $day_delta, $minute_delta);
				$resource_data['event_timestamp_end'] = date("r",strtotime($string, $timestamp_end));
			break;
			default:
				die();
			break;
		}
		$resource_data = array(
			'event_description' => $resource_data['event_description'],
			'event_url' => $resource_data['event_url'],
			'event_allday' => $resource_data['event_allday'],
			'event_timestamp' => $resource_data['event_timestamp'],
			'event_timestamp_end' => $resource_data['event_timestamp_end'],
			'event_alert' => $resource_data['event_alert']
		);		
		$data['resource_data'] = json_encode($resource_data);

		$resource->setResourceData($data);
		if ($resource->save()) echo "true";
		die();
	}

	/**
	 *	Handler to return the online status of users (ListerControlMain_users.phtml)
	 *	@param int $show_date Whether to show the date when users who is offline was "last seen", or nothing
	 *	@param int $span Whether to show the date wrapped in a span
	 *	@return json object/array of user_id => text 
	 */
	public function handleOnlineStatus($show_date = 1, $span = 1) {
		$queries = NEnvironment::getHttpRequest()->getQuery();
		if (!isset($queries['data'])) {
			echo "false";
			die();
		}
		
		$data = json_decode($queries['data']);

		if (!isset($data) || count($data) < 1) {
			echo "false";
			die();
		}
		
		foreach ($data AS $object_id => &$value) {
			if (Auth::USER <= Auth::isAuthorized(1, $object_id)) {
					$format_date_time = _t("j.n.Y");
					$last_activity = User::getRelativeLastActivity($object_id, $format_date_time);

					if ($span == 0) {
						$value = $last_activity['last_seen'];
					} elseif ($last_activity['online']) {
						if ($show_date) {
							$value = ' ';
						} else {
							$value = '<span style="color:#0A0;font-size:2em;margin-top:-4px;" title="'._t('now online').'">&#149</span>';
						}
					} else {
						if ($show_date) {
							$value = '<span style="top:5px;position:relative;" id="activity_'.$object_id.'">'.$last_activity['last_seen'].'</span>';
						} else {
							$value = ' ';
						}
					}
			}
		}
		echo json_encode($data);
		die();
	}


}
