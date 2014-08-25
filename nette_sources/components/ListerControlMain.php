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
 

class ListerControlMain extends NControl
{
	//constants
	const LISTER_TYPE_USER = 1;
	const LISTER_TYPE_GROUP = 2;
	const LISTER_TYPE_RESOURCE = 3;
	const LISTER_TYPE_USER_DETAIL = 4;
	const LISTER_TYPE_GROUP_DETAIL = 5;
	const LISTER_TYPE_RESOURCE_DETAIL = 6;
	
	
	//default values
	protected $lister_type = array(self::LISTER_TYPE_USER, self::LISTER_TYPE_GROUP, self::LISTER_TYPE_RESOURCE, self::LISTER_TYPE_USER_DETAIL, self::LISTER_TYPE_GROUP_DETAIL, self::LISTER_TYPE_RESOURCE_DETAIL);
	protected $currentpage = 1;
	protected $itemscount = 0;
	protected $itemsperpage = 10;
	protected $itemsonbar = 10;
	protected $data = null;
	protected $refresh_path = "Homepage:default";
	protected $refresh_path_params = array();
	protected $template_body = "ListerControlMain_body.phtml";
	protected $template_filter = "ListerControlMain_filter.phtml";
	protected $persistent_filter = array();
	protected $template_variables = array();
	protected $active = false;
	protected $cache_tags;
	protected $cache_expiry;


