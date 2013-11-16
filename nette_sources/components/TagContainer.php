<?php
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
