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
 

class TagListerControl extends NControl
{
	protected $currentpage = 1;
	protected $itemscount = 0;
	protected $itemsperpage = 10;
	protected $itemsonbar = 10;
	protected $data = null;
	protected $refresh_path = "Homepage:default";
	protected $refresh_path_params = array();
	protected $template_source = "ListerControl.phtml";

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function __construct($parent, $name, $itemsPerPage = null)
	{
		parent::__construct($parent, $name);
		if (!is_null($itemsPerPage)) {
			$this->setItemsPerPage($itemsPerPage);
		}
		
		$filter           = $this->getFilterArray();
		$this->itemscount = $this->getDataCount($filter);
		if (!empty($filter['page'])) {
			$this->setCurrentPage($filter['page']);
		}
		$this->generateList();
		
		$this->setRefreshPath("Administration:tags");
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
			$language = $session->language;
		}
		$template->setTranslator(new GettextTranslator(LOCALE_DIR . '/' . $language . '/LC_MESSAGES/messages.mo', $language));
		
		$template->setFile(dirname(__FILE__) . '/TagListerControl_filter.phtml');
		$template->data = $this->data;
		$template->render();
		
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function renderBody()
##### needed?
	{
		$template = $this->template;
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language = $session->language;
		}
		$template->setTranslator(new GettextTranslator(LOCALE_DIR . '/' . $language . '/LC_MESSAGES/messages.mo', $language));
		
		$template->setFile(dirname(__FILE__) . '/TagListerControl.phtml');
		if ($this->getParent()->name !== $this->presenter->name) {
			
			$template->name = $this->getParent()->name . "-" . $this->name;
		} else {
			$template->name = $this->name;
			
		}
		$template->data        = $this->data;
		$template->currentpage = $this->currentpage;
		$template->max_page    = $this->getMaxPage();

		
		$template->render();
		
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function createComponentListItem($data_row)
	{
		$params   = NEnvironment::getHttpRequest()->getQuery("lister-page");
		$tag      = Tag::create($data_row['tag_id']);
		$tag_data = $tag->getTagData();
		$form     = new NAppForm($this, "tagform" . $data_row['tag_id']);
		$form->addHidden('tag_id');
		$form->addText('tag_name', 'Tag name:');
		$form->addComponent(new ContainerTreeSelectControl(_t('Parent level:'), $data_row['tag_id']), 'tag_parent_id');
		$form->addSubmit('send', 'Update');
		$form->addSubmit('remove', _t('Remove'));
		$form->onSubmit[] = array(
			$this,
			'adminUserFormSubmitted'
		);
		$form->setDefaults(array(
			'tag_id' => $data_row['tag_id'],
			'tag_name' => $data_row['tag_name'],
			'tag_parent_id' => $data_row['tag_parent_id']
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
		$tag    = Tag::create($values['tag_id']);
		$tag_id = $values['tag_id'];
		if ($values['tag_id'] == $values['tag_parent_id']) {
			$values['tag_parent_id'] == 0;
		}
		if ($form['send']->isSubmittedBy()) {
			$tag->setTagData($values);
			$tag->save();
		} else if ($form['remove']->isSubmittedBy()) {
			Tag::remove($tag_id);
		}
		unset($values['tag_id']);
		$this->getPresenter()->redirect("Administration:tags");
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
		$form->addSubmit('filter', 'Filter');
		$form->addSubmit('reset', 'Reset');
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
		unset($filter['page']);
		if ($form['reset']->isSubmittedBy()) {
			$this->setFilterArray(array());
		} else {
			$new_filter = array_merge($filter, $values);
			$this->setFilterArray($new_filter);
		}
		$this->getPresenter()->redirect("Administration:tags");
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getDataCount($filter)
	{
		$data = Administration::getAllTags($filter);
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
		$data            = Administration::getAllTags($filter);
		return $data;
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
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function generateList()
	{
		$this->data = $this->getPageData($this->getFilterArray());
		
		foreach ($this->data as $data_row) {
			$this->createComponentListItem($data_row);
		}
		
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function setFilterArray($filter)
	{
		$session             = NEnvironment::getSession()->getNamespace($this->name);
		$session->filterdata = $filter;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getFilterArray()
	{
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if (empty($session->filterdata)) {
			return array();
		}
		$filter = $session->filterdata;
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
		$this->getPresenter()->redirect("Administration:tags");
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
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function setRefreshPath($path, $params = array())
	{
		$this->refresh_path        = $path;
		$this->refresh_path_params = $params;
	}
	
	
}