	public function __construct($parent, $name, $options = array())
	{
		parent::__construct($parent, $name);
		if (isset($options['itemsPerPage'])) {
			$this->setItemsPerPage($options['itemsPerPage']);
		}
		if (isset($options['template_body'])) {
			$this->template_body = $options['template_body'];
		}
		if (isset($options['template_filter'])) {
			$this->template_filter = $options['template_filter'];
		}
		if (isset($options['lister_type'])) {
			$this->lister_type = $options['lister_type'];
		}
		if (isset($options['refresh_path'])) {
			$this->refresh_path = $options['refresh_path'];
			if (isset($options['refresh_path_params'])) {
				$this->refresh_path_params = $options['refresh_path_params'];
			}
		}
		if (isset($options['filter'])) {
			$this->persistent_filter = $options['filter'];
			$session                 = NEnvironment::getSession()->getNamespace($this->name);
			if (!empty($session->filterdata)) {
				$session->filterdata = array_merge($session->filterdata, $this->persistent_filter);
			} else {
				$session->filterdata = $this->persistent_filter;
			}
		}
		if (isset($options['template_variables'])) {
			$this->template_variables = $options['template_variables'];
		}
		if (isset($options['filter'])) {
			$this->template_variables['persistent_filter'] = $options['filter'];
		}
		
		if (isset($options['cache_tags'])) {
			$this->cache_tags = $options['cache_tags'];
		}

		if (isset($options['cache_expiry'])) {
			$this->cache_expiry = $options['cache_expiry'];
		}

		$filter           = $this->getFilterArray();
		$this->itemscount = $this->getDataCount($filter);
		if (!empty($filter['page'])) {
			$this->setCurrentPage($filter['page']);
			if ($this->currentpage != $filter['page']) { // check if current page setting is higher than max page after changing from another screen
				$filter['page'] = $this->currentpage;
				$this->setFilterArray($filter);
			}
		}

		$this->template_variables['name'] = $this->name;
		$this->registerHelpers();
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
			$dirty_html = strtr($dirty_html, $smileys);
			
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
	 *	Renders the list with the filter.
	 *	@param void
	 *	@return void
	 */
	public function render()
	{
		$this->renderFilter();
		$this->renderBody();
	}


	/**
	 *	Renders status of filter as readable text for button
	 *	@param void
	 *	@return void
	 */
	public function renderFiltercheck()
	{
		if ($this->activeFilter()) {
			echo $this->filter_summary();
		} else {
			echo _t("Filter is off.");
		}
	}


	/**
	 *	Renders short status of filter for use in class for styling.
	 *	@param void
	 *	@return void
	 */
	public function renderFilterstatus()
	{
		if ($this->activeFilter()) {
			echo "on";
		} else {
			echo "off";
		}
	}


	/**
	 *	Renders text for title tag of filter button.
	 *	@param void
	 *	@return void
	 */
	public function renderFiltertitle()
	{
		if (!$this->activeFilter()) {
			echo _t("Use the filter to narrow down the list of items");
		}
	}


	/**
	 *	Checks whether filter is active or not.
	 *	@param void
	 *	@return boolean
	 */
	public function activeFilter()
	{
		$active = false;
		$filter = $this->getFilterArray();
		if (isset($filter['name']) && strlen($filter['name']) > 0)
			$active = true;
		elseif (!empty($filter['type']) && !is_array($filter['type']) && (($this->name == 'defaultresourceresourcelister') || ($this->name == 'homepageresourcelister')))
			$active = true;
		elseif (isset($filter['language']) && $filter['language'] > 0)
			$active = true;
		elseif (isset($filter['mapfilter']) && $filter['mapfilter'] != 'null')
			$active = true;
		else {
			if (!$active && isset($filter['tags'])) {
				foreach ($filter['tags'] as $key => $value) {
					if ($key != 'all' && $value == true) {
						$active = true;
						break;
					}
				}
			}
		}
		return $active;
	}


	/**
	 *	Returns summary of used filter criteria for HTML output.
	 *	@param void
	 *	@return string
	 */
	public function filter_summary()
	{
		$output = array();
		$length = 0;
		$resource_type_icon = array(
			2 => '<b class="icon-event" title="'._t('event').'" style="vertical-align:-4px;"></b>',
			3 => '<b class="icon-org" title="'._t('organization').'" style="vertical-align:-4px;"></b>',
			4 => '<b class="icon-doc" title="'._t('document').'" style="vertical-align:-4px;"></b>',
			5 => '<b class="icon-video" title="'._t('video').'" style="vertical-align:-4px;"></b><b class="icon-audio" title="'._t('audio').'" style="vertical-align:-4px;"></b>',
			6 => '<b class="icon-website" title="'._t('link to external resource').'" style="vertical-align:-4px;"></b>'
		);

		$filter = $this->getFilterArray();
		if (isset($filter['name']) && strlen($filter['name']) > 0) {
			$name = _t('Name');
			$output[] = '<span style="color:#777;">'.$name.':</span> '.preg_replace('/[\r\n]+/', '', $filter['name']);
			$length += strlen(_t('Name').$filter['name']);
		}
		if (!empty($filter['type']) && !is_array($filter['type']) && (($this->name == 'defaultresourceresourcelister') || ($this->name == 'homepageresourcelister'))) {
			$output[] = $resource_type_icon[$filter['type']];
			$length += 5;
		}
		if (isset($filter['language']) && $filter['language'] > 0) {
			$name = Language::getLanguageName($filter['language']);
			$output[] = '<span style="color:#777;">'._t('Language').':</span> '.$name;
			$length += strlen(_t('Language').$name);
		}
		if (isset($filter['tags'])) {
			if (!isset($filter['tags']['all']) || $filter['tags']['all']!=true) {
				$output_temp = array();
				foreach ($filter['tags'] as $key => $value) {
					if ($key != 'all' && $value == true) {
						if ($length > 80) {
							$output_temp[] = '<b class="icon-tag" title="'._t('More tags').'" style="width:17px; vertical-align:-3px;"></b>...';
							break;
						}
						$tag = Tag::create($key);
						$name = _t_tags($tag->getName());
						$output_temp[] = '<b class="icon-tag" title="'._t('Tag').'" style="width:17px; vertical-align:-3px;"></b>'.preg_replace('/[\r\n]+/', '', $name);
						$length += strlen($name);
					}
				}
				if (!empty($output_temp)) {
					$output[] = implode(' | ', $output_temp);
				}
			}
		}
		if (isset($filter['mapfilter']) && $filter['mapfilter'] != 'null') {
			$output[] = '<img src="'.NEnvironment::getVariable("URI").'/images/icon-map.png" title="'._t('Map').'" style="vertical-align:-4px;"/>';
		}
		
		return '<span style="margin: 0 5px; padding:5px 7px; background-color:#EAE9E3;">'.implode(' | ', $output).'</span><b class="icon-cancel" onclick="$(\'#filter_box_a\').attr(\'id\',\'\');$(\'#frmfilter-reset\').click();" title="'._t("Reset the filter and show the entire list.").'" style="vertical-align:-5px;"></b>';
		
	}



	/**
	 *	Renders the filter.
	 *	@param void
	 *	@return void
	 */
	public function renderFilter()
	{
		$template = $this->template;
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}
		$template->setTranslator(new GettextTranslator(LOCALE_DIR . '/' . $language . '/LC_MESSAGES/messages.mo', $language));
		
		$template->setFile(dirname(__FILE__) . '/' . $this->template_filter);
		if ($this->getParent()->name !== $this->presenter->name) {
			$template->name = $this->getParent()->name . "-" . $this->name;
		} else {
			$template->name = $this->name;
			
		}
		$template->data               = $this->data;
		$template->template_variables = $this->template_variables;
		$template->refresh_path       = $this->refresh_path;
		$user                         = NEnvironment::getUser()->getIdentity();
		if (!empty($user)) {
			$template->user_id = $user->getUserId();
		}
		
		$template->render();
	}


