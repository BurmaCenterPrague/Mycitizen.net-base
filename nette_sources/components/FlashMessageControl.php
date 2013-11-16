<?php

class FlashMessageControl extends NControl {

  public function render() {
		$this->template->setFile(dirname(__FILE__) . '/FlashMessageControl.phtml');
    	$this->template->render();
  }

}

