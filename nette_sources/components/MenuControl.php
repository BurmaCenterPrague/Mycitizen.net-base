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
 

class MenuControl extends NControl {
	const MENU_STYLE_TABLE = 2;
	const MENU_STYLE_CLASSIC = 1;
	const MENU_STYLE_TREE = 3;

	const MENU_ORIENTATION_HORIZONTAL = 'h';
	const MENU_ORIENTATION_VERTICAL = 'v';

	protected $menudata = null;
	protected $columns = 4;
	protected $rows = 4;
	protected $orientation = "horizontal";
	protected $styletemplate = "MenuControl_classic";
	public function __construct($parent,$name,$menu_data) {
		parent::__construct($parent,$name);
		$this->prepareData($menu_data);
	}
	public function render() {
      $template = $this->template;
		$session = NEnvironment::getSession()->getNamespace("GLOBAL");
      $language = $session->language;
      if(empty($language)) {
         $session->language = 'en_US';
         $language = $session->language;
      }
      $template->setTranslator(new GettextTranslator('../locale/'.$language.'/LC_MESSAGES/messages.mo', $language));

      $template->setFile(dirname(__FILE__) . '/'.$this->styletemplate.'.phtml');
		$template->orientation = $this->orientation;
		$template->columns = $this->columns;
		$template->rows = $this->rows;
		$template->name = $this->name;
		$template->data = $this->menudata;
        $template->render();
    }
	public function prepareData($m_data) {
		$data = array();
		$parent_sort = array();
		$index_sort = array();
		$key_sort = array();
		foreach($m_data as $key=>$row) {
			if(!isset($row['parent'])) {
				$parent = $key;
				$index = 0;
			} else {
				$parent = $row['parent'];
			}
			if($key == $parent) {
				$index = 0;
			}
			if(!isset($index) && !isset($row['index'])) {
				$index = 1;
			}
			if(!isset($index) && isset($row['index'])) {
				$index = $row['index'];
			}
			if($index == 0 && $key != $parent) {
				$index = 1;
			}
			$url = "javascript:void(0);";
			$parameters = array();
			if(isset($row['parameters'])) {
				$parameters = $row['parameters'];
			}
			if(isset($row['presenter']) && isset($row['action'])) {
				$url = $this->getPresenter()->link($row['presenter'].":".$row['action'],$parameters);
			} elseif(isset($row['presenter']) && !isset($row['action'])) {
				$url = $this->getPresenter()->link($row['presenter'].":default",$parameters);
			}
			$content = "";
			if(isset($row['content'])) {
				$content = $row['content'];
			}
			$parent_sort[] = $parent;
			$index_sort[] = $index;
			$key_sort[] = $key; 
			$data_data = array(
                     'key'=>$key,
                     'title'=>$row['title'],
                     'url'=>$url,
                     'parent'=>$parent,
                     'index'=>$index,
                     'content'=>$content
                     );
			if(isset($row['active'])) {
         	$data_data['active'] = true;   
         }

			$data[] = $data_data;
		}
		//array_multisort($parent_sort,SORT_ASC,$index_sort,SORT_ASC,$key_sort,SORT_ASC,$data);
		$this->menudata = $data;
	}
	public function setStyle($style) {
		if($style == $this::MENU_STYLE_CLASSIC) {
			$this->styletemplate = "MenuControl_classic";
		} elseif($style == $this::MENU_STYLE_TABLE) {
			$this->styletemplate = "MenuControl_table";
		} elseif($style == $this::MENU_STYLE_TREE) {
			$this->styletemplate = "MenuControl_tree";
		} else {
			$this->styletemplate = "MenuControl_classic";	
		}
	}
	public function setOrientation($or) {
		if($or == $this::MENU_ORIENTATION_VERTICAL) {
			$this->orientation = "vertical";
		} else {
			$this->orientation = "horizontal";
		}
	}
	public function setTableDimensions($row = null,$col = null) {
		if(!is_null($row) && is_null($col)) {
			$this->rows = $row;
			$this->columns = $row;
		} elseif(!is_null($col) && is_null($row)) {
			$this->rows = $col;
			$this->columns = $col;
		} else {
			$this->rows = $row;
			$this->columns = $col;
		}	
	}
}