	/**
	 *	Renders the body (lists of items that pass the filter).
	 *	@param boolean $echo (whether to echo or return)
	 *	@return void|string
	 */
	public function renderBody($echo=true)
	{
		$template = $this->template;
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}
		$template->setTranslator(new GettextTranslator(LOCALE_DIR . '/' . $language . '/LC_MESSAGES/messages.mo', $language));

		$template->setFile(dirname(__FILE__) . '/' . $this->template_body);
		if ($this->getParent()->name !== $this->presenter->name) {
			$template->name = $this->getParent()->name . "-" . $this->name;
		} else {
			$template->name = $this->name;
		}
		$template->persistent_filter  = $this->persistent_filter;
		$template->template_variables = $this->template_variables;
		$template->refresh_path       = $this->refresh_path;
		$user                         = NEnvironment::getUser()->getIdentity();
		if (!empty($user)) {
			$template->user_id = $user->getUserId();
			$template->logged = true;
		}
		
		$template->currentpage = $this->currentpage;
		$template->max_page    = $this->getMaxPage();
		$template->lister_type = $this->lister_type;
		$template->active_filter = $this->activeFilter();
		$template->baseUri = NEnvironment::getVariable("URI") . '/';

		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Lister.render.".$this->name);
		$cache->clean();
		$no_filter = array('userHomepage','groupHomepage','resourceHomepage');
		$user_o = NEnvironment::getUser()->getIdentity();
		if (!empty($user_o)) {
			$user_id = $user_o->getUserId();
		} else {
			$user_id = 0;
		}
		if (in_array($this->name, $no_filter)) {
			$cache_key = $language.'-general';
		} else {
			$filter = $this->getFilterArray();
			$cache_key = $user_id.'-'.$language.'-'.md5(json_encode($filter).json_encode($this->template_variables));
		}
		if ($cache->offsetExists($cache_key)) {
			$output = $cache->offsetGet($cache_key);
			echo $output;
			echo '<!-- cache Lister.render.'.$this->name.' -->';
		} else {
			$this->generateList();
			$template->data = $this->data;
			
			ob_start();
			$template->render();
			$output = ob_get_contents();
			if ($echo) {
				ob_end_flush();
			} else {
				ob_end_clean();
			}
			if (isset($this->cache_expiry)) {
				$settings = array(NCache::EXPIRE => time()+$this->cache_expiry);
			} else {
				$settings = array(NCache::EXPIRE => time()+120);
			}
			$settings[NCache::TAGS] = array();
			if (isset($this->cache_tags)) {
				$settings[NCache::TAGS] = $this->cache_tags;
			}
			$settings[NCache::TAGS][] = "name/$this->name";
			$cache->save($cache_key, $output, $settings);
			if (!$echo) {
				return $output;
			}
		}
	}


	/**
	 *	Sets the amount of items.
	 *	@param int $count
	 *	@return void
	 */
	public function setItemsCount($count)
	{
		$this->itemscount = $count;
	}


	/**
	 *	Retrieves the amount of items.
	 *	@param void
	 *	@return int
	 */
	public function getItemsCount()
	{
		return $this->itemscount;
	}


	/**
	 *	Sets how many items in a list on one page.
	 *	@param int $count
	 *	@return void
	 */
	public function setItemsPerPage($count)
	{
		$this->itemsperpage = $count;
	}


	/**
	 *	Retrieves the maximum page number.
	 *	@param void
	 *	@return int
	 */
	public function getMaxPage()
	{
		$maxpage = (int) ($this->itemscount / $this->itemsperpage);
		if ($this->itemscount % $this->itemsperpage != 0) {
			$maxpage++;
		}
		return $maxpage;
	}


	/**
	 *	Sets the current page in a multi-page list.
	 *	@param int $page
	 *	@return void
	 */
	public function setCurrentPage($page)
	{
		$maxpage = $this->getMaxPage();
		if ($page >= $maxpage) {
			$page = $maxpage;
		}
		if ($page < 1) {
			$page = 1;
		}
		$this->currentpage = $page;
	}


	/**
	 *	Retrieves id of first item on a page.
	 *	@param int $page
	 *	@return int
	 */
	public function getPageFirstIndex($page)
	{
		$itemsonpage = $this->itemsperpage;
		return $itemsonpage * ($page - 1);
	}


	/**
	 *	Retrieves the data for lists.
	 *	Checks the authorization to view these items (to view that they exist).
	 *	Removes own connections from recommendations.
	 *	Adds forms to items for administration purposes.
	 *	@param void
	 *	@return void 
	 */
	public function generateList()
	{

		$filter = $this->getFilterArray();

		if (isset($filter['trash']) && $filter['trash'] == 2) {
			$logged_user = NEnvironment::getUser()->getIdentity();
			if (isset($logged_user)) {
				$logged_user_id = $logged_user->getUserId();
				$count_unread = User::getUnreadMessages($logged_user_id);
				if ($count_unread == 0) $filter['trash'] = 0;
				$this->setFilterArray($filter);
				unset($logged_user);
			}
		} elseif (isset($filter['trash']) && $filter['trash'] == 'null') {
			$logged_user = NEnvironment::getUser()->getIdentity();
			if (isset($logged_user)) {
				$logged_user_id = $logged_user->getUserId();
				$count_unread = User::getUnreadMessages($logged_user_id);
				if ($count_unread > 0) $filter['trash'] = 2;
				$this->setFilterArray($filter);
				unset($logged_user);
			}
		}

		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}
		
