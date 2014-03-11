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
 

class MapContainer extends NFormControl {
	protected $defaults = array();


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function __construct($name, $label) {
		parent::__construct($name, $label);
		$this->monitor('NForm');
	}


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
   public function getControl() {
      $control = parent::getControl();

      $template = new NTemplate();
		$session = NEnvironment::getSession()->getNamespace("GLOBAL");
      $language = $session->language;
      if(empty($language)) {
         $session->language = 'en_US';
         $language = $session->language;
      }
      $template->setTranslator(new GettextTranslator('../locale/'.$language.'/LC_MESSAGES/messages.mo', $language));

		$template->registerFilter(new NLatteFilter);
        $template->registerHelper('escape', 'NTemplateHelpers::escapeHtml');
        $template->registerHelper('escapeJs', 'NTemplateHelpers::escapeJs');
        $template->registerHelper('escapeCss', 'NTemplateHelpers::escapeCss');

      $template->setFile(dirname(__FILE__) . '/MapContainer.phtml');
		$template->data = array();

      $template->name = $control->name;
//      var_dump($this->defaults);die('2');
    	if ($this->defaults!='"null"') {
      		$template->defaults = $this->defaults;
      	}
		$template->default_latitude = Settings::getVariable('gps_default_latitude');
    	$template->default_longitude = Settings::getVariable('gps_default_longitude');
    	$template->baseUri = NEnvironment::getVariable("URI") . '/';

		$template->type = "radius";	
      ob_start();
      $template->render();
      $output = ob_get_contents();
      ob_end_clean();
      return NHtml::el()->setHtml($output);

   }


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function setValue($values) {
   		$this->value = $values;
    	$this->defaults = json_encode($values);
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getValue() {
		$data = json_decode($this->value['mapdata'],true);
		return $data;
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

	}
}