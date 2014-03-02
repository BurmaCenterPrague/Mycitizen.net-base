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
 

class Cron extends BaseModel
{

	public $verbose;

	public function __construct() {
	}
	
	/**
	 *	Executes all cron jobs that are due.
	 *	@param void
	 *	@return void
	 */
	public function run() {
	
		$this->setCronTime();
		$sender_name = NEnvironment::getVariable("PROJECT_NAME");
		$uri = NEnvironment::getVariable("URI");
		
		$options = 'From: '.$sender_name.' <' . Settings::getVariable("from_email") . '>' . "\n" . "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit";
			
		$mail_subject = '=?UTF-8?B?' . base64_encode(sprintf(_t('Notification from %s'), $sender_name)) . '?=';
		
		$result = dibi::fetchAll("SELECT `cron_id`, `time`, `recipient_type`, `recipient_id`, `text`, `object_type`, `object_id`, `executed_time` FROM `cron` WHERE `time` < %i AND `executed_time` = 0", time());
		
		foreach ($result as $task) {

			switch ($task['recipient_type']) {
		
			case '1': // user
				$email = dibi::fetchSingle("SELECT `user_email` FROM `user` WHERE `user_id` = %i", $task['recipient_id']);

				switch ($task['object_type']) {
					case 0: $link = $uri.'/user/messages/?language='.User::getUserLanguage($task['recipient_id']); break;
					case 1: $link = $uri.'/user/?user_id='.$task['object_id']. '&language='.User::getUserLanguage($task['recipient_id']); break;
					case 2: $link = $uri.'/group/?group_id='.$task['object_id']. '&language='.User::getUserLanguage($task['recipient_id']); break;
					case 3: $link = $uri.'/resource/?resource_id='.$task['object_id'].'&language='. User::getUserLanguage($task['recipient_id']); break;
					default: $link = $uri.'&language='.User::getUserLanguage($task['recipient_id']); break;
				}
			
				$mail_body = $task['text'];
				$mail_body .= "\r\n\n"._t('Find more information at:')."\r\n";
				$mail_body .= $link;
				$mail_body .= "\r\nYours,\r\n".$sender_name."\r\n\r\n";
			
				if (mail($email, $mail_subject, $mail_body, $options)) {
			
					if (isset($this->verbose)) {
						echo 'Cron task #'.$task['cron_id'].': Email sent to '.$email.'<br/>';
					}

					dibi::query("UPDATE `cron` SET `executed_time` = %i WHERE `cron_id` = %i", time(), $task['cron_id']);
				
				} elseif (isset($this->verbose)) {
					echo 'Cron task #'.$task['cron_id'].': Problem sending email to '.$email.'<br/>';
				}
			break;
			case '2': // group
				// get all members
				$group = Group::create($task['recipient_id']);
				$group_name = Group::getName($task['recipient_id']);
				$filter = array(
						'enabled' => 1
					);
				$users_a = $group->getAllUsers($filter);

				switch ($task['object_type']) {
					case 0: $link = $uri.'/user/messages/?language='.Group::getGroupLanguage($task['recipient_id']); break;
					case 1: $link = $uri.'/user/?user_id='.$task['object_id'].'&language='.Group::getGroupLanguage($task['recipient_id']); break;
					case 2: $link = $uri.'/group/?group_id='.$task['object_id'].'&language='.Group::getGroupLanguage($task['recipient_id']); break;
					case 3: $link = $uri.'/resource/?resource_id='.$task['object_id'].'&language='.Group::getGroupLanguage($task['recipient_id']); break;
					default: $link = $uri.'&language='.Group::getGroupLanguage($task['recipient_id']); break;
				}

				$mail_body = $task['text'];
				$mail_body .= "\r\n\r\n".sprintf(_t('You receive this message as a member of the group "%s".'),$group_name);
				$mail_body .= "\r\n\r\n"._t('Find more information at:')."\r\n";
				$mail_body .= $link;
				$mail_body .= "\r\n\r\nYours,\r\n\r\n".$sender_name."\r\n\r\n";

				// send emails				
				foreach ($users_a as $user_a) {
				
					$email = $user_a['user_email'];

					if (mail($email, $mail_subject, $mail_body, $options)) {
			
						if (isset($this->verbose)) {
							echo 'Cron task #'.$task['cron_id'].': Email sent to '.$email.' (member of group '.$group_name.')<br/>';
						}

						dibi::query("UPDATE `cron` SET `executed_time` = %i WHERE `cron_id` = %i", time(), $task['cron_id']);
				
					} elseif (isset($this->verbose)) {
						echo 'Cron task #'.$task['cron_id'].': Problem sending email to '.$email.' (member of group '.$group_name.')<br/>';
					}
					
				}
								
			break;
			}
			
		}


		if (isset($this->verbose)) {
			echo 'Queuing notifications to be sent ...<br/>';
		}

		$users_a = User::getAllUsersForCron();
		if (is_array($users_a)) {
			foreach ($users_a as $user_a) {
				if (User::getUnreadMessages($user_a['user_id'])) {
					$email_text = sprintf(_t("Dear %s"), $user_a['user_login']). ",\n\n";
					$email_text .= _t("You have unread messages!");
//					$email_text .= "\n\n";
//					$email_text .= "("._t("You can change your notification settings in your profile.").")";
					StaticModel::addCron(time(), 1, $user_a['user_id'], $email_text, 0, 0);
					User::setUserCronSent($user_a['user_id']);
					if (isset($this->verbose)) {
						echo 'User with id '.$user_a['user_id'].' will receive a notification about unread messages.<br/>';
					}
				}
			}
		}
		
		if (isset($this->verbose)) {
			echo 'Done.<br/>';
		}

	}


