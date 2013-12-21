<?php
/**
 * mycitizen.net - Open source social networking for civil society
 *
 * @version 0.2.2 beta
 *
 * @author http://mycitizen.org
 * @copyright  Copyright (c) 2013 Burma Center Prague (http://www.burma-center.org)
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */
 

class TagContainer extends NFormContainer {
	protected $defaultComponent = "none";
    protected $id = 0;	
	public function __construct($id,$defaultComponent = "none") {
		parent::__construct();
		$this->monitor('NForm');
		$this->id = $id;
		$this->defaultComponent = $defaultComponent;	
	}
	
	 protected function attached($obj) {
     	parent::attached($obj);

        if (!$obj instanceof NFormContainer) { // because of lookups
                        return ;
         }

			$this->addComponent(new ContainerTreeSelectControl(_('Add tag').':'), 'tag_id');
	
     }

}
