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
 

class EmailModel extends BaseModel {
	public static function sendEmail($emails,$mail_subject,$mail_body,$options) {
		$headers = "";
		foreach($options as $key=>$header) {
			$headers .= $key.":".$header."\r\n";
		}

		$mail_count = 0;
		$to = "";
		foreach($emails as $key=>$email) {
			if($to != "") {
				$to .=",";
			}
			$to .= $email;
			if($mail_count == 100) {
				if (!mail($to,$mail_subject,$mail_body,$headers)) return false;
				$mail_count = 0;
				$to = "";
			}
			$mail_count++;
		}
		return true;
	}
}
