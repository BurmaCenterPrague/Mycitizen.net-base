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

		if (isset($this->verbose)) {
			echo date("r").': '.'Cron has started.<br/>';
		}
	
		// remember language - if run by user
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language_prev = $session->language;

		if (isset($this->verbose)) {
			echo date("r").': '.'Queuing notifications to be sent ...<br/>';
		}

		$uri = NEnvironment::getVariable("URI");
		$users_a = User::getAllUsersForCron();
		if (is_array($users_a)) {
			foreach ($users_a as $user_a) {
				$new_messages = false;
				$new_activity = false;
				$language = Language::getFlag($user_a['user_language']);
				if (empty($language)) {
					$language = 'en_US';
				}
				_t_set($language);
				$language_id = User::getUserLanguage($user_a['user_id']);
				
				$email_text = '';

				// unread private messages
				$number = User::getUnreadMessages($user_a['user_id']);
				if ($number > 0) {
					// TO DO: option for counting in other languages
					if ($number == 1) {
						$email_text .= _t("You have 1 unread message!");
					} else {
						$email_text .= _t("You have %d unread messages!", $number);
					}
					$link = $uri.'/user/messages/?language='.$language_id;
					$email_text .= "\r\n<p>".StaticModel::markup_links( _t('Read your messages in your [inbox] (%s).', $link))."</p>\r\n";
					$new_messages = true;
					if (isset($this->verbose)) {
						echo date("r").': '.'User with id '.$user_a['user_id'].' will receive a notification about unread messages.<br/>';
					}
				}

				// sending activity only for time since last logout
				$min_time = ($user_a['user_last_notification'] > strtotime($user_a['user_last_activity'])) ? $user_a['user_last_notification'] : strtotime($user_a['user_last_activity']);

				// admins and mods are notified about new items
				if ($user_a['user_access_level'] > 1) {
					$include_all_latest = 1;
				} else {
					$include_all_latest = 0;
				}
				// new activity
				$data = Activity::getActivities($user_a['user_id'], $min_time, $include_all_latest);
				if (!empty($data)) {
					if (!empty($email_text)) {
						$email_text .= "<p>&nbsp;</p>\r\n"; //'<hr><p>&nbsp;</p>';
					}
					$email_text .= '<p>'._t('Here is an overview of what has happened since your last visit:').'</p>';
					$email_text .= Activity::renderList($data, $user_a['user_id'], true);
					$link = $uri.'?language='.$language_id;
					$email_text .= "\r\n<p>".StaticModel::markup_links(_t('Find a list of your activities [on your home page] (%s).', $link))."</p>\r\n";
					$new_activity = true;
					if (isset($this->verbose)) {
						echo date("r").': '.'User with id '.$user_a['user_id'].' will receive a list of activities.<br/>';
					}
				}
					
				if ($new_messages || $new_activity) {
					$email_text = _t("Dear %s", User::getFullName($user_a['user_id'])).",<br>\r\n<br>\r\n".$email_text;
					if ($new_messages) {
						Cron::addCron(time() - 1, 1, $user_a['user_id'], $email_text, 0, 0);
					} else {
						Cron::addCron(time() - 1, 1, $user_a['user_id'], $email_text, 4, 0);
					}

					User::setUserCronQueued($user_a['user_id']);
				}

			}
		}

		if (isset($this->verbose)) {
			echo date("r").': '.' ... done<br/>';
		}

		
		if (isset($this->verbose)) {
			echo date("r").': '.'Processing queue ...<br/>';
		}

		$this->setCronTime();
		$sender_name = NEnvironment::getVariable("PROJECT_NAME");
		$uri = NEnvironment::getVariable("URI");
		
		$options = 'From: '.$sender_name.' <' . Settings::getVariable("from_email") . '>' . "\n" . "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit";
			
		$result = dibi::fetchAll("SELECT `cron_id`, `time`, `recipient_type`, `recipient_id`, `text`, `object_type`, `object_id`, `executed_time` FROM `cron` WHERE `time` < %i AND `executed_time` = 0", time());
		
		foreach ($result as $task) {

			switch ($task['recipient_type']) {
		
			case '1': // user
				$email = dibi::fetchSingle("SELECT `user_email` FROM `user` WHERE `user_id` = %i", $task['recipient_id']);

				$mail_body = $task['text'];
				$footer_note = null;

				$profile_url = $uri.'/user/edit/?user_id='.$task['recipient_id'];		
				
				switch ($task['object_type']) {
					case 0:
						$language_id = User::getUserLanguage($task['recipient_id']);
						$link = $uri.'/user/messages/?language='.$language_id;
						$footer_note = _t('Too many emails? You can change the schedule on your [profile page](%s).', $profile_url);
						$mail_subject = _t('You have unread messages at %s', $sender_name);
					break;
					case 1:
						$language_id = User::getUserLanguage($task['recipient_id']);
						$link = $uri.'/user/?user_id='.$task['object_id']. '&language='.$language_id;
						$mail_body .= "\r\n\r\n"._t('Continue to this [user] (%s).', $link)."\r\n";
						$mail_subject = sprintf(_t('Notification from %s'), $sender_name);
					break;
					case 2:
						$language_id = User::getUserLanguage($task['recipient_id']);
						$link = $uri.'/group/?group_id='.$task['object_id']. '&language='.$language_id;
						$mail_body .= "\r\n\r\n"._t('Continue to this [group] (%s).', $link)."\r\n";
						$mail_subject = sprintf(_t('Notification from %s'), $sender_name);
					break;
					case 3:
						$language_id = User::getUserLanguage($task['recipient_id']);
						$link = $uri.'/resource/?resource_id='.$task['object_id'].'&language='.$language_id;
						$mail_body .= "\r\n\r\n"._t('Continue to this [resource] (%s).', $link)."\r\n";
						$mail_subject = sprintf(_t('Notification from %s'), $sender_name);
					break;
					case 4:
						$language_id = User::getUserLanguage($task['recipient_id']);
						$link = $uri.'?language='.$language_id;
						$footer_note = _t('Too many emails? You can change the schedule on your [profile page](%s).', $profile_url);
						$mail_subject = sprintf(_t('Recent activity at %s'), $sender_name);
					break;
				}
			
				$language = Language::getFlag($language_id);
				if (empty($language)) {
					$language = 'en_US';
				}
				_t_set($language);

				$fullname = User::getFullName($task['recipient_id']);

				if (StaticModel::send_email($fullname, $email, $mail_subject, $mail_body, $footer_note)) {
					if (isset($this->verbose)) {
						echo date("r").': '.'Cron task #'.$task['cron_id'].': Email sent to '.$email.'<br/>';
					}
					dibi::query("UPDATE `cron` SET `executed_time` = %i WHERE `cron_id` = %i", time(), $task['cron_id']);
				
				} else {
					
					if (isset($this->verbose)) {
						echo date("r").': '.'Cron task #'.$task['cron_id'].': Problem sending email to '.$email.'<br/>';
					}
					dibi::query("UPDATE `cron` SET `executed_time` = 2 WHERE `cron_id` = %i", $task['cron_id']);
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
					case 0:
						$language_id = Group::getGroupLanguage($task['recipient_id']);
						$link = $uri.'/user/messages/?language='.$language_id;
					break;
					case 1:
						$language_id = Group::getGroupLanguage($task['recipient_id']);
						$link = $uri.'/user/?user_id='.$task['object_id'].'&language='.$language_id;
					break;
					case 2:
						$language_id = Group::getGroupLanguage($task['recipient_id']);
						$link = $uri.'/group/?group_id='.$task['object_id'].'&language='.$language_id;
					break;
					case 3:
						$language_id = Group::getGroupLanguage($task['recipient_id']);
						$link = $uri.'/resource/?resource_id='.$task['object_id'].'&language='.$language_id;
					break;
					default:
						$language_id = Group::getGroupLanguage($task['recipient_id']);
						$link = $uri.'?language='.$language_id;
					break;
				}

				$language = Language::getFlag($language_id);
				if (empty($language)) {
					$language = 'en_US';
				}
				_t_set($language);
				
				$mail_subject = _t('A message from your group %s at %s', $group_name, $sender_name);
				$mail_body = $task['text'];
				$mail_body .= "\r\n\r\n".sprintf(_t('You receive this message as a member of the group "%s".'),$group_name);
				$mail_body .= "\r\n\r\n"._t('Find more information [on the page of the group] (%s).', $link)."\r\n";

				// send emails				
				foreach ($users_a as $user_a) {
					$email = $user_a['user_email'];
					$fullname = User::getFullName($task['recipient_id']);
					$profile_url = $uri.'/user/edit/?user_id='.$task['recipient_id'];		
					$footer_note = "\n"._t('Too many messages? You can change the schedule on your [profile page](%s).', $profile_url);
					if (StaticModel::send_email($fullname, $email, $mail_subject, $mail_body, $footer_note)) {
						if (isset($this->verbose)) {
							echo date("r").': '.'Cron task #'.$task['cron_id'].': Email sent to '.$email.' (member of group '.$group_name.')<br/>';
						}
						dibi::query("UPDATE `cron` SET `executed_time` = %i WHERE `cron_id` = %i", time(), $task['cron_id']);
					} elseif (isset($this->verbose)) {
						echo date("r").': '.'Cron task #'.$task['cron_id'].': Problem sending email to '.$email.' (member of group '.$group_name.')<br/>';
					}
				}
								
			break;
			}
			
		}

		if (isset($this->verbose)) {
			echo date("r").': '.' ... done<br/>';
		}
		
		if (isset($this->verbose)) {
			echo date("r").': '.'Removing old combined scripts ...<br/>';
		}

		// Clean up old .js and .css cached files, will be re-created on next use.
		$files_js = glob(WWW_DIR.'/js/combined-*.js');
		$files_css = glob(WWW_DIR.'/css/combined-*.css');
		$files = array_merge($files_js, $files_css);
		if ( is_array ( $files ) && count($files) ) {
			foreach($files as $file) {
				// ... older than 7 days
				if (time() - filemtime($file) > 3600*24*7) {
					unlink($file);
					if (isset($this->verbose)) {
						echo date("r").': Deleted file '.$file;
					}
				}
			}
		}

		if (isset($this->verbose)) {
			echo date("r").': '.' ... done<br/>';
		}

		if (isset($this->verbose)) {
			echo date("r").': '.'Checking exception notification lock ...<br/>';
		}

		// Removes the file php_error.log.monitor to re-enable sending error reports by email, if lock is older than 1 week
		$file = LOG_DIRECTORY.'*.monitor';
		if (file_exists($file)) {
			if (time() - filemtime($file) > 3600*24*7) {		
				unlink($file);
				if (isset($this->verbose)) {
					echo date("r").': Removed lock';
				}
			}
		}


		if (isset($this->verbose)) {
			echo date("r").': '.' ... done<br/>';
		}

		
		// restore previous language
		_t_set($language_prev);
		
		if (isset($this->verbose)) {
			echo date("r").': '.'Cron has finished.<br/>';
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