<?php
class StaticModel extends BaseModel {
	const SYSTEM_MESSAGE_WARNING_USER = 1;
	const SYSTEM_MESSAGE_FRIENDSHIPOFFER = 2;
	const SYSTEM_MESSAGE_FRIENDSHIPACCEPTED = 3;
	const SYSTEM_MESSAGE_FRIENDSHIPREJECTED = 4;
	const SYSTEM_MESSAGE_WARNING_GROUP = 5;
	const SYSTEM_MESSAGE_WARNING_RESOURCE = 6;
	const SYSTEM_MESSAGE_FRIENDSHIPTRERMINATED = 7;

	 
   public static function ipIsRegistered($type_id,$object_id,$ip) {
      $result = dibi::fetchSingle("SELECT `ip_address` FROM `visits` WHERE `type_id` = %i AND `object_id` = %i AND `ip_address` = %s",$type_id,$object_id,trim($ip));
		if(!empty($result)) {
			return false;
		} else {
			dibi::query("INSERT INTO `visits`",array('type_id'=>$type_id,'object_id'=>$object_id,'ip_address'=>$ip));
			return true;
		}
      return $languages;
   }
	
	public static function sendSystemMessage($message_type,$from,$to,$message = null) {
		$sender = User::create($from);
		if(!empty($sender)) {
			$sender_data = $sender->getUserData();
		} else {
			return false;
		}
		$recipient = User::create($to);
		if(!empty($recipient)) {
			$recipient_data = $recipient->getUserData();
		} else {
			return false;
		}
		
		switch($message_type) {
			case self::SYSTEM_MESSAGE_FRIENDSHIPOFFER:
				$message_subject = sprintf(_("User %s requested your friendship."), $sender_data['user_login']);
				$message_text = _("Friendship request");
				break;
			case self::SYSTEM_MESSAGE_FRIENDSHIPACCEPTED:
				$message_subject = sprintf(_("User %s accepted your friendship."), $sender_data['user_login']);
            	$message_text = _("Friendship accepted");
				break;
			case self::SYSTEM_MESSAGE_FRIENDSHIPREJECTED:
				$message_subject = sprintf(_("User %s rejected your friendship."), $sender_data['user_login']);
	            $message_text = _("Friendship request rejected");
				break;
			case self::SYSTEM_MESSAGE_FRIENDSHIPTRERMINATED:
				$message_subject = sprintf(_("User %s canceled your friendship."), $sender_data['user_login']);
	            $message_text = _("Friendship canceled");			
				break;
			case self::SYSTEM_MESSAGE_WARNING_USER:
				$message_subject = _("System message: Warning!!!");
	            $message_text = $message;
				break;
			case self::SYSTEM_MESSAGE_WARNING_GROUP:
				$message_subject = _("System message: Warning!!!");
				$message_text = $message;
         	  	break;
			case self::SYSTEM_MESSAGE_WARNING_RESOURCE:
				$message_subject = _("System message: Warning!!!");
         		$message_text = $message;
	            break;
			default:
				return false;
		}

    	$resource = Resource::create();
      
		$data = array();
      
		$data['resource_author'] = $sender->getUserId();
    	$data['resource_type'] = 9;
		$data['resource_visibility_level'] = 3;
    	$data['resource_name'] = $message_subject;
    	$data['resource_data'] = json_encode(array('message_text'=>$message_text,'message_type'=>$message_type));
      
		$resource->setResourceData($data);
		$resource->save();

      //$resource->updateUser($from,array('resource_user_group_access_level'=>1));
      $resource->updateUser($recipient->getUserId(),array('resource_user_group_access_level'=>1));

	}

	public static function isSpamEmail($email) {
		$contents = file_get_contents("http://www.stopforumspam.com/api?email=".$email."&f=json");
		$json = json_decode($contents,true);
		if(isset($json['success']) && $json['success']) {
			if($json['email']['appears']) {
				return true;
			}
		}
		return false;
	}

	public static function isSpamIP($ip) {
		$contents = file_get_contents("http://www.stopforumspam.com/api?ip=".$ip."&f=json");
		$json = json_decode($contents,true);
		if(isset($json['success']) && $json['success']) {
			if($json['ip']['appears']) {
				return true;
			}
		}
		return false;
	}

	public static function validEmail($email) {
      $isValid = true;
      $atIndex = strrpos($email, "@");
      if (is_bool($atIndex) && !$atIndex) {
         $isValid = false;
      } else {
         $domain = substr($email, $atIndex+1);
         $local = substr($email, 0, $atIndex);
         $localLen = strlen($local);
         $domainLen = strlen($domain);
         if ($localLen < 1 || $localLen > 64) {
            // local part length exceeded
            $isValid = false;
         } else if ($domainLen < 1 || $domainLen > 255) {
            // domain part length exceeded
            $isValid = false;
         } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
            // local part starts or ends with '.'
            $isValid = false;
         } else if (preg_match('/\\.\\./', $local)) {
            // local part has two consecutive dots
            $isValid = false;
         } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
            // character not valid in domain part
            $isValid = false;
         } else if (preg_match('/\\.\\./', $domain)) {
            // domain part has two consecutive dots
            $isValid = false;
         } else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',str_replace("\\\\","",$local))) {
            // character not valid in local part unless 
            // local part is quoted
            if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local))) {
               $isValid = false;
            }
         }
         if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
            // domain not found in DNS
            $isValid = false;
         }
      }
      return $isValid;
   }

}

