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
 

class GroupUserListerControl extends ListerControl
{
	protected $group = null;
	protected $group_id = null;
	public function __construct($parent, $name, $group_id, $options)
	{
		$this->group    = Group::create($group_id);
		$this->group_id = $group_id;
		
		parent::__construct($parent, $name, $options);
		$this->setRefreshPath("Group:edit", array(
			'group_id' => $this->group_id
		));
	}
	
	public function render()
	{
		parent::render();
		$this->renderFilter();
		$this->renderBody();
	}
	
	public function renderFilter()
	{
		parent::renderFilter();
		$template              = $this->template;
		$template->object_type = 2;
		$template->object_id   = $this->group_id;
		$template->setFile(dirname(__FILE__) . '/GroupUserListerControl_filter.phtml');
		$template->render();
		
	}
	
	public function renderBody()
	{
		parent::renderBody();
		$template              = $this->template;
		$template->object_type = 2;
		$template->object_id   = $this->group_id;
		$template->setFile(dirname(__FILE__) . '/GroupUserListerControl.phtml');
		$template->render();
	}
	
	public function createComponentListItem($data_row)
	{
		$params = NEnvironment::getHttpRequest()->getQuery("lister-page");
		$form   = new NAppForm($this, "userform" . $data_row['user_id']);
		$form->addHidden('user_id');
		$access_level = array(
			'1' => 'Normal user',
			'2' => 'Moderator',
			'3' => 'Administrator'
		);
		$form->addSelect('group_user_access_level', null, $access_level);
		$form->addCHeckbox('group_user_status');
		$form->addSubmit('send', 'Update');
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'adminUserFormSubmitted'
		);
		$form->setDefaults(array(
			'user_id' => $data_row['user_id'],
			'group_user_access_level' => $data_row['group_user_access_level'],
			'group_user_status' => $data_row['group_user_status']
		));
		return $form;
	}
	
	public function adminUserFormSubmitted(NAppForm $form)
	{
		$values  = $form->getValues();
		$user_id = $values['user_id'];
		unset($values['user_id']);
		$this->group->updateUser($user_id, $values);
		$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
	}
	
	public function createComponentFilter()
	{
		$form = new NAppForm($this, "filter");
		$form->addText('name', 'Name');
		$access_level = array(
			'null' => 'All',
			'1' => 'Normal user',
			'2' => 'Moderator',
			'3' => 'Administrator'
		);
		$form->addSelect('group_user_access_level', 'User permissions', $access_level);
		$enabled = array(
			'null' => 'All',
			'1' => 'Active',
			'0' => 'Inactive'
		);
		$form->addSelect('group_user_status', 'User Status', $enabled);
		$form->addSubmit('filter', 'Apply filter');
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'filterFormSubmitted'
		);
		$form->setDefaults($this->getFilterArray());
		return $form;
	}
	
	public function filterFormSubmitted(NAppForm $form)
	{
		$values     = $form->getValues();
		$filter     = $this->getFilterArray();
		$new_filter = array_merge($filter, $values);
		
		$this->setFilterArray($new_filter);
		$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
	}
	
	public function getDataCount($filter)
	{
		$data = $this->group->getAllUsers($filter);
		return count($data);
	}
	
	public function getPageData($filter)
	{
		if (!isset($filter['page'])) {
			$filter['page'] = 1;
		}
		$limit           = $this->pageTolimit($filter['page']);
		$filter['limit'] = $limit['from'];
		$filter['count'] = $limit['count'];
		$data            = $this->group->getAllUsers($filter);
		return $data;
	}
}
