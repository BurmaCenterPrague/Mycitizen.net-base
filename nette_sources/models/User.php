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
 

class User extends BaseModel implements IIdentity
{
	const ADMINISTRATOR = 3;
	const MODERATOR = 2;
	const USER = 1;
	
	private $user_data;
	private $numeric_id;


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function create($user_id = null)
	{
		return new User($user_id);
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function __construct($user_id)
	{
		if (!empty($user_id)) {
			$result = dibi::fetchAll("SELECT `user_id`,`user_password`,`user_name`,`user_surname`,`user_login`,`user_description`,`user_email`,`user_phone`,`user_phone_imei`,`user_position_x`,`user_position_y`,`user_language`,`user_visibility_level`,`user_access_level`,`user_status`,`user_registration_confirmed`,`user_creation_rights`,`user_send_notifications`,`user_url`,`user_portrait` FROM `user` WHERE `user_id` = %i", $user_id);
			if (sizeof($result) > 2) {
				return false;
				throw new Exception("More than one user with the same id found.");
			}
			if (sizeof($result) < 1) {
				return false;
				throw new Exception("Specified user not found.");
			}
			$data_array       = $result[0]->toArray();
			$this->numeric_id = $data_array['user_id'];
			unset($data_array['user_id']);
			$this->user_data = $data_array;
		}
		return true;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function setUserData($data)
	{
		foreach ($data as $key => $value) {
			$this->user_data[$key] = $value;
		}
    	// User hash is not part of data so that it is not exposed through API. It should not even be displayed to other users with permission to view, e.g. friends, who could use it to change the password.
		if (isset($data['user_hash'])) {
			$this->user_data['user_hash'] = $data['user_hash'];
		}

	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getUserData()
	{
		$data = $this->user_data;
		unset($data['user_password']);
		if (!empty($data)) {
			$data['user_email'] = dibi::fetchSingle("SELECT `user_email` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
			$tags = $this->getTags();
			$data['tags'] = array();
			foreach($tags as $tagO) {
				$tag_data = $tagO->getTagData();
				$tag_data['id'] = $tagO->getTagId();
				$data['tags'][] = $tag_data;
			}

		}
		return $data;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getAvatar()
	{
		$portrait = dibi::fetchSingle("SELECT `user_portrait` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		return $portrait;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function removeAvatar()
	{
		dibi::query("UPDATE `user` SET `user_portrait` = NULL WHERE `user_id` = %i", $this->numeric_id);
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function userHasIcon()
	{
		$result = dibi::fetchSingle("SELECT `user_icon` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		if (!empty($result)) {
			return true;
		}
		return false;
	}


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function getIcon()
	{
		$portrait = dibi::fetchSingle("SELECT `user_icon` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		return $portrait;
	}


/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function getLargeIcon()
	{
		$portrait = dibi::fetchSingle("SELECT `user_largeicon` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		return $portrait;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function removeIcons()
	{
		dibi::query("UPDATE `user` SET `user_icon` = NULL WHERE `user_id` = %i", $this->numeric_id);
		dibi::query("UPDATE `user` SET `user_largeicon` = NULL WHERE `user_id` = %i", $this->numeric_id);
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function isActive()
	{
		$result = dibi::fetchSingle("SELECT `user_status` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		if (!empty($result)) {
			if ($result == 1) {
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
	public function isConfirmed()
	{
		$result = dibi::fetchSingle("SELECT `user_registration_confirmed` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		if (!empty($result)) {
			if ($result == 1) {
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
	public function save()
	{
		try {
			dibi::begin();
			if (!empty($this->user_data)) {
				unset($this->user_data['avatar']);
				if (empty($this->numeric_id)) {
					dibi::query('INSERT INTO `user`', $this->user_data);
					$this->numeric_id = dibi::insertId();
				} else {
					dibi::query('UPDATE `user` SET ', $this->user_data, 'WHERE `user_id` = %i', $this->numeric_id);
				}
			}
		}
		catch (Exception $e) {
			dibi::rollback();
			throw $e;
		}
		dibi::commit();
		return true;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function delete($user_id)
	{
		dibi::query("DELETE FROM `user` WHERE `user_id` = %i", $user_id);
	}


	/**
	 *	Creates a salted hash of a cleartext password
	 *	@param string $password
	 *	@return string
	 */
	public static function encodePassword($password)
	{
		if (strlen($password)>128) return false;

		require(LIBS_DIR.'/Phpass/PasswordHash.php');
		$hasher = new PasswordHash(8, false);
		return $hasher->HashPassword($password);
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getUserId()
	{
		return $this->numeric_id;
	}
	
	/**
	*	required by Nette
	*/

/**
 *	something Nette-specific
 *	@param
 *	@return
*/
	public function getRoles()
	{
		return array(
			'customer'
		);
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getVisibilityLevel()
	{
		return $this->user_data['user_visibility_level'];
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getAccessLevel()
	{
		$result = dibi::fetchSingle("SELECT `user_access_level` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		if (!empty($result)) {
			return $result;
		}
		return 0;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getAccessLevelFromLogin($login)
	{
		$result = dibi::fetchSingle("SELECT `user_access_level` FROM `user` WHERE `user_login` = %s", $login);
		if (!empty($result)) {
			return $result;
		}
		return 0;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function insertTag($tag_id)
	{
		$registered_tags = $this->getTags();
		if (!isset($registered_tags[$tag_id])) {
			dibi::query('INSERT INTO `user_tag` (`tag_id`,`user_id`) VALUES (%i,%i)', $tag_id, $this->numeric_id);
		}
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function removeTag($tag_id)
	{
		$registered_tags = $this->getTags();
		if (isset($registered_tags[$tag_id])) {
			dibi::query('DELETE FROM `user_tag` WHERE `tag_id` = %i AND `user_id` = %i', $tag_id, $this->numeric_id);
		}
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getTags()
	{
//		$result = dibi::fetchAll("SELECT ut.`tag_id`,t.`tag_name` FROM `user_tag` ut LEFT JOIN `tag` t ON (t.`tag_id` = ut.`tag_id`) WHERE `user_id` = %i ORDER BY t.`tag_name` ASC", $this->numeric_id);
		$result = dibi::fetchAll("SELECT ut.`tag_id`,t.`tag_name` FROM `user_tag` ut, `tag` t WHERE t.`tag_id` = ut.`tag_id` AND `user_id` = %i ORDER BY t.`tag_name` ASC", $this->numeric_id);
		$array  = array();
		foreach ($result as $row) {
			$data                   = $row->toArray();
			$array[$data['tag_id']] = Tag::create($data['tag_id']);
		}
		
		return $array;
	}
	
	/**
	 *		Groups tags according to their parent and sorts them by parent, then child
	 *	@param
	 *	@return
	*/
	public function groupSortTags($tags) {
	
		uasort($tags, function($a,$b){
		
			$path_a = $a->getPath();
			$path_b = $b->getPath();
		
			if (isset($path_a[1])) $child_a=true; else $child_a=false;
			if (isset($path_b[1])) $child_b=true; else $child_b=false;
		
			if (!$child_a && !$child_b) {			
				return strnatcasecmp($path_a[0], $path_b[0]); // both have no child -> compare parents
			}
		
			$cmp = strnatcasecmp($path_a[0], $path_b[0]);
		
			if ($cmp != 0) {
				return $cmp; // different parents
				
			} else {
			
				if ($child_a && $child_b) {
					return strnatcasecmp($path_a[1], $path_b[1]); // both have a child -> compare children
				}
			
				if ($child_a && !$child_b) {
					return 1; // empty child always before any child
				}

				if (!$child_a && $child_b) {
					return -1; // empty child always before any child
				}
		
			}
	
			return 0; // should not happen
		});
		
		return $tags;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return string
	 */
	public static function generateHash()
	{
		$length = 8;
		$chars  = "abcdefghijkmnopqrstuvwxyz023456789";
		srand((double) microtime() * 1000000);
		$i    = 0;
		$pass = '';
		while ($i < $length) {
			$num  = rand() % 33;
			$tmp  = substr($chars, $num, 1);
			$pass = $pass . $tmp;
			$i++;
		}
		return $pass;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function sendConfirmationEmail()
	{
		$hash  = $this->user_data['user_hash'];
		$email = $this->user_data['user_email'];
		$name = $this->user_data['user_login'];
		$language = $this->user_data['user_language'];
		$id = $this->numeric_id;
		
		$answer = Settings::getVariable('signup_answer');
		if ($answer && $this->getCaptchaOk() == false) {
			$link  = NEnvironment::getVariable("URI") . "/widget/mobilecaptcha/?user_id=" . $id . "&control_key=" . $hash . "&language=" . $language;		
		} else {
			$link  = NEnvironment::getVariable("URI") . "/user/confirm/?user_id=" . $id . "&control_key=" . $hash . "&language=" . $language;
		}
		$body  = sprintf(_t("Hello %s,\n\nThank you for signing up at Mycitizen.net!\nTo finish your registration click [here] (%s), or copy the following link to your browser:\n\n%s"), $name, $link, $link );
		
/*
		$headers = 'From: Mycitizen.net <' . Settings::getVariable("from_email") . '>' . "\n" . "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit";
		
		return mail($email, '=?UTF-8?B?' . base64_encode(_t('Finish your registration at Mycitizen.net')) . '?=', $body, $headers);
*/
		return StaticModel::send_email($name, $email, _t('Finish your registration at Mycitizen.net'), $body);
	}


	/**
	 *	checks if user has properly filled the captcha during registration
	 *	@param void
	 *	@return boolean
	 */
	public function getCaptchaOk()
	{
		$result = dibi::fetchSingle("SELECT `user_captcha_ok` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		if ($result == 1) {
			return true;
		}
		return false;	
	}


	/**
	 *	sets captcha status
	 *	@param boolean
	 *	@return boolean
	 */
	public function setCaptchaOk($new_status)
	{
		$new_status_int = $new_status ? 1 : 0;
		
		$result = dibi::query("UPDATE `user` SET `user_captcha_ok` = %i  WHERE `user_id` = %i", $new_status_int, $this->numeric_id);

		return $result;	
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return object
	 */
	public static function getEmailOwner($email)
	{
		$result = dibi::fetchSingle("SELECT `user_id` FROM `user` WHERE `user_email` = %s", $email);
		if (!empty($result)) {
			return User::create($result);
		}
		return null;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function firstLogin()
	{
		$result = dibi::fetchSingle("SELECT `user_first_login` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		if ($result == 1) {
			return false;
		}
		return true;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function registerFirstLogin()
	{
		$result = dibi::query("UPDATE `user` SET `user_first_login` = '1' WHERE `user_id` = %i", $this->numeric_id);
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function sendLostpasswordEmail()
	{
		$hash = self::generateHash();
		$this->user_data['user_hash'] = $hash;
		$this->save();
		$name =  $this->user_data['user_login'];
		$email = $this->user_data['user_email'];
		$id    = $this->numeric_id;
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = Language::getId($session->language);
		

		$link  = NEnvironment::getVariable("URI") . "/user/changelostpassword/?user_id=" . $id . "&control_key=" . $hash . "&language=" . $language;
		
		$body  = sprintf(_t("Hello %s,\n\nYou have requested a password change on Mycitizen.net.\nTo finish your request click [here] (%s), or copy the following link to your browser:\n\n%s"), $name, $link, $link);
		
/*
		$headers = 'From: Mycitizen.net <' . Settings::getVariable("reply_email") . '>' . "\n" . "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit";
		mail($email, '=?UTF-8?B?' . base64_encode(_t('Password change on Mycitizen.net')) . '?=', $body, $headers);
		return $body;
*/	
		return StaticModel::send_email($name, $email, _t('Password change on Mycitizen.net'), $body);

	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function finishPasswordchange($user_id, $control_key, $password)
	{
		$result = dibi::fetchSingle("SELECT `user_login` FROM `user` WHERE `user_id` = %i AND `user_hash` = %s", $user_id, $control_key);
		if (!empty($result)) {
			dibi::query("UPDATE `user` SET `user_password` = %s WHERE `user_id` = %i", self::encodePassword($password), $user_id);
			return true;
		}
		return false;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function changePassword($user_id, $password)
	{
		return dibi::query("UPDATE `user` SET `user_password` = %s WHERE `user_id` = %i", self::encodePassword($password), $user_id);

	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function sendEmailchangeEmail($email = null)
	{
		$hash = self::generateHash();
		
		$this->user_data['user_hash'] = $hash;
		$this->save();
		$name =  $this->user_data['user_login'];
		if (!isset($email)) $email = $this->user_data['user_email'];
		$id    = $this->numeric_id;
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = Language::getId($session->language);
		$link  = "http://" . $_SERVER['HTTP_HOST'] . "/user/emailchange/?user_id=" . $id . "&control_key=" . $hash . "&language=" . $language;
		$body  = sprintf(_t("Hello %s,\n\nYou have requested an email change on Mycitizen.net.\nTo finish your request click [here] (%s), or copy the following link to your browser:\n\n%s"), $name, $link, $link );
		
		StaticModel::send_email($name, $email, _t('Email change on Mycitizen.net'), $body);
		
		if ($email != $this->user_data['user_email']) {
			$support_url = NEnvironment::getVariable("SUPPORT_URL");
			$body  = sprintf(_t("Hello %s,\n\nSomebody has requested an email change on Mycitizen.net. The new email will be: %s\n\nIf you think that this is wrong, please [contact the support] (%s)."), $name, $email, $support_url ) . "\n\n " . $link;

			StaticModel::send_email($name, $this->user_data['user_email'], _t('Email change on Mycitizen.net'), $body);
		}
		return $body;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function finishEmailchange($user_id, $control_key)
	{
		$result = dibi::fetchSingle("SELECT `user_email_new` FROM `user` WHERE `user_id` = %i AND `user_hash` = %s", $user_id, $control_key);
		if (!empty($result) && $result != "") {
			dibi::query("UPDATE `user` SET `user_email` = `user_email_new`, `user_email_new` = '' WHERE `user_id` = %i", $user_id);
			return true;
		}
		return false;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function finishEmailchangeAdmin($user_id, $user_email)
	{
		dibi::query("UPDATE `user` SET `user_email` = %s WHERE `user_id` = %i", $user_email, $user_id);
		return true;
	}


	/**
	 *	Activate user and set registration as confirmed if email was confirmed through correct hash-.
	 *	@param int $user_id
	 *	@param string $control_key hash from field user_hash
	 *	@return bool
	 */
	public static function finishRegistration($user_id, $control_key)
	{
		$result = dibi::fetchSingle("SELECT `user_login` FROM `user` WHERE `user_id` = %i AND `user_registration_confirmed` = '0' AND `user_hash` = %s", $user_id, $control_key);
		if (!empty($result)) {
			dibi::query("UPDATE `user` SET `user_status` = '1',`user_registration_confirmed` = '1' WHERE `user_id` = %i", $user_id);
			return true;
		}
		return false;
	}


	/**
	 *	Activate user and set registration as confirmed if email was supplied through external API.
	 *	@param void
	 *	@return int
	 */
	public function finishExternalRegistration()
	{
		return dibi::query("UPDATE `user` SET `user_status` = '1',`user_registration_confirmed` = '1' WHERE `user_id` = %i", $this->numeric_id);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getType()
	{
		return 1;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function areFriends($user_1_id, $user_2_id)
	{
		$user = User::create($user_1_id);
		if (!empty($user)) {
			if ($user->friendsStatus($user_2_id) == 2 && $user->reverseFriendsStatus($user_2_id) == 2) {
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
	public function friendshipIsRegistered($user_id)
	{
		$result = dibi::fetchSingle("SELECT `friend_id` FROM `user_friend` WHERE `user_id` = %i AND `friend_id` = %i", $this->numeric_id, $user_id);
		if (!empty($result)) {
			return true;
			//return $result;
		}
		return false;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function isFriendOf($user_id)
	{
		if ($this->friendsStatus($user_id) == 2 && $this->reverseFriendsStatus($user_id) ==2) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function friendsStatus($user_id)
	{
		$result = dibi::fetchSingle("SELECT `user_friend_status` FROM `user_friend` WHERE (`user_id` = %i AND `friend_id` = %i) ", $this->numeric_id, $user_id);
		if (!empty($result)) {
			return $result;
		}
		return 0;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function reverseFriendsStatus($user_id)
	{
		$result = dibi::fetchSingle("SELECT `user_friend_status` FROM `user_friend` WHERE (`user_id` = %i AND `friend_id` = %i) ", $user_id, $this->numeric_id);
		if (!empty($result)) {
			return $result;
		}
		return 0;
	}
	
	/**
	*	make a new friendship request or confirm a friendship
	*	status:
	*	1:	requested
	*	2:	accepted
	*	3:	rejected/blocked
	*/
	public function updateFriend($user_id, $data)
	{
		try {
			dibi::begin();
			if (!$this->friendshipIsRegistered($user_id)) {
				$data['friend_id']          = $user_id;
				$data['user_id']            = $this->numeric_id;
				$data['user_friend_status'] = 1;
				dibi::query('INSERT INTO `user_friend`', $data);
				
				$data['friend_id']          = $this->numeric_id;
				$data['user_id']            = $user_id;
				$data['user_friend_status'] = 0;
				dibi::query('INSERT INTO `user_friend`', $data);
				StaticModel::sendSystemMessage(2, $this->numeric_id, $user_id);
				Activity::addActivity(Activity::FRIENDSHIP_REQUEST, $this->numeric_id, 1, $user_id);
			} else {
			
				switch ($this->reverseFriendsStatus($user_id)) {
				case 0:	// request friendship after earlier request + revocation
					$data['user_friend_status'] = 1;
					dibi::query('UPDATE `user_friend` SET ', $data, 'WHERE `friend_id` = %i AND `user_id` = %i', $user_id, $this->numeric_id);
					// don't send a message in this case, otherwise one could spam by alternating requests and revocation
					break;
				case 1:	// responding to a request
					$data['user_friend_status'] = 2;
					dibi::query('UPDATE `user_friend` SET ', $data, 'WHERE `friend_id` = %i AND `user_id` = %i', $user_id, $this->numeric_id);
					dibi::query('UPDATE `user_friend` SET ', $data, 'WHERE `friend_id` = %i AND `user_id` = %i', $this->numeric_id, $user_id);
					StaticModel::sendSystemMessage(3, $this->numeric_id, $user_id);
					Activity::addActivity(Activity::FRIENDSHIP_YES, $this->numeric_id, 1, $user_id);
					break;
				case 3:	// other person has rejected/blocked
					return;
					break;
				}
			}
		}
		catch (Exception $e) {
			dibi::rollback();
			throw $e;
		}
		dibi::commit();
	}

	/**
	 *	reject a friendship request
	 *
	 *	status:
	 *	1:	requested
	 *	2:	accepted
	 *	3:	rejected/blocked
	 *	@param
	 *	@return
	 */
	public function removeFriend($user_id)
	{
		try {
			dibi::begin();
			if ($this->friendshipIsRegistered($user_id)) {
				switch ($this->reverseFriendsStatus($user_id)) {
				case 0:	// other person has done nothing, own request -> revoke request
					$data['user_friend_status'] = 0;
					dibi::query('UPDATE `user_friend` SET ', $data, 'WHERE `friend_id` = %i AND `user_id` = %i', $user_id, $this->numeric_id);
					break;
				case 1:	// other person requested friendship -> reject/block
					$data['user_friend_status'] = 3;
					dibi::query('UPDATE `user_friend` SET ', $data, 'WHERE `friend_id` = %i AND `user_id` = %i', $user_id, $this->numeric_id);
					Activity::addActivity(Activity::FRIENDSHIP_NO, $this->numeric_id, 1, $user_id);
					StaticModel::sendSystemMessage(4, $this->numeric_id, $user_id);
					break;
				case 2: // friendship exists -> terminate
					$data['user_friend_status'] = 3;
					dibi::query('UPDATE `user_friend` SET ', $data, 'WHERE `friend_id` = %i AND `user_id` = %i', $user_id, $this->numeric_id);
					$data['user_friend_status'] = 0;
					dibi::query('UPDATE `user_friend` SET ', $data, 'WHERE `friend_id` = %i AND `user_id` = %i', $this->numeric_id, $user_id);
					Activity::addActivity(Activity::FRIENDSHIP_END, $this->numeric_id, 1, $user_id);
					break;
				case 3:	// other person also blocks -> reset to zero
					$data['user_friend_status'] = 0;
					dibi::query('UPDATE `user_friend` SET ', $data, 'WHERE `friend_id` = %i AND `user_id` = %i', $user_id, $this->numeric_id);
					break;				
				}
			}
		}
		catch (Exception $e) {
			dibi::rollback();
			throw $e;
		}
		dibi::commit();
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getGroups()
	{
		$groups = dibi::fetchAll("SELECT `group_id` FROM `group_user` WHERE `user_id` = %i", $this->numeric_id);
		$result = array();
		foreach ($groups as $row) {
			$data     = $row->toArray();
			$result[] = array(
				'group_id' => $data['group_id']
			);
		}
		return $result;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getFriends()
	{
//		$friends = dibi::fetchAll("SELECT `user_friend`.`friend_id`,`user`.`user_login` FROM `user_friend` LEFT JOIN `user` ON (`user`.`user_id` = `user_friend`.`friend_id`) WHERE `user_friend`.`user_id` = %i AND `user_friend`.`user_friend_status` = '2' AND EXISTS (SELECT f.`user_id` FROM `user_friend` f WHERE f.`user_id` = `user_friend`.`friend_id` AND f.`friend_id` = `user_friend`.`user_id` AND f.`user_friend_status` = '2')", $this->numeric_id);
		$friends = dibi::fetchAll("SELECT `user_friend`.`friend_id`,`user`.`user_login` FROM `user_friend`, `user` WHERE `user`.`user_id` = `user_friend`.`friend_id` AND `user_friend`.`user_id` = %i AND `user_friend`.`user_friend_status` = '2' AND EXISTS (SELECT f.`user_id` FROM `user_friend` f WHERE f.`user_id` = `user_friend`.`friend_id` AND f.`friend_id` = `user_friend`.`user_id` AND f.`user_friend_status` = '2')", $this->numeric_id);
		$result  = array();
		foreach ($friends as $row) {
			$data                       = $row->toArray();
			$result[$data['friend_id']] = $data['user_login'];
		}
		return $result;
		
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function incrementVisitor()
	{
		$result = dibi::query("UPDATE `user` SET `user_viewed` = user_viewed+1 WHERE `user_id` = %i", $this->numeric_id);
	}


	/**
	 *	Returns the full name of a user.
	 *	@param int $user_id
	 *	@return int | boolean
	 */
	public static function getFullName($user_id)
	{
		$result = trim(dibi::fetchSingle("SELECT CONCAT(`user_name`,' ',`user_surname`) FROM `user` WHERE `user_id` = %i", $user_id)); // trim here to catch the space added with concat

		if (empty($result)) {
			return self::getUserLogin($user_id);
		}
		return $result;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getUserLogin($user_id)
	{
		$result = dibi::fetchSingle("SELECT `user_login` FROM `user` WHERE `user_id` = %i", $user_id);
		if (empty($result)) {
			return "unknown";
		}
		return trim($result);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getOwnerIdsFromLogin($user_login)
	{
		$users = dibi::fetchAll("SELECT `user_id` FROM `user` WHERE `user_login` LIKE %~like~", $user_login);
		if (empty($users)) {
			return array(0);
		}
		$result = array();
		foreach ($users as $row) {
			$data = $row->toArray();
			$result[] = $data['user_id'];
		}
		return $result;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getTopUsers($count = 10)
	{
		$user = NEnvironment::getUser()->getIdentity();
		if (!empty($user)) {
			$visibility = "u.`user_visibility_level` IN ('1','2')";
		} else {
			$visibility = "u.`user_visibility_level` IN ('1')";
		}
		$users  = dibi::fetchAll("SELECT `user_id`,`user_login`,`user_name`,`user_surname`,(SELECT COUNT(uf.`friend_id`) FROM `user_friend` uf WHERE uf.`user_id` = u.`user_id` AND ) as friends FROM `user` u WHERE u.`user_status` = '1' AND uf.`user_friend_status` = '2'" . $visibility . " ORDER BY friends DESC  LIMIT 0,%i", $count);
		$result = array();
		foreach ($users as $row) {
			$data = $row->toArray();
			$result[$data['user_id']]['user_login']   = $data['user_login'];
			$result[$data['user_id']]['user_name']    = $data['user_name'];
			$result[$data['user_id']]['user_surname'] = $data['user_surname'];
			$result[$data['user_id']]['friends']      = $data['friends'];
		}
		return $result;
		
	}

	/**
	 *	Checks whether the username (login) is already registered.
	 *	@param string $login
	 *	@return boolean
	 */
	public static function loginExists($login)
	{
		
		$result = dibi::fetchSingle("SELECT `user_login` FROM `user` WHERE `user_login` = %sN", $login);
		if (empty($result)) {
			return false;
		}
		return true;
		
	}


	/**
	 *	Checks whether the email address is already registered.
	 *	@param string $email
	 *	@return boolean
	 */
	public static function emailExists($email)
	{
		$result = dibi::fetchSingle("SELECT `user_email` FROM `user` WHERE `user_email` = %sN", $email);
		if (empty($result)) {
			return false;
		}
		return true;
		
	}


	/**
	 *	Obtains the username (login) from an email address.
	 *	@param string $email
	 *	@return string|boolean
	 */
	public static function userloginFromEmail($email)
	{
		return dibi::fetchSingle("SELECT `user_login` FROM `user` WHERE `user_email` = %s", $email);
	}


	/**
	 *	Checks whether the user has set a geographic position.
	 *	@param void
	 *	@return boolean
	 */
	public function hasPosition()
	{
		$result = dibi::fetchSingle("SELECT `user_id` FROM `user` WHERE `user_id` = %i AND `user_position_x` IS NOT NULL AND `user_position_y` IS NOT NULL", $this->numeric_id);
		if (!empty($result)) {
			return true;
		}
		return false;
	}


	/**
	 *	Returns the position of the user.
	 *	@param void
	 *	@return array
	*/
	public function getPosition()
	{
		$result = dibi::fetchAll("SELECT  `user_position_x`, `user_position_y` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		foreach( $result as $row )
			$data[] = $row->toArray();
		return $data[0];
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function bann()
	{
		if (Auth::MODERATOR <= Auth::isAuthorized(1, $this->numeric_id)) {
			dibi::query("UPDATE `user` SET `user_status` = '0' WHERE `user_id` = %i", $this->numeric_id);
			dibi::query("UPDATE `resource_user_group` SET `resource_user_group_status` = '0' WHERE `member_type` = '1' AND `member_id` = %i", $this->numeric_id);
			dibi::query("UPDATE `group_user` SET `group_user_status` = '0' WHERE `user_id` = %i", $this->numeric_id);
		}
	}


	/**
	 *	Revokes the user's rights to create groups and resources
	 *	@param void
	 *	@return void
	 */
	public function revokeCreationRights()
	{
		if (Auth::MODERATOR <= Auth::isAuthorized(1, $this->numeric_id)) {
			dibi::query("UPDATE `user` SET `user_creation_rights` = '0' WHERE `user_id` = %i", $this->numeric_id);
		}
	}


	/**
	 *	Retrieves the date of last activity of a user.
	 *	@param void
	 *	@return string
	 */
	public function getLastActivity()
	{
		$result = dibi::fetchSingle("SELECT `user_last_activity` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		return $result;
	}


	/**
	 *	Retrieves the date of last activity of a user, with relative formatting for recent times.
	 *	@param int $user_id
	 *	@param string $format How to format the time if absolute value.
	 *	@return array
	 */
	public static function getRelativeLastActivity($user_id, $format = 'r')
	{
		$query_result = dibi::fetchSingle("SELECT `user_last_activity` FROM `user` WHERE `user_id` = %i", $user_id);
		if (empty($query_result) || $query_result=='0000-00-00 00:00:00') {
			return array('last_seen' => _t("never online"), 'online' => false);
		}
		$timestamp = strtotime($query_result);
		$online = false;
		if ($timestamp !== false) {
			if ($timestamp <= 0) {
				$result = _t("never online");			
			} elseif (abs($timestamp - time()) < 60 ) {
				$result = _t("now online");
				$online = true;
			} elseif (abs($timestamp - time()) < 60*5) {
				$result = _t("Last seen less than 5 mins ago.");
			} elseif (abs($timestamp - time()) < 60*10) {
				$result = _t("Last seen less than 10 mins ago.");
			} elseif (strtotime('midnight', $timestamp) == strtotime('midnight')) {
				$result = _t("Last seen:")." ". _t("today");
			} elseif (date('Ymd', strtotime('yesterday')) == date('Ymd', $timestamp)) {
				$result = _t("Last seen:")." ". _t("yesterday");
			} else {
				$result = _t("Last seen:")." ".date($format, $timestamp);
			}
		} else {
			$result = "";
		}
		return array('last_seen' => $result, 'online' => $online);
	}


	/**
	 *	Sets the user's date of last activity to now.
	 *	@param void
	 *	@return void
	 */
	public function setLastActivity()
	{
		dibi::query("UPDATE `user` SET `user_last_activity` = NOW() WHERE `user_id` = %i", $this->numeric_id);
	}


	/**
	 *	Sets the user's date of registration to now.
	 *	@param void
	 *	@return void
	 */
	public function setRegistrationDate()
	{
		dibi::query("UPDATE `user` SET `user_registration` = NOW() WHERE `user_id` = %i", $this->numeric_id);
	}


	/**
	 *	Checks whether a user has the right to create groups and resources by checking the minimum time since confirming the registration and possibly revoked permissions.
	 *	@param void
	 *	@return boolean
	 */
	public function hasRightsToCreate()
	{
		$time         = Settings::getVariable("object_creation_min_time");
		$registration = dibi::fetchSingle("SELECT `user_registration` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		if (date('U', strtotime($registration)) + ($time * 86400) <= date('U')) {
			$privilege = dibi::fetchSingle("SELECT `user_creation_rights` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
			if (!empty($privilege)) {
				return true;
			}
		}
		return false;
	}


	/**
	 *	Checks whether the user of this object is identical with the logged-in user who is performing this action.
	 *	@param void
	 *	@return boolean
	 */
	public function thatsMe() {
		$logged_user = NEnvironment::getUser()->getIdentity();
		
		if (isset($logged_user) && $logged_user->getUserId() == $this->numeric_id)
			return true;
		else
			return false;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getImage($user_id,$size='img',$title=null) {
		$image = Image::createimage($user_id, 1);
		if ($image !== false) {
			return $image->renderImg($size, $title);
		} else {
			return Image::default_img($size, $title);
		}
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getLanguage()
	{
		$result = dibi::fetchSingle("SELECT `user_language` FROM `user` WHERE `user_id` = %i ", $this->numeric_id);
		if (!empty($result)) {
			return $result;
		}
		return 0;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getUserLanguage($user_id)
	{
		$result = dibi::fetchSingle("SELECT `user_language` FROM `user` WHERE `user_id` = %i ", $user_id);
		if (!empty($result)) {
			return $result;
		}
		return 0;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function getAllUsersForCron()
	{
		$result = dibi::fetchAll("SELECT `user_id`, `user_login`, `user_email`, `user_language`, `user_send_notifications`, `user_last_notification`, `user_last_activity`, `user_access_level` FROM `user` WHERE `user_status` = 1 AND `user_send_notifications` != 0 AND (`user_last_notification` + `user_send_notifications` * 3600 < %i)", time());
		if (sizeof($result) < 1) {
			return false;
		}
		foreach($result as $user_array)	{	
			$result_array[] = $user_array->toArray();
		}
		
		return $result_array;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public static function setUserCronQueued($user_id)
	{
		dibi::query("UPDATE `user` SET `user_last_notification` = %i WHERE `user_id` = %i", time(), $user_id);
	}


	/**
	 *	Returns the number of private messages that are marked as unread.
	 *	@param int $user_id
	 *	@return int
	 */
	public static function getUnreadMessages($user_id)
	{
//		$count = dibi::fetchSingle("SELECT COUNT(`resource`.`resource_id`) FROM `resource`  LEFT JOIN `resource_user_group` ON `resource`.`resource_id` = `resource_user_group`.`resource_id` WHERE `resource_user_group`.`resource_opened_by_user` = 0 AND `resource`.`resource_type` IN (1,9,10) AND `resource`.`resource_author` <> %i AND `resource_user_group`.`member_type` = 1 AND `resource_user_group`.`member_id` = %i AND `resource`.`resource_status` <> 0", $user_id, $user_id);
		$count = dibi::fetchSingle("SELECT COUNT(`resource`.`resource_id`) FROM `resource`, `resource_user_group` WHERE `resource`.`resource_id` = `resource_user_group`.`resource_id` AND `resource_user_group`.`resource_opened_by_user` = 0 AND `resource`.`resource_type` IN (1,9,10) AND `resource`.`resource_author` <> %i AND `resource_user_group`.`member_type` = 1 AND `resource_user_group`.`member_id` = %i AND `resource`.`resource_status` <> 0", $user_id, $user_id);
		if (isset($count)) {
			return $count;
		} else {
			return 0;
		}
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function getNotificationSetting()
	{
		return dibi::fetchSingle("SELECT `user_send_notifications` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function setNotificationSetting($value)
	{
		dibi::query("UPDATE `user` SET `user_send_notifications` = %i WHERE `user_id` = %i", $value, $this->numeric_id);
	}

}
