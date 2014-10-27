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



class ContainerTreeSelectControl extends NFormControl
{
	
	protected $template = null;
	protected $tree = null;
	protected $selected_value = null;
	protected $tag_names = null;
	protected $identifier = null;

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function __construct($caption = NULL, $identifier = NULL)
	{
		parent::__construct($caption);
		$this->tag_names[0] = array(
			'tag_id' => 0,
			'tag_name' => 'top level'
		);
		$this->identifier   = $identifier;
		
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getControl()
	{
		//Set up template
		$control  = parent::getControl();
		$template = $this->template = new NTemplate();
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}
		$this->template->setTranslator(new GettextTranslator(LOCALE_DIR . '/' . $language . '/LC_MESSAGES/messages.mo', $language));
		
		$template->registerFilter(new NLatteFilter);
		$template->registerHelper('escape', 'NTemplateHelpers::escapeHtml');
		$template->registerHelper('escapeJs', 'NTemplateHelpers::escapeJs');
		$template->registerHelper('escapeCss', 'NTemplateHelpers::escapeCss');
		$template->setFile(dirname(__FILE__) . '/ContainerTreeSelectControl.phtml');
		$template->control      = $this;
		$template->control_name = $this->getName();
		$template->name         = $this->identifier;
		$this->tree             = Tag::getTreeArray();
		$template->tree_array   = $this->tree;
		//Fill the array indicating whether the current user can select the folder in the selector
		$object_type_name_array = array();
		foreach ($template->tree_array as $node) {
			$this->tag_names[$node['tag_id']] = $node;
		}
		//Add the first level (master root) which is not in tree_array
		$template->object_type_name_array = $this->tag_names;
		
		//Get complete path to the active node (without the active node itself)
		$active_node_path = array();
		$node_id          = $this->getValue();
		$node_parent_id   = $this->tag_names[$node_id]['tag_id'];
		while ($node_id != $node_parent_id) {
			$node_id        = $this->tag_names[$node_id]['tag-child_id'];
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
		//return null;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function setValue($value)
	{
		$this->selected_value = $this->getPathString($value);
		parent::setValue($value);
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getPathString($tag_id)
	{
		if (isset($this->tag_names[$tag_id]['tag_name'])) {
			return $this->tag_names[$tag_id]['tag_name'];
		} else {
			return '';
		}
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getFirstLevelContainerId()
	{
		return $this->tree_object->getMasterRootId();
	}
}
