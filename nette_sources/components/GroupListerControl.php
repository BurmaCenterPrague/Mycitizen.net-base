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
 

class GroupListerControl extends ListerControl
{
	const GROUPLISTER_NORMAL = 0;
	const GROUPLISTER_RESOURCE = 1;
	
	protected $group_lister_type = self::GROUPLISTER_NORMAL;
	protected $object_id = null;
	
	public function __construct($parent, $name, $options)
	{
		
		parent::__construct($parent, $name, $options);
		$this->setRefreshPath("Group:default");
		if (isset($options['widget']) && $options['widget'] == self::GROUPLISTER_RESOURCE && isset($options['resource_id'])) {
			$this->group_lister_type = self::GROUPLISTER_RESOURCE;
			$this->object_id         = $options['resource_id'];
		}
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
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/GroupListerControl_filter.phtml');
		$template->refresh_path = $this->refresh_path;
		if ($this->group_lister_type == self::GROUPLISTER_RESOURCE) {
			$template->resource_widget = true;
			$template->resource_id     = $this->object_id;
		}
		
		$user = NEnvironment::getUser()->getIdentity();
		if (!empty($user)) {
			$template->user_id = $user->getUserId();
		}
		
		$template->render();
		
	}
	
	public function renderBody()
	{
		parent::renderBody();
		$template = $this->template;
		$template->setFile(dirname(__FILE__) . '/GroupListerControl.phtml');
		$template->refresh_path = $this->refresh_path;
		if ($this->group_lister_type == self::GROUPLISTER_RESOURCE) {
			$template->resource_widget = true;
			$template->resource_id     = $this->object_id;
		}
		
		$user = NEnvironment::getUser()->getIdentity();
		if (!empty($user)) {
			$template->user_id = $user->getUserId();
		}
		
		$template->img = Group::getImage($this->object_id, 'img');
		$template->icon = Group::getImage($this->object_id, 'icon');
		$template->large_icon = Group::getImage($this->object_id, 'large_icon');
		
		$template->render();
		
		
	}
	
	public function createComponentListItem($data_row)
	{
		$params     = NEnvironment::getHttpRequest()->getQuery("lister-page");
		$group      = Group::create($data_row['group_id']);
		$group_data = $group->getGroupData();
		$form       = new NAppForm($this, "groupform" . $data_row['group_id']);
		$form->addHidden('group_id');
		$form->addCHeckbox('group_status');
		$form->addSubmit('send', 'Update');
		$form->addProtection(_('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'adminGroupFormSubmitted'
		);
		$form->setDefaults(array(
			'group_id' => $data_row['group_id'],
			'group_status' => $group_data['group_status']
		));
		return $form;
	}
	public function adminGroupFormSubmitted(NAppForm $form)
	{
		$values = $form->getValues();
		$group  = Group::create($values['group_id']);
		unset($values['group_id']);
		$group->setGroupData($values);
		$group->save();
		$this->getPresenter()->redirect("Group:default");
	}
	public function createComponentFilter()
	{
		$form = new NAppForm($this, "filter");
		$form->addText('name', 'Name');
		$enabled = array(
			'null' => 'All',
			'1' => 'Active',
			'0' => 'Inactive'
		);
		$form->addSelect('group_status', 'Group status', $enabled);
		$form->addSubmit('filter', 'Filter');
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
		$this->getPresenter()->redirect("Group:default");
	}
	public function getDataCount($filter)
	{
		$data = Administration::getAllGroups($filter);
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
		$data            = Administration::getAllGroups($filter);
		return $data;
	}
	
}
