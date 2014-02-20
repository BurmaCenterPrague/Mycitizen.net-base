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
 

class TagContainer extends NFormContainer {
	protected $defaultComponent = "none";
    protected $id = 0;	

/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function __construct($id,$defaultComponent = "none") {
		parent::__construct();
		$this->monitor('NForm');
		$this->id = $id;
		$this->defaultComponent = $defaultComponent;	
	}

	 /**
	  *	@todo ### Description
	  *	@param
	  *	@return
	  */
	 protected function attached($obj) {
     	parent::attached($obj);

        if (!$obj instanceof NFormContainer) { // because of lookups
                        return ;
         }

			$this->addComponent(new ContainerTreeSelectControl(_t('Add tag').':'), 'tag_id');
	
     }

}
