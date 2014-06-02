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
	// Apparently needed by Nette FW.
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
		$request = NEnvironment::getHttpRequest();
		
/*
		// we are making history
		if ($this->isAjax()){
			if ($request->getQuery('do') != 'historyback' && $request->getQuery('go') != 'back') {
				if (!isset($session->history)) $session->history = array();	
				$session->history[] = array( 'presenter_name' => $this->presenter->getName(), 'presenter_view' => $this->presenter->getView(), 'object_id' => $session->data['object_id'], 'query' => $request->getQuery());
			}
		} elseif ($request->getQuery('go') != 'back') {
			unset($session->history);
		}
*/
		
		$lang = $request->getQuery("language");
		if (isset($lang) && !empty($lang) && !$this->isAjax()) {
			$flag = Language::getFlag($lang);
			if (!empty($flag)) $language = $flag;
			$session->language = $language;
		}
		if (empty($language)) $language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}
		$this->template->setTranslator(new GettextTranslator(LOCALE_DIR . '/' . $language . '/LC_MESSAGES/messages.mo', $language));
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

		$this->template->nochat = $request->getQuery("nochat");		
		$toggleChat = $request->getQuery("toggleChat");
		if (isset($toggleChat)) {
				$session = NEnvironment::getSession()->getNamespace("GLOBAL");
				if (isset($session->chat)) {
					$session->chat = ($session->chat === true) ? false : true;
				} else {
					$session->chat = true;
				}
				$message = "error";
				$message = ($session->chat === true) ? _t("Chat window turned on.") : _t("Chat window turned off.");
				$this->flashMessage($message);

				$this->invalidateControl('popupchat');
				$this->redirect('this');
		}

		if (isset($session->chat) && $session->chat) {
			$this->template->popup_chat = APP_DIR.'/components/popup_chat.phtml';
		}
		
		$this->template->PROJECT_NAME = NEnvironment::getVariable("PROJECT_NAME");
		$this->template->PROJECT_DESCRIPTION = NEnvironment::getVariable("PROJECT_DESCRIPTION");
		$this->template->PROJECT_VERSION = PROJECT_VERSION;
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';
		$this->template->baseUri_np = preg_replace('#^https?://#', '//', NEnvironment::getVariable("URI") . '/');
		$this->template->CDN = NEnvironment::getVariable("CDN") . '/';
		$this->template->TC_URL = NEnvironment::getVariable("TC_URL");
		$this->template->PP_URL = NEnvironment::getVariable("PP_URL");
		$this->template->PIWIK_URL = NEnvironment::getVariable("PIWIK_URL");
		$this->template->PIWIK_ID = NEnvironment::getVariable("PIWIK_ID");
		$this->template->PIWIK_TOKEN = NEnvironment::getVariable("PIWIK_TOKEN");
		if (NEnvironment::getVariable("EXTERNAL_JS_CSS")) $this->template->load_external_js_css = true;
		
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

		if (!empty($this->template->PIWIK_URL) && !empty($this->template->PIWIK_ID)) {
			$this->template->piwik_url_bare = preg_replace('/https?(:\/\/.*)/i','$1',$this->template->PIWIK_URL);
			if (substr($this->template->piwik_url_bare,-1,1)!='/'){
				$this->template->piwik_url_bare.='/';
			}
		}

		$user_env = NEnvironment::getUser();
		if ($user_env->isLoggedIn()) {
			$user = $user_env->getIdentity();
			if (!$user->isActive()) {
				if ($user->isConfirmed()) {
					$this->flashMessage(sprintf(_t("Your account has been deactivated. If you think that this is a mistake, please contact the support at %s."),NEnvironment::getVariable("SUPPORT_URL")), 'error');
					$user_env->logout();
					$this->redirect("User:login");
				} else {
					$this->flashMessage(sprintf(_t("You first need to confirm your registration. Please check your email account and click on the link of the confirmation email."),NEnvironment::getVariable("SUPPORT_URL")), 'error');
					
					if ($user->sendConfirmationEmail()) {
						$this->flashMessage(_t("We have resent your confirmation email - just in case you didn't get it before."));
					}

					$user_env->logout();
					$this->redirect("User:login");					
				}
			} else {
				// nothing
			}
			$user->setLastActivity();
			$this->template->logged   = true;
			$userdata                 = $user->getUserData();
			$this->template->username = $userdata['user_login'];
			$this->template->my_id	= $user->getUserId();
			$this->template->fullname = trim($userdata['user_name'].' '.$userdata['user_surname']);
			$this->template->image = User::getImage($this->template->my_id,'icon'); // don't use 2nd param because tooltip interferes with mouseover to keep drawer open

			$number_tags = count($user->getTags());
			$first_login = $user->firstLogin();
			$has_position = $user->hasPosition();
			
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

		// js and css that needs to be combined. Observe right order!
		$scripts = new Scripts;
		$scripts->setBaseOriginUrl(NEnvironment::getVariable("CDN"));
		$scripts->setBaseTargetUrl(NEnvironment::getVariable("URI"));
		$scripts->setBaseTargetPath(WWW_DIR);
		$css = array(
			'css/mycitizen.min.css',
			'css/jquery.fancybox.min.css'
			);
		$scripts->queueScript('css', $css);

		if (empty($this->template->load_external_js_css)) {
			$scripts->queueScript('js', 'js/jquery-1.10.2.min.js');
		}
		$scripts->queueScript('css', 'css/jquery.qtip.min.css');
		$scripts->queueScript('js', 'js/jquery.qtip.min.js');
		if (isset($this->template->logged) && empty($this->template->load_external_js_css)) {
			$scripts->queueScript('js', 'js/fullcalendar.min.js');
			$scripts->queueScript('js', 'js/gcal.js');
			$scripts->queueScript('js', 'js/jquery.Jcrop.min.js');
			$scripts->queueScript('css', 'css/fullcalendar.css');
			$scripts->queueScript('css', 'css/jquery.Jcrop.min.css');
		}
		if (isset($this->template->logged)) {
			$scripts->queueScript('css', 'css/jquery.tagit.css');
			$scripts->queueScript('css', 'css/tagit.ui-zendesk.css');
			$scripts->queueScript('css', 'css/jquery.tree.css');
			$scripts->queueScript('css', 'css/jquery.calendarPicker.min.css');
			$scripts->queueScript('js', 'js/jquery.calendarPicker.min.js');
			$scripts->queueScript('js', 'js/jquery.tree.min.js');
		}
		$js = array(
			'js/jquery-ui-1.10.4.min.js',
			'js/jquery.nette.js',
			'js/jquery.fancybox.min.js',
			'js/base-functions.min.js',
			'js/jquery.json-2.2.min.js',
			'js/tag-it.min.js',
			'js/jquery.fancybox.min.js'
		);
		$scripts->queueScript('js', $js);
		$this->template->embed_js = $scripts->outputScripts('js');
		$this->template->embed_css = $scripts->outputScripts('css');
	}


	/**
	 *	Helpers for Latte templates
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
			$config->set('HTML.Allowed', 'h2,h3,h4,a[href|target|rel],strong,b[class|title],div,br,img[src|alt|height|width|style|title],dir,span[style],blockquote[id],ol,ul,li[type],pre,u,hr,code,strike,sub,sup,p[style],table,tr,td[colspan],th,iframe[src|width|height|frameborder]');
			$config->set('Attr.AllowedFrameTargets', array('_blank', '_top'));
			$config->set('HTML.SafeIframe', true);
			$config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www.youtube.com/embed/.*)|(player.vimeo.com/video/)%'); //allow YouTube and Vimeo
			$config->set('Filter.YouTube', true);
			$purifier = new HTMLPurifier($config);
			$text = $purifier->purify($dirty_html);
			
			// converting references
			$pattern = array(
				'/(\s|^|>)(@{1,3}[^@\s<>"\'!?,:;()]+@?)([!?.,:;()\Z\s<])/u',
				'/(\s|^|>)(#[^#\s<>"\'!?,:;()]+#?)([!\?\,:;()\Z\s<])/u'
			);
			$text = preg_replace_callback(
				$pattern,
				function($lighter){
					$title = '';
					$lighter[2] = trim($lighter[2]);
					if (preg_match("/^@([0-9]+)@?/", $lighter[2], $ids) === 1) {
						if (Auth::isAuthorized(1, $ids[1]) > Auth::UNAUTHORIZED) {
							$label = User::getFullName($ids[1]);
							$link = 'user/?user_id='.$ids[1];
							$title = _t("go to '%s'", $label);
						}
					} elseif (preg_match("/^@@([0-9]+)@?/", $lighter[2], $ids) === 1) {
						if (Auth::isAuthorized(2, $ids[1]) > Auth::UNAUTHORIZED) {
							$label = Group::getName($ids[1]);
							$link = 'group/?group_id='.$ids[1];
							$title = _t("go to '%s'", $label);
						}
					} elseif (preg_match("/^@@@([0-9]+)@?/", $lighter[2], $ids) === 1) {
						if (Auth::isAuthorized(3, $ids[1]) > Auth::UNAUTHORIZED) {
							$label = Resource::getName($ids[1]);
							$link = 'resource/?resource_id='.$ids[1];
							$title = _t("go to '%s'", $label);
						}
					}
					If (!isset($label) || !isset($link)) {
						$label = str_replace('_',' ',$lighter[2]);
						$link = '?do=search&string='.urlencode($lighter[2]);
						$title = _t("search for '%s'", $label);
					}
					if (!isset($lighter[3])) {
						$lighter[3] = '';
					}
					return $lighter[1].'<a href="'.NEnvironment::getVariable("URI").'/'.$link.'" title="'.$title.'">'.$label.'</a>'.$lighter[3];
				},
				$text);
			
			$smileys = array(
					':-o' => 'omg_smile.png',
					':-O' => 'omg_smile.png',
					':-)' => 'regular_smile.png',
					':)' => 'regular_smile.png',
					';-)' => 'wink_smile.png',
					';)' => 'wink_smile.png',
					':-(' => 'sad_smile.png',
					':(' => 'sad_smile.png',
					':-D' => 'teeth_smile.png',
					':D' => 'teeth_smile.png',
					':-P' => 'tongue_smile.png',
					':P' => 'tongue_smile.png',
					'(n)' => 'thumbs_down.png',
					'(y)' => 'thumbs_up.png',
					'8-)' => 'shades_smile.png',
					'<3' => 'heart.png'
				);
			array_walk($smileys, function(&$value, $key){
				$value='<img src="'.NEnvironment::getVariable("URI").'/js/ckeditor/plugins/smiley/images/'.$value.'"/>';
			});
			$text = strtr($text, $smileys);

			return $text;
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
			while (preg_match("{\\b$rexProtocol$rexDomain$rexPort$rexPath$rexQuery$rexFragment(?=[?.!,;:\"]?(\s|$))}i", $input, $match, PREG_OFFSET_CAPTURE, $position))
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
			$config->set('HTML.Allowed', 'h2,h3,h4,a[href|target|rel],strong,b[class|title],br,dir,span[style],ol,ul,li[type],pre,u,hr,strike,sub,sup');
			$config->set('Attr.AllowedFrameTargets', array('_blank', '_top'));
			$purifier = new HTMLPurifier($config);
			$output = $purifier->purify($output);

			$smileys = array(
					':-o' => 'omg_smile.png',
					':-O' => 'omg_smile.png',
					':-)' => 'regular_smile.png',
					':)' => 'regular_smile.png',
					';-)' => 'wink_smile.png',
					';)' => 'wink_smile.png',
					':-(' => 'sad_smile.png',
					':(' => 'sad_smile.png',
					':-D' => 'teeth_smile.png',
					':D' => 'teeth_smile.png',
					':-P' => 'tongue_smile.png',
					':P' => 'tongue_smile.png',
					'(n)' => 'thumbs_down.png',
					'(y)' => 'thumbs_up.png',
					'8-)' => 'shades_smile.png',
					'<3' => 'heart.png'
				);
			array_walk($smileys, function(&$value, $key){
				$value='<img src="'.NEnvironment::getVariable("URI").'/js/ckeditor/plugins/smiley/images/'.$value.'"/>';
			});
			$output = strtr($output, $smileys);
			
			return $output;
		});
	}


	/**
	 * Message component factory. For flash messages in @layout.phtml.
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
		$user_env = NEnvironment::getUser();
		if ($user_env->isLoggedIn()) {
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
		
		if ($user_env->isLoggedIn()) {
			$userObject = NEnvironment::getUser()->getIdentity();
			if ($userObject->hasRightsToCreate() || $userObject->getAccessLevel() >= 2) {
				$menu[6] = array(
					'title' => _t('create'),
					'presenter' => 'resource',
					'action' => 'create',
					'parent' => 4
				);
			}

			$menu[5] = array(
				'title' => _t('browse'),
				'presenter' => 'resource',
				'action' => 'browse',
				'parent' => 0
			);

		}
		
		if ($user_env->isLoggedIn()) {
			$userObject = NEnvironment::getUser()->getIdentity();
			$access_level = $userObject->getAccessLevel();
			if ($access_level == 3 || $access_level == 2) {
				$menu[7] = array('title'=>_t('Administration'),
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
		$user_env = NEnvironment::getUser();
		if (!$user_env->isLoggedIn()) die('no permission');
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
				$number .= _t($match);
			}
			return $number;
		}
		return preg_replace_callback("/(\d)/u","translate_array",strval($in));
	}


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
		echo 'true';
		$this->terminate();
	}


	/**
	 *	Visitor counter per object.
	 *	@param int $type_id
	 *	@param int $object_id
	 *	@return void
	 */
	public function visit($type_id, $object_id)
	{
		$ip_address = StaticModel::getIpAddress(); // $_SERVER['REMOTE_ADDR'];
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
	public function handleImage($id, $type, $redirect=true)
	{
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
	 *	Default for accessibility of pages: only with login. Can be reverted on a per-page basis.
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
	 *	The token is set in config.ini.
	 * 	adding &verbose=1 will output some more info
	 *	@param string $token
	 *	@param boolean $verbose
	 *	@return
	 */
	public function handleCron($token, $verbose = null)
	{
	
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
	public function handleUpload()
	{
		$user_env = NEnvironment::getUser();
		if (!$user_env->isLoggedIn()) die('no permission');
		
		$allowed_extensions = array('jpg','jpeg','gif','png', 'pdf', 'odt', 'doc', 'docx', 'xls', 'ods', 'txt', 'rtf', 'ppt', 'pptx', 'odp');
		$allowed_types = array('image/jpeg', 'image/gif', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.presentation', 'application/vnd.oasis.opendocument.spreadsheet');
		$image_types = array('image/jpeg', 'image/gif', 'image/png');
		$path = '/uploads';
		$max_size = 3000000;
		$max_width = 800;
		$max_height = 2500;

		$query = NEnvironment::getHttpRequest();
		$file_info = $query->getFile('upload');
		$file_name = $file_info->getName();
		$funcNum = $query->getQuery('CKEditorFuncNum');
		if (!$funcNum) die();
		
		$content_type = $file_info->getContentType(); // before moving file

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
		$access_level = $user->getAccessLevel();
		if ($access_level < 3 && in_array($ext, $allowed_extensions)===false) {
			$message = 'ERROR: wrong extension';
		} elseif ($access_level < 3 && in_array($file_info->getContentType(), $allowed_types)===false) {
			$message = 'ERROR: wrong file type';
		} elseif ($file_info->getSize()===0) {
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
				if (in_array($content_type, $image_types)===true) {
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
	 *	Delete files in user's folder on the server, called from file browser.
	 *	@param string $file_name
	 *	@param int $user_id
	 *	@return void
	 */
	public function handleDeleteFile($file_name, $user_id)
	{
		if (empty($file_name) || empty($user_id))  {
			die('Empty parameters');
		}
		$user_env = NEnvironment::getUser();		
		if (!$user_env->isLoggedIn()) {
			die('no permission');
		}
		$user = $user_env->getIdentity();
		if ($user->getUserId() != $user_id && $user->getAccessLevel() < 2) {
			die('No permission');
		}

		$allowed_extensions = array('jpg','jpeg','gif','png', 'pdf', 'odt', 'doc', 'docx', 'xls', 'ods', 'txt', 'rtf', 'ppt', 'pptx', 'odp');
		
		$ext = pathinfo($file_name, PATHINFO_EXTENSION);
		if (empty($ext) || in_array($ext, $allowed_extensions)===false) {
			die('Wrong extension.');
		}

		$file_name = pathinfo($file_name, PATHINFO_FILENAME).'.'.$ext;
		$file_path = WWW_DIR . '/uploads/user-'.$user_id.'/'.$file_name;

		if (!file_exists($file_path)) {
			echo json_encode(_t("File '%s' not found.", $file_name));
			die();
		}

		unlink($file_path);
		echo json_encode("true");
		die();
	}


	/**
	 *	Rename files in user's folder on the server, called from file browser.
	 *	@param string $old_name
	 *	@param string $new_name
	 *	@param int $user_id
	 *	@return void
	 */
	public function handleRenameFile($old_name, $new_name, $user_id)
	{
		if (empty($old_name) || empty($new_name) || empty($user_id)) {
			die('Empty parameters');
		}
		$user_env = NEnvironment::getUser();		
		if (!$user_env->isLoggedIn()) {
			die('no permission');
		}
		$user = $user_env->getIdentity();
		if ($user->getUserId() != $user_id && $user->getAccessLevel() < 2) {
			die('No permission');
		}

		$allowed_extensions = array('jpg','jpeg','gif','png', 'pdf', 'odt', 'doc', 'docx', 'xls', 'ods', 'txt', 'rtf', 'ppt', 'pptx', 'odp');
		$file_path_old = WWW_DIR . '/uploads/user-'.$user_id.'/'.$old_name;
		$ext = pathinfo($file_path_old, PATHINFO_EXTENSION);
		if (empty($ext) || in_array($ext, $allowed_extensions)===false) {
			die('Wrong extension.');
		}

		$new_name = pathinfo($new_name, PATHINFO_FILENAME).'.'.$ext;
		
		$file_path_new = WWW_DIR . '/uploads/user-'.$user_id.'/'.$new_name;

		if ($file_path_new == $file_path_old) {
			die();
		}
		
		if (!file_exists($file_path_old)) {
			echo json_encode(_t("File '%s' not found.", $old_name));
			die();
		}
		if (file_exists($file_path_new)) {
			echo json_encode(_t("File '%s' already exists.", $new_name));
			die();
		}
		echo json_encode(rename($file_path_old, $file_path_new) ? 'true' : 'Error renameing file.');
		die();
	}


	/**
	 *	Creates HTML output of recent activity
	 *
	 *	@param int $id describes time range: today, yesterday, week, month
	 *	@return void
	 */
	public function handleActivity($id = 2, $latest = 0, $placement = 'home')
	{
		$user_env = NEnvironment::getUser();
		if (!$user_env->isLoggedIn()) die('no permission');
		$user = $user_env->getIdentity();
		$user_id = $user->getUserId();

		// get timeframe from id
		switch ($id) {
			case 2: $header_time = _t("Today"); $from = strtotime('today midnight'); $to = null; break;
			case 3: $header_time = _t("Yesterday"); $from = strtotime('yesterday midnight'); $to = strtotime('today midnight'); break;
			case 4: $header_time = _t("Week"); $from = strtotime('7 days ago midnight'); $to = strtotime('yesterday midnight'); break;
			default: $header_time = _t("Month"); $from = strtotime('1 month ago midnight'); $to = strtotime('7 days ago midnight'); break;
		}

		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Activity.stream");
		$cache->clean();
		$cache_key = $user_id.'-'.$id.'-'.$latest;
		if ($cache->offsetExists($cache_key)) {
			$output_list = $cache->offsetGet($cache_key);
		} else {
			$activities = Activity::getActivities($user_id, $from, $to, $latest);
			$output_list = Activity::renderList($activities, $user_id);

			if ($id == 2) $time = time()+120; else $time = time()+3600;
			$cache->save($cache_key, $output_list, array(NCache::EXPIRE => $time, NCache::TAGS => array("user_id/$user_id", "name/activity")));
		}
	
//		$now = time();
		$output = '';
		// for scrolling
		$output .= '<div id="activity-scroll-target-'.$placement.'-'.$id.'"></div>';
		$output .= "<h3>".$header_time."</h3>";

		$output .= $output_list;
		if ($id < 5) {
			$id_inc = $id +1;
			$id_tag = 'load-more-'.$placement.'-'.$id;
			$output .= '
	<div id="'.$id_tag .'">
		<p><a href="javascript:void(0);" id="load_more-'.$placement.'" class="button">'._t("load more...").'</a></p>
	</div>';
			$output .= '
	<script>
		$("#load_more-'.$placement.'").click(function(){
			loadActivity("#'.$id_tag.'", '.$id_inc.', '.$latest.', "'.$placement.'");
		});
	</script>
			';
		}
		echo $output;
		
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
		if (!$user_env->isLoggedIn()) die('no permission');
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
		$cache->save($cache_key, $array_feed_items, array(NCache::EXPIRE => time()+120, NCache::TAGS => array("user_id/$user_id", "name/events")));
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
	public function handleChangeevent($changed,$resource_id,$day_delta=0,$minute_delta=0,$allday=null)
	{
		if (Auth::isAuthorized(3,$resource_id) < Auth::MODERATOR) die('no permission');
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
	public function handleOnlineStatus($show_date = 1, $span = 1)
	{
		$user = NEnvironment::getUser();
		if (!$user->isLoggedIn()) die('no permission');
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
							$value = '<span style="color:#37AB44;font-size:2em;margin-top:-4px;" title="'._t('now online').'">&#149</span>';
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


	/**
	 *	Receives a reference and sets the filter for search.
	 *	References are prepended by @ for users, or multiple @s for groups and resources, and contain names or ids. They are prepended by a # to search for tag names. Names are optionally finished by another @ or #.
	 *	@param string $string
	 *	@return void
	*/
	public function handleSearch($string)
	{
		$string = str_replace('_', ' ', trim($string));
		if (preg_match("/^@([0-9]+)@?/", $string, $matches)) {
			$type = 'user_id';
			$id = $matches[1];
			$redirect = "User:default";
		} elseif (preg_match("/^@([^@]+)@?/", $string, $matches)) {
			$type = 'user_name';
			$text = $matches[1];
			$name = 'userlister';
			$redirect = "User:default";
		} elseif (preg_match("/^@@([^@0-9]+)@?/", $string, $matches)) {
			$type = 'group_name';
			$text = $matches[1];
			$name = 'grouplister';
			$redirect = "Group:default";
		} elseif (preg_match("/^@@([^@]+)@?/", $string, $matches)) {
			$type = 'group_id';
			$id = $matches[1];		
			$redirect = "Group:default";
		} elseif (preg_match("/^@@@([^@0-9]+)@?/", $string, $matches)) {
			$type = 'resource-name';
			$text = $matches[1];
			$name = 'defaultresourceresourcelister';
			$redirect = "Resource:default";
		} elseif (preg_match("/^@@@([^@]+)@?/", $string, $matches)) {
			$type = 'resource_id';
			$id = $matches[1];		
			$redirect = "Resource:default";
		} elseif (preg_match("/^#([^#]+)#?/", $string, $matches)) {
			$type = 'tag_name';
			$tag = $matches[1];
			$redirect = "User:default";
		} else {
			$this->redirect("this");
		}
		
		if (isset($id)) {
			$this->redirect($redirect, array($type => $id));
		} elseif ($type == 'tag_name') {
			$result = Tag::ids_from_name($tag);
			$tag_ids = $result['tag_ids'];
			if (count($tag_ids) > 0) {
				$tag_id = $tag_ids[0];
				$filterdata = array(
					'tags' => array(
						'all' => false,
						$tag_id => true
						)
					);
			} else {
				$this->flashMessage(_t('Tag not found.'));
				$this->redirect("this");
			}
		} else {
			$filterdata = array(
				'name' => $text
				);
		}
		
		if (NEnvironment::getVariable("GLOBAL_FILTER")) {
			$name='defaultresourceresourcelister';
		}
		$filter = new ExternalFilter($this,$name);
		$session = NEnvironment::getSession()->getNamespace($name);
		unset($session->filterdata);
		$filter->clearFilterArray();
		if (NEnvironment::getVariable("GLOBAL_FILTER")) {
			$filter->syncFilterArray($filterdata);
		} else {
			$session->filterdata = $filterdata;
		}

		$this->redirect($redirect);
	}



	/**
	 *	Receives request to change content of page via Ajax.
	 *	@param void
	 *	@return void
	 */
    public function handleClickLink()
    {
		if ($this->isAjax()) {
			$session = NEnvironment::getSession()->getNamespace($this->name);
			$data = $session->data;

			$this->presenter->actionDefault();
            $this->invalidateControl('mainContent');
            $this->invalidateControl('mainMenu');
            $this->invalidateControl('activityMenu');
		} else {
		  	$this->redirect('this');
		}
    }


	/**
	 *	Receives request to apply filter via Ajax. Applicable only for main item listers with popup filters.
	 *	@param void
	 *	@return void
	 */
    public function handleAjaxFilter()
    {
		if ($this->isAjax()) {
			switch ($this->name) {
			case 'Homepage': $options['components'] = array(
				'homepagefriendlister',
				'homepagegrouplister',
				'homepageresourcelister'); break;
			case 'Resource': $options['components'] = array('defaultresourceresourcelister'); break;
			case 'User': $options['components'] = array('userlister'); break;
			case 'Group': $options['components'] = array('grouplister'); break;
    		}

			$options['include_tags'] = true;
			$options['include_type'] = true;
			$options['include_map'] = true;
			$options['include_pairing'] = true;
			$options['include_language'] = true;
			$options['include_name'] = true;
		
			$filter = new ExternalFilter($this, 'filter', $options);
			$filter->clearFilterArray();
			$filter->ajaxFilterSubmitted();

			$this->presenter->actionDefault();
            $this->invalidateControl('mainContent');
            $this->invalidateControl('mainMenu');
            $this->invalidateControl('activityMenu');
		} else {
		  	$this->redirect('this');
		}
    }


	/**
	 *	Change the page through a pager via Ajax.
	 *	@param int $page
	 *	@param string $name
	 *	@return void
	 */
	public function handleChangePage($page, $name)
	{
		if ($this->isAjax()) {
			$session = NEnvironment::getSession()->getNamespace($name);
			if (!empty($session->filterdata)) {
				$filter = $session->filterdata;
			}
			$filter['page'] = $page;
			$session->filterdata = $filter;

			$data = $session->data;

			$this->presenter->actionDefault();
			$this->invalidateControl('mainContent');
            $this->invalidateControl('mainMenu');
		}
	}


	/**
	 *	Receives a private message and saves it to database.
	 *	@param string $pmpop_message_text
	 *	@param int $recipient_id
	 *	@return void
	 */
	public function handleSubmitPMPOPChat($pmpop_message_text = '', $recipient_id)
	{
		$user_env = NEnvironment::getUser();
		if (!$user_env->isLoggedIn()) die('no permission');
		$logged_user = $user_env->getIdentity();

		if ($recipient_id == 0 || !$logged_user->friendshipIsRegistered($recipient_id) || empty($pmpop_message_text)) {
			echo json_encode(_t("Error sending message.")); die();
		}

		$resource                          = Resource::create();
		$data                              = array();
		$data['resource_author']           = $logged_user->getUserId();
		$data['resource_type']             = 1;
		$data['resource_visibility_level'] = 3;
		$data['resource_name'] = '<PM>';
		$data['resource_data']             = json_encode(array(
			'message_text' => $pmpop_message_text
		));
		$resource->setResourceData($data);
		$check = $resource->check_doublette($data, $recipient_id, 1);
		if ($check === true) {
			echo json_encode(_t("You have just said that."));
			die();
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
	 *	Creates the form to send off messages; no processing of results, submitted by own AJAX
	 *	@param	void
	 *	@return	array
	 */
	protected function createComponentPopupmessageform()
	{
		$user    = NEnvironment::getUser()->getIdentity();
		$friends = $user->getFriends();
		$friends = array(0=>_t('Please select a recipient'))+$friends;
		$form    = new NAppForm($this, 'popupmessageform');
		$form->addSelect('friend_id', _t('To:'), $friends);
		$form->addTextarea('pmppop_message_text', '');
		$form->addSubmit('send', _t('Send'));
		$form->addProtection(_t('Error submitting form.'));
		$form->setDefaults(array('friend_id' => 0));		
		// next declaration needed by framework?
		$form->onSubmit[] = array(
			$this,
			'popupmessageformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	Moving message to trash.
	 *	Permission check in Resource class.
	 *	@param int $resource_id
	 *	@return void
	 */
	public function handleMoveToTrash($resource_id)
	{
		$user     = NEnvironment::getUser()->getIdentity();
		$resource = Resource::create($resource_id);

		if (!empty($resource)) {
			if (!empty($user)) {
				if ($resource->userIsRegistered($user->getUserId())) {
					Resource::moveToTrash($resource_id);

					$storage = new NFileStorage(TEMP_DIR);
					$cache = new NCache($storage);
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/messagelisteruser")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmwidget")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmwidgetslim")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmabstract")));
				}
			}
		}
		$this->terminate();
	}


	/**
	 *	Restoring message from trash.
	 *	Permission check in Resource class.
	 *	@param int $resource_id
	 *	@return void
	 */
	public function handleMoveFromTrash($resource_id)
	{
		$user     = NEnvironment::getUser()->getIdentity();
		$resource = Resource::create($resource_id);

		if (!empty($resource)) {
			if (!empty($user)) {
				if ($resource->userIsRegistered($user->getUserId())) {
					Resource::moveFromTrash($resource_id);

					$storage = new NFileStorage(TEMP_DIR);
					$cache = new NCache($storage);
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/messagelisteruser")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmwidget")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmwidgetslim")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmabstract")));
				}
			}
		}
		$this->terminate();
	}


	/**
	 *	Mark message as read.
	 *	Permission check in Resource class.
	 *	@param int $resource_id
	 *	@return void
	 */
	public function handleMarkRead($resource_id)
	{
		$user     = NEnvironment::getUser()->getIdentity();
		$resource = Resource::create($resource_id);

		if (!empty($resource)) {
			if (!empty($user)) {
				if ($resource->userIsRegistered($user->getUserId())) {
					$resource->setOpened($user->getUserId(),$resource_id);

					$storage = new NFileStorage(TEMP_DIR);
					$cache = new NCache($storage);
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/messagelisteruser")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmwidget")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmwidgetslim")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmabstract")));
				}
			}
		}
		$this->terminate();
	}


	/**
	 *	Mark message as unread.
	 *	Permission check in Resource class.
	 *	@param int $resource_id
	 *	@return void
	 */
	public function handleMarkUnread($resource_id)
	{
		$user     = NEnvironment::getUser()->getIdentity();
		$resource = Resource::create($resource_id);

		if (!empty($resource)) {
			if (!empty($user)) {
				if ($resource->userIsRegistered($user->getUserId())) {
					$resource->setUnopened($user->getUserId(),$resource_id);

					$storage = new NFileStorage(TEMP_DIR);
					$cache = new NCache($storage);
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/messagelisteruser")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmwidget")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmwidgetslim")));
					$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmabstract")));
				}
			}
		}
		$this->terminate();
	}


	/**
	 *	Empties the trash.
	 *	@param void
	 *	@return void
	 */
	public function handleEmptyTrash()
	{
		Resource::emptyTrash();

		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/messagelisteruser")));
		$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmwidget")));
		$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmwidgetslim")));
		$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/pmabstract")));
	}



	/**
	 *	Experimental for future develoment; handles back in history while respecting changes by Ajax
	 *	@param
	 *	@return
	 */
	public function handleHistoryBack()
	{
		$allowed_queries = array('group_id', 'user_id', 'resource_id', 'object_type', 'object_id');
		if ($this->isAjax()) {
			$session = NEnvironment::getSession()->getNamespace('GLOBAL');
			
			if (!empty($session->history) && is_array($session->history)) {

//				$this->invalidateControl('mainContent');
//				$this->invalidateControl('mainMenu');
				$history = array_pop($session->history);
				$history = array_pop($session->history);
//				$view = $history['presenter_view'];

				$query = array_intersect_key($history['query'],$allowed_queries);
				$query['go'] = 'back';
				$this->redirect($history['presenter_name'].':'.$history['presenter_view'], $query);
//				$this->presenter->setView($view);


			}
		}
	}


	/**
	 *	Asking for friendship, or confirming
	 *	@param int $friend_id
	 *	@return
	 */
	public function handleUserFriendInsert($friend_id)
	{
		if (empty($friend_id)) {
			print json_encode("false");
			$this->terminate();
		} else {
			$user   = NEnvironment::getUser()->getIdentity();
			$user_id = $user->getUserId();
			$friend = User::create($friend_id);
			
			if (!empty($friend)) {
				$friend_id = $friend->getUserId();
				if (!empty($friend_id)) {
					$user->updateFriend($friend_id, array());
					
					$storage = new NFileStorage(TEMP_DIR);
					$cache = new NCache($storage);
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/friendlister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagefriendlister")));
					$cache->clean(array(NCache::TAGS => array("user_id/$user_id", "name/homepagerecommendedfriendlister")));
					$cache->clean(array(NCache::TAGS => array("friend_id/$friend_id", "name/friendlister")));
					$cache->clean(array(NCache::TAGS => array("friend_id/$friend_id", "name/homepagefriendlister")));
					$cache->clean(array(NCache::TAGS => array("friend_id/$friend_id", "name/homepagerecommendedfriendlister")));

					print json_encode("true");
				}
			}
		}
		
		$this->terminate();
	}

}
