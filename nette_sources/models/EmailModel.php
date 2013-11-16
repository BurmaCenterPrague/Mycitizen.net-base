<?php
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
				mail($to,$mail_subject,$mail_body,$headers);
				$mail_count = 0;
				$to = "";
			}
			$mail_count++;
		}
	}
}