	/**
	 *	schedules task for cron (sending email)
	 *	@param int $time UNIX timestamp when to execute
	 *	@param int $recipient_type user or group
	 *	@param int $recipient_id
	 *	@param string $text
	 *	@param int $object_type
	 *	@param int $object_id
	 *	@return bool
	 */
	public static function addCron($time, $recipient_type, $recipient_id, $text, $object_type, $object_id) {
	
		// max. 1 upcoming task per object and user
		self::removeCron($recipient_type, $recipient_id, $object_type, $object_id);
				
		$result = dibi::query('INSERT INTO `cron` (`time`, `recipient_type`, `recipient_id`, `text`, `object_type`, `object_id`, `executed_time`) VALUES (%i,%i,%i,%s,%i,%i,0)', $time, $recipient_type, $recipient_id, $text, $object_type, $object_id);

		return $result;
	}

	
	/**
	*	deactivates all upcoming tasks in cron for given user and object
	 *	@param int $recipient_type user or group
	 *	@param int $recipient_id
	 *	@param int $object_type
	 *	@param int $object_id
	 *	@return bool
	*/
	public static function removeCron($recipient_type, $recipient_id, $object_type, $object_id) {
	
		if (!$recipient_type || !$recipient_id) {
			// remove all crons for that object
			return dibi::query("UPDATE `cron` SET `executed_time` = '1' WHERE `object_type` = %i AND `object_id` = %i AND `time` > %i", $object_type, $object_id, time());
		} else {
			return dibi::query("UPDATE `cron` SET `executed_time` = '1' WHERE `recipient_type` = %i AND `recipient_id` = %i AND `object_type` = %i AND `object_id` = %i AND `time` > %i", $recipient_type, $recipient_id, $object_type, $object_id, time());
		}
				
	}
	
	/**
	*	updates time (UNIX timestamp) of last run in database
	 *	@param void
	 *	@return void
	*/
	private function setCronTime() {
		if (!dibi::query("UPDATE `system` SET `value` = %i WHERE `name` = 'cron_last_run'", time()))
			dibi::query("INSERT INTO `system` (`name`, `value`) VALUES ('cron_last_run', %i)", time());
	}
	
}