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
 


class FlashMessageControl extends NControl {


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
  public function render() {
		$this->template->setFile(dirname(__FILE__) . '/FlashMessageControl.phtml');
    	$this->template->flash_message_time = (int) NEnvironment::getVariable("FLASH_MESSAGE_TIME");
    	if ($this->template->flash_message_time<1) $this->template->flash_message_time = 3;
    	$this->template->render();
  }

}

