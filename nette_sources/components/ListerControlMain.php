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

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
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
		
		$filter           = $this->getFilterArray();
		$this->itemscount = $this->getDataCount($filter);
		if (!empty($filter['page'])) {
			$this->setCurrentPage($filter['page']);
			if ($this->currentpage != $filter['page']) { // check if current page setting is higher than max page after changing from another screen
				$filter['page'] = $this->currentpage;
				$this->setFilterArray($filter);
			}
		}
		$this->generateList();
		$this->registerHelpers();
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function registerHelpers()
	{
		$this->template->registerHelper('htmlpurify', function($dirty_html)
		{
			require_once LIBS_DIR . '/HTMLPurifier/HTMLPurifier.auto.php';
			$config = HTMLPurifier_Config::createDefault();
			$config->set('Attr.EnableID', true);
			$config->set('Attr.IDBlacklistRegexp', '/^(?!((quoting_\d+)|(reply\d+))).*/'); // blacklisting all id attributes that don't start with "quoting_" followed by a number
			$config->set('HTML.Nofollow', true);
			$config->set('HTML.Allowed', 'h2,h3,h4,a[href|target|rel],strong,b,div,br,img[src|alt|height|width|style],dir,span[style],blockquote[id],ol,ul,li[type],pre,u,hr,code,strike,sub,sup,p[style],table,tr,td[colspan],th,iframe[src|width|height|frameborder]');
			$config->set('HTML.SafeIframe', true);
			$config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www.youtube.com/embed/.*)|(player.vimeo.com/video/)%'); //allow YouTube and Vimeo
			$config->set('Filter.YouTube', true);
			$purifier = new HTMLPurifier($config);
			return $purifier->purify($dirty_html);
		});
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function render()
	{
		$this->renderFilter();
		$this->renderBody();
	}
	
	/**
	 *	Outputs status of filter as readable text
	 */

/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function renderFiltercheck()
	{
		if ($this->activeFilter())
			echo _t("Filter is on.");
		else
			echo _t("Filter is off.");
	}
	
	/**
	 *	Outputs status of filter for use in class
	 */

/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function renderFilterstatus()
	{
		if ($this->activeFilter())
			echo "on";
		else
			echo "off";
	}
	
	/**
	 *	Checks whether filter is active or not
	 */

/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function activeFilter()
	{
		$active = false;
		$filter = $this->getFilterArray();
		if (isset($filter['name']) && strlen($filter['name']) > 0)
			$active = true;
		elseif (!empty($filter['type']) && !is_array($filter['type']) && $this->name == 'defaultresourceresourcelister')
			$active = true;
		elseif (isset($filter['language']) && $filter['language'] > 0)
			$active = true;
		elseif (isset($filter['mapfilter']) && $filter['mapfilter'] != 'null')
			$active = true;
		
		if (!$active && isset($filter['tags']))
			foreach ($filter['tags'] as $key => $value) {
				if ($key != 'all' && $value == true) {
					$active = true;
					break;
				}
			}
		return $active;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
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
		$template->setTranslator(new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/messages.mo', $language));
		
		$template->setFile(dirname(__FILE__) . '/' . $this->template_filter);
		if ($this->getParent()->name !== $this->presenter->name) {
			$template->name = $this->getParent()->name . "-" . $this->name;
		} else {
			$template->name = $this->name;
			
		}
		//$this['filter']->setDefaults($this->getFilterArray());
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
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function renderBody()
	{
		$template = $this->template;
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}
		$template->setTranslator(new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/messages.mo', $language));
		
		$template->setFile(dirname(__FILE__) . '/' . $this->template_body);
		if ($this->getParent()->name !== $this->presenter->name) {
			$template->name = $this->getParent()->name . "-" . $this->name;
		} else {
			$template->name = $this->name;
		}
		$template->persistent_filter  = $this->persistent_filter;
		$template->data               = $this->data;
		$template->template_variables = $this->template_variables;
		$template->refresh_path       = $this->refresh_path;
		$user                         = NEnvironment::getUser()->getIdentity();
		if (!empty($user)) {
			$template->user_id = $user->getUserId();
		}
		
		$template->currentpage = $this->currentpage;
		$template->max_page    = $this->getMaxPage();
		$template->lister_type = $this->lister_type;
		$template->active_filter = $this->activeFilter();
		$template->baseUri = NEnvironment::getVariable("URI") . '/';
		
		$template->render();
		
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function setItemsCount($count)
	{
		$this->itemscount = $count;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getItemsCount()
	{
		return $this->itemscount;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function setItemsPerPage($count)
	{
		$this->itemsperpage = $count;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getMaxPage()
	{
		$maxpage = (int) ($this->itemscount / $this->itemsperpage);
		if ($this->itemscount % $this->itemsperpage != 0) {
			$maxpage++;
		}
//		var_dump($this->itemsperpage);die();
		return $maxpage;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
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
	 *	@todo ### Description
	 *	@param
	 *	@return
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

		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Lister.".$this->name);
		$dont_cache = array('chatwidget','pmwidget', 'chatlistergroup');
		$cache->clean();
		$cache_key = md5(json_encode($filter));
		if (!in_array($this->name, $dont_cache) && $cache->offsetExists($cache_key)) {
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
				$this->data = array_diff($this->data, $data_connections);
			}
			if (!in_array($this->name, $dont_cache)) $cache->save($cache_key, $this->data, array(NCache::EXPIRE => time()+120));
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
	 *	@todo ### Description
	 *	@param
	 *	@return
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
	 *	@todo ### Description
	 *	@param
	 *	@return
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
	 *	@todo ### Description
	 *	@param
	 *	@return
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
			if (isset($filter['type']) && $filter['type'] == 'all') {
				$filter['type'] = 'all';
			} elseif (!isset($filter['type'])) {
				$filter['type'] = 'all';
			}
		} else {
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
		}
		
		return $filter;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleChangePage($page)
	{
		$filter = $this->getFilterArray();
		$this->setCurrentPage($page);
		$filter['page'] = $this->currentpage;
		$this->setFilterArray($filter);
		$this->invalidateControl('list_body');
		$this->invalidateControl('list_pager');
		$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
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
	 *	Define administration form for connected users.
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
//		No Group Administrator because this is reserved for the owner.
		$access_level = array(
			'1' => _t('Normal'),
			'2' => _t('Moderator')
		);
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
	 *	@todo ### Description
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
		} else if (isset($this->persistent_filter['resource_id'])) {
			foreach ($values as $key => $value) {
				$values["resource_user_group_" . $key] = $value;
				unset($values[$key]);
			}
			
			$resource = Resource::create($this->persistent_filter['resource_id']);
			$resource->updateUser($user->getUserId(), $values);
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
	 *	@todo ### Description
	 *	@param
	 *	@return
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
		$user = NEnvironment::getUser()->getIdentity();
		
		Resource::emptyTrash();
		$this->flashMessage(_t("Trash emptied."));
		//		$this->redirect("User:messages");
		$this->handleChangePage(1);
	}
}