/*
	We are caching this list because it may appear identically on default and detail pages, no need to recreate when user clicks from default to detail view.
*/
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Lister.".$this->name);
		$cache->clean();
		$no_filter = array('userHomepage','groupHomepage','resourceHomepage');
		$user_o = NEnvironment::getUser()->getIdentity();
		if (!empty($user_o)) {
			$user_id = $user_o->getUserId();
		} else {
			$user_id = 0;
		}
		if (in_array($this->name, $no_filter)) {
			$cache_key = $language.'-general';
		} else {
			$cache_key = $user_id.'-'.$language.'-'.md5(json_encode($filter));
		}
		if ($cache->offsetExists($cache_key)) {
			$this->data = $cache->offsetGet($cache_key);
		} else {
			$this->data = $this->getPageData($filter);
		
			// check permissions, remove items that user may not view
			foreach ($this->data as $key => $data_row) {
				if ($data_row['type_name'] == "user") {
					if (Auth::isAuthorized(1,$data_row['id'])==0) unset($this->data[$key]);
				}
				if ($data_row['type_name'] == "group") {
					if (Auth::isAuthorized(2,$data_row['id'])==0) unset($this->data[$key]);
				}
				if ($data_row['type_name'] == "resource") {
					if (Auth::isAuthorized(3,$data_row['id'])==0) unset($this->data[$key]);
				}
			}

			// if this is a list of recommended items: remove those where user is already connected, including oneself
			if (isset($filter['exclude_connections_user_id'])) {
				foreach ($this->data as $key => $data_row) {
					if ($data_row['type_name'] == "user" && $data_row['id'] == $filter['exclude_connections_user_id']) unset($this->data[$key]);
				}
				$filter_connections = array_merge($filter, array('user_id' => $filter['exclude_connections_user_id']));
				$data_connections = $this->getPageData($filter_connections);
### array_diff throws notice if arrays are multi-dimensional
				$this->data = @array_diff($this->data, $data_connections);
			}
			if (isset($this->cache_expiry)) {
				$settings = array(NCache::EXPIRE => time()+$this->cache_expiry);
			} else {
				$settings = array(NCache::EXPIRE => time()+120);
			}
			$settings[NCache::TAGS] = array();
			if (isset($this->cache_tags)) {
				$settings[NCache::TAGS] = $this->cache_tags;
			}
			$settings[NCache::TAGS][] = "name/$this->name";
			$cache->save($cache_key, $this->data, $settings);
		}
		
		foreach ($this->data as $key => $data_row) {
			if ($data_row['type_name'] == "user") {
				$this->createComponentUserListItem($data_row);
			}
			if ($data_row['type_name'] == "group") {
				$this->createComponentGroupListItem($data_row);
			}
			if ($data_row['type_name'] == "resource") {
				$this->createComponentResourceListItem($data_row);
			}
		}
	}


	/**
	 *	Sets the session filter to the array. Optionally merges previous array into new one (prioritizes new array).
	 *	@param array $filter
	 *	@param boolean $keep_old_data
	 *	@return void
	 */
	public function setFilterArray($filter, $keep_old_data = false)
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if ($keep_old_data) {
			$old_filter          = $session->filterdata;
			$old_filter          = array_merge($old_filter, $filter);
			$session->filterdata = $old_filter;
		} else {
			$session->filterdata = array_merge($this->persistent_filter, $filter);
		}
	}


	/**
	 *	Clears session filter array. Optionally clears only keys that are present in sample filter array.
	 *	@param array $filter
	 *	@return void
	 */
	public function clearFilterArray($filter = null)
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (is_null($filter)) {
			$session->filterdata = $this->persistent_filter;
		} else {
			foreach ($filter as $key => $value) {
				if (in_array($key, $session->filterdata)) {
					unset($session->filterdata[$key]);
				}
			}
		}
	}


	/**
	 *	Retrieves session filter array.
	 *	@param void
	 *	@return array
	 */
	public function getFilterArray()
	{
		$filter  = array();
		$session = NEnvironment::getSession()->getNamespace($this->name);
		
		if (!empty($session->filterdata)) {
			$filter = $session->filterdata;
		}
		//allowed types only
		if (isset($this->template_variables['all_types'])) {
/*
			if ((isset($filter['type']) && $filter['type'] == 'all') {
				$filter['type'] = 'all';
			} elseif (!isset($filter['type'])) {
				$filter['type'] = 'all';
			}
*/
			if (!isset($filter['type'])) {
				$filter['type'] = 'all';
			}
		} else {
/*
			if (isset($filter['type']) && $filter['type'] == 'all') {
				$filter['type'] = array(
					2,
					3,
					4,
					5,
					6
				);
			} elseif (!isset($filter['type'])) {
				$filter['type'] = array(
					2,
					3,
					4,
					5,
					6
				);
			}
*/
			if ((isset($filter['type']) && $filter['type'] == 'all') || (!isset($filter['type']))) {
				$filter['type'] = array(
					2,
					3,
					4,
					5,
					6
				);
			}
		}
		return $filter;
	}


	/**
	 *	Sets page to new value via Ajax.
	 *	@param int $page
	 *	@return void
	 */
	public function handleChangePage($page)
	{
		$filter = $this->getFilterArray();
		$this->setCurrentPage($page);
		$filter['page'] = $this->currentpage;
		$this->setFilterArray($filter);
		$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
	}


	/**
	 *	Sets beginning and length of new page.
	 *	@param int $page
	 *	@return array
	 */
	public function pageToLimit($page)
	{
		if (!empty($page)) {
			$pageFirstIndex = $this->getPageFirstIndex($page);
			return array(
				'from' => $pageFirstIndex,
				'count' => $this->itemsperpage
			);
		}
		return array(
			'from' => 0,
			'count' => $this->itemsperpage
		);
	}


	/**
	 *	Define administration form for connected users of a group or resource.
	 *	@param array $data_row
	 *	@return array $form
	 */
	public function createComponentUserListItem($data_row)
	{
		$params    = NEnvironment::getHttpRequest()->getQuery("lister-page");
		$user      = User::create($data_row['id']);
		$user_data = $user->getUserData();
		$form      = new NAppForm($this, "userform" . $data_row['id']);
		$form->addHidden('id');

		$access_level = array(
			'1' => _t('Normal'),
			'2' => _t('Moderator')
		);

		// moderators can be made the new owner
		if ($data_row['access_level'] == 2 ) {
			if ((isset($this->persistent_filter['group_id']) && Auth::ADMINISTRATOR == Auth::isAuthorized(2,$this->persistent_filter['group_id'])) || (isset($this->persistent_filter['resource_id']) && Auth::ADMINISTRATOR == Auth::isAuthorized(3,$this->persistent_filter['resource_id']))) {
				$access_level['3'] = _t('Owner');
			}
		}

		$form->addSelect('access_level', null, $access_level);
		$form->addCheckbox('status');
		$form->addSubmit('send', _t('Update'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'adminUserFormSubmitted'
		);
		$form->setDefaults(array(
			'id' => $data_row['id'],
			'access_level' => $data_row['access_level'],
			'status' => $data_row['status']
		));

		return $form;
	}



	/**
	 *	Process return values from createComponentUserListItem()
	 *	@param
	 *	@return
	 */
	public function adminUserFormSubmitted(NAppForm $form)
	{
		$values = $form->getValues();
		$user   = User::create($values['id']);
		
		unset($values['id']);

		if (isset($this->persistent_filter['group_id'])) {
			foreach ($values as $key => $value) {
				$values["group_user_" . $key] = $value;
				unset($values[$key]);
			}
			
			$group = Group::create($this->persistent_filter['group_id']);
			$group->updateUser($user->getUserId(), $values);
			if ($values["group_user_access_level"] == 3) {
				if (Auth::ADMINISTRATOR == Auth::isAuthorized(2,$this->persistent_filter['group_id'])) {
					$owner = $group->getOwner();
					$group->updateUser($owner->getUserId(), array("group_user_access_level" => 2));
					Activity::addActivity(Activity::GROUP_PERMISSION_CHANGE, $this->persistent_filter['group_id'], 2, $owner->getUserId());
					$group->setOwner($user->getUserId());
					Activity::addActivity(Activity::GROUP_PERMISSION_CHANGE, $this->persistent_filter['group_id'], 2, $user->getUserId());
				}
			}
			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage, "Lister.groupmemberlister");
			$cache->clean(array(NCache::TAGS => array("group_id/".$this->persistent_filter['group_id'])));
		} else if (isset($this->persistent_filter['resource_id'])) {
			foreach ($values as $key => $value) {
				$values["resource_user_group_" . $key] = $value;
				unset($values[$key]);
			}
			
			$resource = Resource::create($this->persistent_filter['resource_id']);
			$resource->updateUser($user->getUserId(), $values);
			if ($values["resource_user_group_access_level"] == 3) {
				if (Auth::ADMINISTRATOR == Auth::isAuthorized(3,$this->persistent_filter['resource_id'])) {
					$owner = $resource->getOwner();
					$resource->updateUser($owner->getUserId(), array("resource_user_group_access_level" => 2));
					Activity::addActivity(Activity::RESOURCE_PERMISSION_CHANGE, $this->persistent_filter['resource_id'], 3, $owner->getUserId());
					$resource->setOwner($user->getUserId());
					Activity::addActivity(Activity::RESOURCE_PERMISSION_CHANGE, $this->persistent_filter['resource_id'], 3, $user->getUserId());
				}
			}
			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage, "Lister.resourcesubscriberlister");
			$cache->clean(array(NCache::TAGS => array("resource_id/".$this->persistent_filter['resource_id'])));
		} else {
			foreach ($values as $key => $value) {
				$values["user_" . $key] = $value;
				unset($values[$key]);
			}
			$user->setUserData($values);
			$user->save();
		}
		$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function createComponentGroupListItem($data_row)
	{
		$params     = NEnvironment::getHttpRequest()->getQuery("lister-page");
		$group      = Group::create($data_row['id']);
		$group_data = $group->getGroupData();
		$form       = new NAppForm($this, "groupform" . $data_row['id']);
		$form->addHidden('id');
		$form->addCheckbox('status');
		$form->addSubmit('send', _t('Update'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'adminGroupFormSubmitted'
		);
		$form->setDefaults(array(
			'id' => $data_row['id'],
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
		$values = $form->getValues();
		$group  = Group::create($values['id']);
		unset($values['id']);
		
		if (isset($this->persistent_filter['resource_id'])) {
			foreach ($values as $key => $value) {
				$values["resource_user_group_" . $key] = $value;
				unset($values[$key]);
			}
			
			$resource = Resource::create($this->persistent_filter['resource_id']);
			$resource->updateGroup($group->getGroupId(), $values);
		} else {
			foreach ($values as $key => $value) {
				$values["group_" . $key] = $value;
				unset($values[$key]);
			}
			
			$group->setGroupData($values);
			$group->save();
		}
		$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function createComponentResourceListItem($data_row)
	{
		$params        = NEnvironment::getHttpRequest()->getQuery("lister-page");
		$resource      = Resource::create($data_row['id']);
		$resource_data = $resource->getResourceData();
		$form          = new NAppForm($this, "resourceform" . $data_row['id']);
		$form->addHidden('id');
		$form->addCheckbox('status');
		$form->addSubmit('send', _t('Update'));
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'adminResourceFormSubmitted'
		);
		$form->setDefaults(array(
			'id' => $data_row['id'],
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
		$values   = $form->getValues();
		$resource = Resource::create($values['id']);
		unset($values['id']);
		foreach ($values as $key => $value) {
			$values["resource_" . $key] = $value;
			unset($values[$key]);
		}
		
		$resource->setResourceData($values);
		$resource->save();
		$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentFilter($name)
	{
		$options = array(
			'components' => array(
				$this->name
			),
			'refresh_path' => $this->refresh_path,
			'refresh_path_params' => $this->refresh_path_params
		);
		$options['include_tags'] = true;
		$user                    = NEnvironment::getUser()->getIdentity();
		if (!empty($user) && ($options['components'][0] == 'userlister') || ($options['components'][0] == 'grouplister') || ($options['components'][0] == 'defaultresourceresourcelister')) {
			$options['include_suggest'] = true;
		}
		
		foreach ($this->lister_type as $lt) {
			if ($lt == self::LISTER_TYPE_RESOURCE) {
				$options['include_type'] = true;
				if (isset($this->persistent_filter['type']) && ($this->persistent_filter['type'] == 7 || $this->persistent_filter['type'] == 8 || $this->persistent_filter['type'] == 1)) {
					unset($options['include_type']);
					unset($options['include_tags']);
				}
				if (isset($this->template_variables['messages'])) {
					unset($options['include_type']);
					unset($options['include_tags']);
					$options['include_trash'] = true;
				}
				
			}
			if (isset($this->template_variables['include_map'])) {
				$options['include_map'] = true;
			}
			
			if (isset($this->template_variables['include_pairing'])) {
				$options['include_pairing'] = true;
			}
			
			if (isset($this->template_variables['include_language'])) {
				$options['include_language'] = true;
			}
			
			if (isset($this->template_variables['include_name'])) {
				$options['include_name'] = true;
			}
			
			if (isset($this->template_variables['hide_filter'])) {
				$options['hide_filter'] = true;
			}
			
			if (isset($this->template_variables['hide_apply'])) {
				$options['hide_apply'] = true;
			}
			
			if (isset($this->template_variables['hide_reset'])) {
				$options['hide_reset'] = true;
			}

			if (isset($this->template_variables['resource_browser'])) {
				unset($options['include_type']);
				unset($options['include_tags']);
				$options['hide_reset'] = true;
				$options['hide_apply'] = true;
			}

		}
		
		$control = new ExternalFilter($this, $name, $options);
		
		return $control;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getPageData($filter)
	{
		
		if (!isset($filter['page'])) {
			$filter['page'] = 1;
		}
		$limit           = $this->pageTolimit($filter['page']);
		$filter['limit'] = $limit['from'];
		$filter['count'] = $limit['count'];
		
		
		if (!isset($this->template_variables['administration'])) {
			// not on "edit" pages but on "default" pages
			
			if ($this->name == 'userlister') {
				unset($filter['group_id']);
				unset($filter['resource_id']);
			}
			
			if ($this->name == 'grouplister') {
				unset($filter['user_id']);
				unset($filter['resource_id']);
			}
			
			if ($this->name == 'defaultresourceresourcelister') {
				unset($filter['group_id']);
				unset($filter['user_id']);
			}
			
		}
		
		$data = array();
		$data = Administration::getData($this->lister_type, $filter);
		
		return $data;
	}


	/**
	 *	Retrieves the number of items for a given filter setting.
	 *	@param array $filter
	 *	@return int
	 */
	public function getDataCount($filter)
	{
		$data = array();
		
		$data = Administration::getData($this->lister_type, $filter, true);
		
		return $data;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function setRefreshPath($path, $params = array())
	{
		$this->refresh_path        = $path;
		$this->refresh_path_params = $params;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentEmptytrashform()
	{
		$user = NEnvironment::getUser()->getIdentity();
		$form = new NAppForm($this, 'emptytrashform');
		$form->addSubmit('empty', _t('Empty Trash'));
		
		$form->onSubmit[] = array(
			$this,
			'emptytrashformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function emptytrashformSubmitted(NAppForm $form)
	{
		Resource::emptyTrash();

		$user = NEnvironment::getUser()->getIdentity();
				
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage);
		$cache->clean(array(NCache::TAGS => array("user_id/".$user->getUserId(), "name/messagelisteruser")));
		$this->flashMessage(_t("Trash emptied."));
		$this->handleChangePage(1);
	}
}