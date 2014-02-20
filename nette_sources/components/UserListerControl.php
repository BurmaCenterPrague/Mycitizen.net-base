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
 

class UserListerControl extends ListerControl
{
	const USERLISTER_NORMAL = 0;
	const USERLISTER_RESOURCE = 1;
	
	protected $user_lister_type = self::USERLISTER_NORMAL;
	protected $object_id = null;

/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function __construct($parent, $name, $options)
	{
		parent::__construct($parent, $name, $options);
		$this->setRefreshPath("User:default");
		if (isset($options['widget']) && $options['widget'] == self::USERLISTER_RESOURCE && isset($options['resource_id'])) {
			$this->user_lister_type = self::USERLISTER_RESOURCE;
			$this->object_id        = $options['resource_id'];
		}
	}

/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function render()
	{
		parent::render();
		$this->renderFilter();
		$this->renderBody();
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function renderFilter()
	{
		parent::renderFilter();
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/UserListerControl_filter.phtml');
		$template->refresh_path = $this->refresh_path;
		if ($this->user_lister_type == self::USERLISTER_RESOURCE) {
			$template->resource_widget = true;
			$template->resource_id     = $this->object_id;
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
		parent::renderBody();
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/UserListerControl.phtml');
		$template->refresh_path = $this->refresh_path;
		if ($this->user_lister_type == self::USERLISTER_RESOURCE) {
			$template->resource_widget = true;
			$template->resource_id     = $this->object_id;
		}
		$template->render();
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function createComponentListItem($data_row)
	{
		$params    = NEnvironment::getHttpRequest()->getQuery("lister-page");
		$user      = User::create($data_row['user_id']);
		$user_data = $user->getUserData();
		$form      = new NAppForm($this, "userform" . $data_row['user_id']);
		$form->addHidden('user_id');
		$access_level = array(
			'1' => 'Normal user',
			'2' => 'Moderator',
			'3' => 'Administrator'
		);
		$form->addSelect('user_access_level', null, $access_level);
		$form->addCHeckbox('user_status');
		$form->addSubmit('send', 'Update');
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'adminUserFormSubmitted'
		);
		$form->setDefaults(array(
			'user_id' => $data_row['user_id'],
			'user_access_level' => $user_data['user_access_level'],
			'user_status' => $user_data['user_status']
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
		$user   = User::create($values['user_id']);
		unset($values['user_id']);
		$user->setUserData($values);
		$user->save();
		$this->getPresenter()->redirect("User:default");
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function createComponentFilter()
	{
		$form = new NAppForm($this, "filter");
		$form->addText('name', 'Name');
		$access_level = array(
			'null' => 'all',
			'1' => 'Normal user',
			'2' => 'Moderator',
			'3' => 'Administrator'
		);
		$form->addSelect('user_access_level', 'User permissions', $access_level);
		$enabled = array(
			'null' => 'All',
			'1' => 'Active',
			'0' => 'Inactive'
		);
		$form->addSelect('user_status', 'User status', $enabled);
		$form->addSubmit('filter', 'Apply filter');
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'filterFormSubmitted'
		);
		$form->setDefaults($this->getFilterArray());
		return $form;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function filterFormSubmitted(NAppForm $form)
	{
		$values     = $form->getValues();
		$filter     = $this->getFilterArray();
		$new_filter = array_merge($filter, $values);
		
		$this->setFilterArray($new_filter);
		$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getDataCount($filter)
	{
		$data = Administration::getAllUsers($filter);
		return count($data);
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
		$data            = Administration::getAllUsers($filter);
		return $data;
	}
}
