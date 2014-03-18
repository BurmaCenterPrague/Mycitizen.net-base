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
 

class ResourceListerControl extends ListerControl
{

/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function __construct($parent, $name, $options)
	{
		
		parent::__construct($parent, $name, $options);
		$this->setRefreshPath("Resource:default");
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
		$template->setFile(dirname(__FILE__) . '/ResourceListerControl_filter.phtml');
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
		$template->setFile(dirname(__FILE__) . '/ResourceListerControl.phtml');
		$template->render();
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function createComponentListItem($data_row)
	{
		$params        = NEnvironment::getHttpRequest()->getQuery("lister-page");
		$resource      = Resource::create($data_row['resource_id']);
		$resource_data = $resource->getResourceData();
		$form          = new NAppForm($this, "resourceform" . $data_row['resource_id']);
		$form->addHidden('resource_id');
		$form->addCHeckbox('resource_status');
		$form->addSubmit('send', 'Update');
		$form->addProtection(_t('Error submitting form.'));
		$form->onSubmit[] = array(
			$this,
			'adminResourceFormSubmitted'
		);
		$form->setDefaults(array(
			'resource_id' => $data_row['resource_id'],
			'resource_status' => $resource_data['resource_status']
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
		$resource = Resource::create($values['resource_id']);
		unset($values['resource_id']);
		$resource->setResourceData($values);
		$resource->save();
		$this->getPresenter()->redirect("Resource:default");
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function createComponentFilter()
	{
		$type = Resource::getTypeArray();
		$form = new NAppForm($this, "filter");
		$form->addText('name', 'Name');
		$enabled = array(
			'null' => 'All',
			'1' => 'Active',
			'0' => 'Inactive'
		);
		$form->addSelect('resource_status', 'Resource status', $enabled);
		$form->addSelect('resource_type', 'Type', $type);
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
		$this->getPresenter()->redirect("Resource:default");
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getDataCount($filter)
	{
		$data = Administration::getAllResources($filter);
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
		$data            = Administration::getAllResources($filter);
		return $data;
	}
	
}
