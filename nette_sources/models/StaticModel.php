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
 

class StaticModel extends BaseModel {
	const SYSTEM_MESSAGE_WARNING_USER = 1;
	const SYSTEM_MESSAGE_FRIENDSHIPOFFER = 2;
	const SYSTEM_MESSAGE_FRIENDSHIPACCEPTED = 3;
	const SYSTEM_MESSAGE_FRIENDSHIPREJECTED = 4;
	const SYSTEM_MESSAGE_WARNING_GROUP = 5;
	const SYSTEM_MESSAGE_WARNING_RESOURCE = 6;
	const SYSTEM_MESSAGE_FRIENDSHIPTRERMINATED = 7;


   /**
    *	@todo ### Description
    *	@param
    *	@return
    */
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


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function sendSystemMessage($message_type, $from, $to, $message = null, $object_type = null, $object_id = null) {
		
		$send_email = false;
		
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
		
		// remember language
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language_prev = $session->language;

		$language = Language::getFlag($recipient_data['user_language']);
		if (empty($language)) {
			$language = 'en_US';
		}
		_t_set($language);
		
		$name = $recipient->getUserLogin($recipient->getUserId());
		
		$data = array();

		switch($message_type) {
			case self::SYSTEM_MESSAGE_FRIENDSHIPOFFER:
				$message_subject = sprintf(_t("User %s requested your friendship."), $sender_data['user_login']);
				$message_text = _t("Friendship request");
				$email_text = $message_subject;
				$data['resource_type'] = 10;
				break;
			case self::SYSTEM_MESSAGE_FRIENDSHIPACCEPTED:
				$message_subject = sprintf(_t("User %s accepted your friendship."), $sender_data['user_login']);
            	$message_text = _t("Friendship accepted");
            	$email_text = $message_subject;
            	$data['resource_type'] = 9;
				break;
			case self::SYSTEM_MESSAGE_FRIENDSHIPREJECTED:
				$message_subject = sprintf(_t("User %s rejected your friendship."), $sender_data['user_login']);
	            $message_text = _t("Friendship request rejected");
	            $email_text = $message_subject;
	            $data['resource_type'] = 9;
				break;
			case self::SYSTEM_MESSAGE_FRIENDSHIPTRERMINATED:
				$message_subject = sprintf(_t("User %s canceled your friendship."), $sender_data['user_login']);
	            $message_text = _t("Friendship canceled");
	            $email_text = $message_subject;
	            $data['resource_type'] = 9;
				break;
			case self::SYSTEM_MESSAGE_WARNING_USER:
				$message_subject = _t("System message: You've received a warning.");
	            $message_text = $message;
	            $email_text = $message_subject."\n\r".$message_text;
	            $data['resource_type'] = 9;
	            $send_email = true;
				break;
			case self::SYSTEM_MESSAGE_WARNING_GROUP:
				$message_subject = _t("System message: You've received a warning about your group.");
				$message_text = $message;
				$email_text = $message_subject."\n\r".$message_text;
				$data['resource_type'] = 9;
	            $send_email = true;
         	  	break;
			case self::SYSTEM_MESSAGE_WARNING_RESOURCE:
				$message_subject = _t("System message:  You've received a warning about your resource.");
         		$message_text = $message;
         		$email_text = $message_subject."\n\r".$message_text;
         		$data['resource_type'] = 9;
	            $send_email = true;
	            break;
			default:
				return false;
		}

    	$resource = Resource::create();
      
		$data['resource_author'] = $sender->getUserId();
		$data['resource_visibility_level'] = 3;
    	$data['resource_name'] = $message_subject;
    	$data['resource_data'] = json_encode(array('message_text'=>$message_text,'message_type'=>$message_type));
      
		$resource->setResourceData($data);
		$resource->save();

		if (!isset($object_id) || !isset($object_type)) {
			if (isset($sender_data['user_login'])) {
				$object_type = 1; $object_id = $from;
			} else {
				$object_type = 0; $object_id = 0;
			}
		}
		
		if ($send_email) {
			$email_text = sprintf(_t("Dear %s"), $name).",\n\r\n\r".$email_text;
			self::addCron(time()+60, 1, $to, $email_text, $object_type, $object_id);
		}
		
    	$resource->updateUser($recipient->getUserId(),array('resource_user_group_access_level'=>1));

		// return to previous language
		_t_set($language_prev);
	}


	/**
	*	Compatibility with API_Base which receives from the mobile application only the email address.
	*
	*/
	public static function isSpamEmail($email) {
		return self::isSpamSFS($email);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function isSpamSFS($email,$ip = '') {
	
		$check_stop_forum_spam = NEnvironment::getVariable("CHECK_STOP_FORUM_SPAM");
		if (!$check_stop_forum_spam) return true;
		
		if (empty($ip)) {
			$contents = file_get_contents("http://www.stopforumspam.com/api?email=".$email."&f=json");
		} else {
			$contents = file_get_contents("http://www.stopforumspam.com/api?email=".$email."&ip=".$ip."&f=json");
		}
		$json = json_decode($contents,true);
		if (isset($json['success']) && $json['success']) {
			if ((isset($json['email']['appears']) && $json['email']['appears']) || (isset($json['ip']['appears']) && $json['ip']['appears'])) {
				return true;
			}
		}
		return false;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
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


/** see class Settings
	public static function getSetting($variable_name) {
	
		return dibi::fetchSingle("SELECT `variable_value` FROM `settings` WHERE `variable_name` = %s", $variable_name);

	}
*/


}
