<?php
/**
 * mycitizen.net - Open source social networking for civil society
 *
 * @version 0.2.1 beta
 *
 * @author http://mycitizen.org
 * @copyright  Copyright (c) 2013 Burma Center Prague (http://www.burma-center.org)
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */
 


class FlashMessageControl extends NControl {

  public function render() {
		$this->template->setFile(dirname(__FILE__) . '/FlashMessageControl.phtml');
    	$this->template->render();
  }

}

