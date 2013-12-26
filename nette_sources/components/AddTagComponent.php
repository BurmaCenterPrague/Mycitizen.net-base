<?php
/**
 * mycitizen.net - Open source social networking for civil society
 *
 * @version 0.3 beta
 *
 * @author http://mycitizen.org
 * @copyright  Copyright (c) 2013 Burma Center Prague (http://www.burma-center.org)
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */
 


class AddTagComponent extends NFormControl {

  protected $template = null;
  protected $tree = null;	
  protected $selected_value = null;
  protected $tag_names = null;

	protected $container = "user";
	protected $container_id = null;

  public function  __construct($container = "user", $container_id = null, $caption = NULL,$options = array()) {
    parent::__construct($caption);
	  $this->tag_names[0] = array('tag_id'=>0,'tag_name'=>'without parent');
		$this->container = $container;
		$this->container_id = $container_id;
  }

  public function getControl() {
    //Set up template
	 $control = parent::getControl();
    $template = $this->template = new NTemplate();
    $template->registerFilter(new NLatteFilter);
    $template->registerHelper('escape', 'NTemplateHelpers::escapeHtml');
    $template->registerHelper('escapeJs', 'NTemplateHelpers::escapeJs');
    $template->registerHelper('escapeCss', 'NTemplateHelpers::escapeCss');
	 if($this->container == "user") {
    	$template->setFile(dirname(__FILE__) . '/AddTagComponent_User.phtml');
	 }	else if($this->container == "group") {
		$template->setFile(dirname(__FILE__) . '/AddTagComponent_Group.phtml');
	 } else if($this->container == "resource") {
		$template->setFile(dirname(__FILE__) . '/AddTagComponent_Resource.phtml');
	 } 
    $template->control = $this;
	 $template->container_id = $this->container_id;
	 $template->name = $control->name;
	 $this->tree = Tag::getTreeArray();
    $template->tree_array = $this->tree;

    //Fill the array indicating whether the current user can select the folder in the selector
    $object_type_name_array = array();
    foreach($template->tree_array as $node) {
	 	$this->tag_names[$node['tag_id']] = $node;
    }
    //Add the first level (master root) which is not in tree_array
    $template->object_type_name_array = $this->tag_names;

    //Get complete path to the active node (without the active node itself)
    $active_node_path = array();
    $node_id = 0;
	 $node_parent_id = $this->tag_names[$node_id]['tag_id'];
	 while($node_id != $node_parent_id) {
	 	$node_id = $this->tag_names[$node_id]['tag_parent_id'];
		$node_parent_id = $this->tag_names[$node_id]['tag_id'];

	 	$active_node_path[] = $node_id;
    }
    $this->template->active_node_path = $active_node_path;
 
    $this->template->selected_value = $this->getPathString($node_id);
    //Get and return output from template
    ob_start();
    $template->render();
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
    return null;
  }

  public function  setValue($value) {
    $this->selected_value = $this->getPathString($value);
    parent::setValue($value);
  }
  
  public function getPathString($tag_id) {
    return $this->tag_names[$tag_id]['tag_name'];
  }

  public function getFirstLevelContainerId() {
    return $this->tree_object->getMasterRootId();
  }
}
