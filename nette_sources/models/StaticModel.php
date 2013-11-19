<?php
/**
 * mycitizen.net - Open source social networking for civil society
 *
 * @version 0.2 beta
 *
 * @author http://mycitizen.org
 *
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */
 

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

		if (isset($sender_data['user_login'])) {
			$object_type = 1; $object_id = $sender_data['user_id'];
		} else {
			$object_type = 0; $object_id = 0;
		}
				
		StaticModel::addCron(time(), $recipient->getUserId(), $message_subject, $object_type, $object_id);

    	$resource->updateUser($recipient->getUserId(),array('resource_user_group_access_level'=>1));

	}

	public static function isSpamSFS($email,$ip) {
		$contents = file_get_contents("http://www.stopforumspam.com/api?email=".$email."&ip=".$ip."&f=json");
		$json = json_decode($contents,true);
		if(isset($json['success']) && $json['success']) {
			if($json['email']['appears'] || $json['ip']['appears']) {
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

	/**
	*	schedules task for cron
	*/
	public static function addCron($time, $recipient_id, $text, $object_type, $object_id) {
	
		// max. 1 upcoming task per object and user
		StaticModel::removeCron($recipient_id, $object_type, $object_id);
				
		$result = dibi::query('INSERT INTO `cron` (`time`, `recipient_id`, `text`, `object_type`, `object_id`, `executed_time`) VALUES (%i,%i,%s,%i,%i,0)', $time, $recipient_id, $text, $object_type, $object_id);

		return $result;
	}

	
	/**
	*	deactivates all upcoming tasks in cron for given user and object
	*/
	public static function removeCron($recipient_id, $object_type, $object_id) {
	
		dibi::query("UPDATE `cron` SET `executed_time` = '1' WHERE `recipient_id` = %i AND `object_type` = %i AND `object_id` = %i AND `time` > %i", $recipient_id, $object_type, $object_id, time());
				
	}

	/**
	*	setting from admin backend
	*/   
	public static function getSetting($variable_name) {
	
		return dibi::fetchSingle("SELECT `variable_value` FROM `settings` WHERE `variable_name` = %s", $variable_name);

	}

}
