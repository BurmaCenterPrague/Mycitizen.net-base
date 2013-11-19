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
 

abstract class ListerControl extends NControl {
	protected $currentpage = 1;
	protected $itemscount = 0;
	protected $itemsperpage = 10;
	protected $itemsonbar = 10;
	protected $data = null;
	protected $refresh_path = "Homepage:default";
	protected $refresh_path_params = array();
	protected $template_source = "ListerControl.phtml";
	public function __construct($parent,$name,$options = array()) {
		parent::__construct($parent,$name);
		if(isset($options['itemsPerPage'])) {
			$this->setItemsPerPage($options['itemsPerPage']);
		}
		if(isset($options['template'])) {
         $this->template_source = $options['template'];
      }


		$filter = $this->getFilterArray();
		$this->itemscount = $this->getDataCount($filter);
		if(!empty($filter['page'])) {
            $this->setCurrentPage($filter['page']);
        }
		$this->generateList();
	}
	public function render() {
		$this->renderFilter();
		$this->renderBody();
	}
	public function renderFilter() {
		$template = $this->template;
		$template->data = $this->data;
		$template->source_template = 'ListerControl_filter.phtml';
		//$template->setFile(dirname(__FILE__) . '/ListerControl_filter.phtml');
	}
	public function renderBody() {
		$template = $this->template;
		if($this->getParent()->name !== $this->presenter->name) {

         $template->name = $this->getParent()->name."-".$this->name;
      } else {
         $template->name = $this->name;

      }
      $template->source_template = 'ListerControl.phtml';//$this->template_source;
      $template->data = $this->data;
      $template->currentpage = $this->currentpage;
      $template->max_page = $this->getMaxPage();
      if($this->currentpage - $this->itemsonbar < 1) {
         $template->start = 1;
         $correction = 1 - ($this->currentpage - $this->itemsonbar);
         if($this->currentpage + $this->itemsonbar > $this->getMaxPage()) {
               $template->end = $this->getMaxPage();
         } else {
               $template->end = $this->currentpage + $this->itemsonbar;
         }

         if($template->end + $correction <= $this->getMaxPage()) {
                $template->end += $correction;
            } else {
                $template->end = $this->getMaxPage();
            }
      } else {
         $template->start = $this->currentpage - $this->itemsonbar;
      }
      if($this->currentpage + $this->itemsonbar > $this->getMaxPage()) {
         $template->end = $this->getMaxPage();
         $correction = $this->getMaxPage() - ($this->currentpage + $this->itemsonbar);
         if($template->start + $correction >= 1) {
            $template->start += $correction;
         } else {
            $template->start = 1;
         }
      } else {
         if($this->currentpage + $this->itemsonbar < (2*$this->itemsonbar + 1)) {

         } else {
            $template->end = $this->currentpage + $this->itemsonbar;
         }
      }
	}
	public function setItemsCount($count) {
		$this->itemscount = $count;
	}
	public function setItemsPerPage($count) {
		$this->itemsperpage = $count;
	}
	public function getMaxPage() {
		$maxpage = (int) ($this->itemscount/$this->itemsperpage);
        if($this->itemscount % $this->itemsperpage != 0) {
            $maxpage++;
        }
		return $maxpage;
	}
	public function setCurrentPage($page) {
		$maxpage = $this->getMaxPage();
		if($page >= $maxpage) {
			$page = $maxpage;
		}
		if($page < 1) {
			$page = 1;
		}
		$this->currentpage = $page;
	}
	
	public function getPageFirstIndex($page) {
		$itemsonpage = $this->itemsperpage;
		return $itemsonpage * ($page - 1);
	}
	
	public function generateList() {
        $this->data = $this->getPageData($this->getFilterArray());
        foreach($this->data as $data_row) {
            $this->createComponentListItem($data_row);
        }
	}
	
	public function setFilterArray($filter) {
		$session = NEnvironment::getSession()->getNamespace($this->name);
		$session->filterdata = $filter;
	}
	
	public function getFilterArray() {
		$session = NEnvironment::getSession()->getNamespace($this->name);
		if(empty($session->filterdata)) {
			return array();
		}
		$filter = $session->filterdata;
		return $filter;
	}
	
	public function handleChangePage($page) {
		$filter = $this->getFilterArray();
		$this->setCurrentPage($page);
		$filter['page'] = $this->currentpage;
		$this->setFilterArray($filter);
		$this->invalidateControl('list_body');
		$this->invalidateControl('list_pager');
		//$this->getPresenter()->redirect($this->refresh_path, $this->refresh_path_params);
	}

	public function pageToLimit($page) {
		if(!empty($page)) {
			$pageFirstIndex = $this->getPageFirstIndex($page);
			return array('from'=>$pageFirstIndex,'count'=>$this->itemsperpage);	
		}
		return array('from'=>0,'count'=>$this->itemsperpage);
	}
	
	abstract public function createComponentListItem($data_row);
	
	abstract public function getPageData($filter);
	
	abstract public function getDataCount($filter);
	
	public function setRefreshPath($path,$params = array()) {
		$this->refresh_path = $path;
		$this->refresh_path_params = $params;
	}


}
