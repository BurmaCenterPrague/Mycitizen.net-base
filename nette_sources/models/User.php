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
 

class User extends BaseModel implements IIdentity
{
	const ADMINISTRATOR = 3;
	const MODERATOR = 2;
	const USER = 1;
	
	private $user_data;
	private $numeric_id;
	
	public static function create($user_id = null)
	{
		return new User($user_id);
	}
	
	public function __construct($user_id)
	{
		if (!empty($user_id)) {
			$result = dibi::fetchAll("SELECT `user_id`,`user_password`,`user_name`,`user_surname`,`user_login`,`user_description`,`user_email`,`user_phone`,`user_phone_imei`,`user_position_x`,`user_position_y`,`user_language`,`user_visibility_level`,`user_access_level`,`user_status`,`user_registration_confirmed`,`user_creation_rights`,`user_send_notifications`,`user_url`,`user_portrait` as user_portrait FROM `user` WHERE `user_id` = %i", $user_id); // user_largeicon` as user_portrait
			if (sizeof($result) > 2) {
				return false;
				throw new Exception(_("More than one user with the same id found."));
			}
			if (sizeof($result) < 1) {
				return false;
				throw new Exception(_("Specified user not found."));
			}
			$data_array       = $result[0]->toArray();
			$this->numeric_id = $data_array['user_id'];
			unset($data_array['user_id']);
			$this->user_data = $data_array;
		}
		return true;
	}
	
	public function setUserData($data)
	{
		foreach ($data as $key => $value) {
			$this->user_data[$key] = $value;
		}
	}
	
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
	
	public function getAvatar()
	{
		$portrait = dibi::fetchSingle("SELECT `user_portrait` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		return $portrait;
	}
	
	public function removeAvatar()
	{
		dibi::query("UPDATE `user` SET `user_portrait` = NULL WHERE `user_id` = %i", $this->numeric_id);
	}
	
	public function userHasIcon()
	{
		$result = dibi::fetchSingle("SELECT `user_icon` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		if (!empty($result)) {
			return true;
		}
		return false;
	}

	public function getIcon()
	{
		$portrait = dibi::fetchSingle("SELECT `user_icon` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		return $portrait;
	}

	public function getBigIcon()
	{
		$portrait = dibi::fetchSingle("SELECT `user_largeicon` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		return $portrait;
	}
	
	public function removeIcons()
	{
		dibi::query("UPDATE `user` SET `user_icon` = NULL WHERE `user_id` = %i", $this->numeric_id);
		dibi::query("UPDATE `user` SET `user_largeicon` = NULL WHERE `user_id` = %i", $this->numeric_id);
	}
	
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
	
	public static function delete($user_id)
	{
		dibi::query("DELETE FROM `user` WHERE `user_id` = %i", $user_id);
	}
	
	public static function encodePassword($password)
	{
		if (strlen($password)>128) return false;

		require(LIBS_DIR.'/Phpass/PasswordHash.php');
		$hasher = new PasswordHash(8, false);
		$hash = $hasher->HashPassword($password);

		return $hasher->HashPassword($password);

//		return sha1($password);
	}
	
	public function getUserId()
	{
		return $this->numeric_id;
	}
	
	/**
	*	required by Nette
	*/
	public function getRoles()
	{
		return array(
			'customer'
		);
	}
	
	public function getVisibilityLevel()
	{
		return $this->user_data['user_visibility_level'];
	}
	
	public function getAccessLevel()
	{
		$result = dibi::fetchSingle("SELECT `user_access_level` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		if (!empty($result)) {
			return $result;
		}
		return 0;
	}

	public static function getAccessLevelFromLogin($login)
	{
		$result = dibi::fetchSingle("SELECT `user_access_level` FROM `user` WHERE `user_login` = %s", $login);
		if (!empty($result)) {
			return $result;
		}
		return 0;
	}
	
	public function insertTag($tag_id)
	{
		$registered_tags = $this->getTags();
		if (!isset($registered_tags[$tag_id])) {
			dibi::query('INSERT INTO `user_tag` (`tag_id`,`user_id`) VALUES (%i,%i)', $tag_id, $this->numeric_id);
		}
	}
	
	public function removeTag($tag_id)
	{
		$registered_tags = $this->getTags();
		if (isset($registered_tags[$tag_id])) {
			dibi::query('DELETE FROM `user_tag` WHERE `tag_id` = %i AND `user_id` = %i', $tag_id, $this->numeric_id);
		}
	}
	
	public function getTags()
	{
		$result = dibi::fetchAll("SELECT ut.`tag_id`,t.`tag_name` FROM `user_tag` ut LEFT JOIN `tag` t ON (t.`tag_id` = ut.`tag_id`) WHERE `user_id` = %i ORDER BY t.`tag_name` ASC", $this->numeric_id);
		$array  = array();
		foreach ($result as $row) {
			$data                   = $row->toArray();
			$array[$data['tag_id']] = Tag::create($data['tag_id']);
		}
		
		return $array;
	}
	
	/**
	*		Groups tags according to their parent and sorts them by parent, then child
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
	
	public function sendConfirmationEmail()
	{
		$hash  = $this->user_data['user_hash'];
		$email = $this->user_data['user_email'];
		$name =  $this->user_data['user_login'];
		$id    = $this->numeric_id;
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		$link  = "http://" . $_SERVER['HTTP_HOST'] . "/user/confirm/?user_id=" . $id . "&control_key=" . $hash . "&lang=" . $language;
//		$link  = "http://" . URI . "/user/confirm/?user_id=" . $id . "&control_key=" . $hash;
		$body  = sprintf(_("Hello %s,\nthank you for signing up at Mycitizen.net!\nTo finish your registration click on the following link."), $name ). "\n\n " . $link;
		
		$headers = 'From: Mycitizen.net <' . Settings::getVariable("from_email") . '>' . "\n" . "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit";
		
		return mail($email, '=?UTF-8?B?' . base64_encode(_('Finish your registration at Mycitizen.net')) . '?=', $body, $headers);
	}
	
	public static function getEmailOwner($email)
	{
		$result = dibi::fetchSingle("SELECT `user_id` FROM `user` WHERE `user_email` = %s", $email);
		if (!empty($result)) {
			return User::create($result);
		}
		return null;
	}
	
	public function firstLogin()
	{
		$result = dibi::fetchSingle("SELECT `user_first_login` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		if ($result == 1) {
			return false;
		}
		return true;
	}
	
	public function registerFirstLogin()
	{
		$result = dibi::query("UPDATE `user` SET `user_first_login` = '1' WHERE `user_id` = %i", $this->numeric_id);
	}
	
	public function sendLostpasswordEmail()
	{
		$hash = self::generateHash();
		
		$this->user_data['user_hash'] = $hash;
		$this->save();
		$name =  $this->user_data['user_login'];
		$email = $this->user_data['user_email'];
		$id    = $this->numeric_id;
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		$link  = "http://" . $_SERVER['HTTP_HOST'] . "/user/changepassword/?user_id=" . $id . "&control_key=" . $hash . "&lang=" . $language;
		$body  = sprintf(_("Hello %s,\nYou have requested a password change on Mycitizen.net.\nTo finish your request click on the following link."), $name) . "\n\n " . $link;
		
		$headers = 'From: Mycitizen.net <' . Settings::getVariable("reply_email") . '>' . "\n" . "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit";
		mail($email, '=?UTF-8?B?' . base64_encode(_('Password change on Mycitizen.net')) . '?=', $body, $headers);
		return $body;
	}
	
	public static function finishPasswordchange($user_id, $control_key, $password)
	{
		$result = dibi::fetchSingle("SELECT `user_login` FROM `user` WHERE `user_id` = %i AND `user_hash` = %s", $user_id, $control_key);
		if (!empty($result)) {
			dibi::query("UPDATE `user` SET `user_password` = %s WHERE `user_id` = %i", self::encodePassword($password), $user_id);
			return true;
		}
		return false;
	}

	public static function changePassword($user_id, $password)
	{
		return dibi::query("UPDATE `user` SET `user_password` = %s WHERE `user_id` = %i", self::encodePassword($password), $user_id);

	}
	
	public function sendEmailchangeEmail()
	{
		$hash = self::generateHash();
		
		$this->user_data['user_hash'] = $hash;
		$this->save();
		$name =  $this->user_data['user_login'];
		$email = $this->user_data['user_email'];
		$id    = $this->numeric_id;
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		$link  = "http://" . $_SERVER['HTTP_HOST'] . "/user/emailchange/?user_id=" . $id . "&control_key=" . $hash . "&lang=" . $language;
		$body  = sprintf(_("Hello %s,\nYou have requested an email change on Mycitizen.net.\nTo finish your request click on the following link."), $name ) . "\n\n " . $link;
		
		$headers = 'From: Mycitizen.net <' . Settings::getVariable("reply_email") . '>' . "\n" . "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit";
		mail($email, '=?UTF-8?B?' . base64_encode(_('Email change on Mycitizen.net')) . '?=', $body, $headers);
		return $body;
	}
	
	
	public static function finishEmailchange($user_id, $control_key)
	{
		$result = dibi::fetchSingle("SELECT `user_email_new` FROM `user` WHERE `user_id` = %i AND `user_hash` = %s", $user_id, $control_key);
		if (!empty($result) && $result != "") {
			dibi::query("UPDATE `user` SET `user_email` = `user_email_new`,`user_email_new` = '' WHERE `user_id` = %i", $user_id);
			return true;
		}
		return false;
	}

	public static function finishEmailchangeAdmin($user_id, $user_email)
	{
		dibi::query("UPDATE `user` SET `user_email` = %s WHERE `user_id` = %i", $user_email, $user_id);
		return true;
	}
	
	public static function finishRegistration($user_id, $control_key)
	{
		$result = dibi::fetchSingle("SELECT `user_login` FROM `user` WHERE `user_id` = %i AND `user_registration_confirmed` = '0' AND `user_hash` = %s", $user_id, $control_key);
		if (!empty($result)) {
			dibi::query("UPDATE `user` SET `user_status` = '1',`user_registration_confirmed` = '1' WHERE `user_id` = %i", $user_id);
			return true;
		}
		return false;
	}
	
	public static function getType()
	{
		return 1;
	}
	
	public function friendshipIsRegistered($user_id)
	{
		$result = dibi::fetchSingle("SELECT `friend_id` FROM `user_friend` WHERE `user_id` = %i AND `friend_id` = %i", $this->numeric_id, $user_id);
		if (!empty($result)) {
			return true;
			//return $result;
		}
		return false;
	}
	
	public function friendsStatus($user_id)
	{
		$result = dibi::fetchSingle("SELECT `user_friend_status` FROM `user_friend` WHERE (`user_id` = %i AND `friend_id` = %i) ", $this->numeric_id, $user_id);
		if (!empty($result)) {
			return $result;
		}
		return 0;
	}

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
	*	status:
	*	1:	requested
	*	2:	accepted
	*	3:	rejected/blocked
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
					StaticModel::sendSystemMessage(4, $this->numeric_id, $user_id);
					break;
				case 2: // friendship exists -> terminate
					$data['user_friend_status'] = 3;
					dibi::query('UPDATE `user_friend` SET ', $data, 'WHERE `friend_id` = %i AND `user_id` = %i', $user_id, $this->numeric_id);
					$data['user_friend_status'] = 0;
					dibi::query('UPDATE `user_friend` SET ', $data, 'WHERE `friend_id` = %i AND `user_id` = %i', $this->numeric_id, $user_id);
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
	
	public function getFriends()
	{
		$friends = dibi::fetchAll("SELECT `user_friend`.`friend_id`,`user`.`user_login` FROM `user_friend` LEFT JOIN `user` ON (`user`.`user_id` = `user_friend`.`friend_id`) WHERE `user_friend`.`user_id` = %i AND `user_friend`.`user_friend_status` = '2' AND EXISTS (SELECT f.`user_id` FROM `user_friend` f WHERE f.`user_id` = `user_friend`.`friend_id` AND f.`friend_id` = `user_friend`.`user_id` AND f.`user_friend_status` = '2')", $this->numeric_id);
		$result  = array();
		foreach ($friends as $row) {
			$data                       = $row->toArray();
			$result[$data['friend_id']] = $data['user_login'];
		}
		return $result;
		
	}
	
	public function incrementVisitor()
	{
		$result = dibi::query("UPDATE `user` SET `user_viewed` = user_viewed+1 WHERE `user_id` = %i", $this->numeric_id);
	}
	
	public static function getFullName($user_id)
	{
		$result = dibi::fetchSingle("SELECT CONCAT(`user_name`,' ',`user_surname`) FROM `user` WHERE `user_id` = %i", $user_id);
		if (empty($result)) {
			return User::getUserLogin($user_id);
		}
		return trim($result);
	}
	
	public static function getUserLogin($user_id)
	{
		$result = dibi::fetchSingle("SELECT `user_login` FROM `user` WHERE `user_id` = %i", $user_id);
		if (empty($result)) {
			return "";
		}
		return trim($result);
	}
	

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
	public static function loginExists($login)
	{
		$result = dibi::fetchSingle("SELECT `user_login` FROM `user` WHERE `user_login` = %s", $login);
		if (empty($result)) {
			return false;
		}
		return true;
		
	}
	
	public static function emailExists($email)
	{
		$result = dibi::fetchSingle("SELECT `user_email` FROM `user` WHERE `user_email` = %s", $email);
		if (empty($result)) {
			return false;
		}
		return true;
		
	}
	
	public static function userloginFromEmail($email)
	{
		return dibi::fetchSingle("SELECT `user_login` FROM `user` WHERE `user_email` = %s", $email);
	}
	
	public function hasPosition()
	{
		$result = dibi::fetchSingle("SELECT `user_id` FROM `user` WHERE `user_id` = %i AND `user_position_x` IS NOT NULL AND `user_position_y` IS NOT NULL", $this->numeric_id);
		if (!empty($result)) {
			return true;
		}
		return false;
	}

	public function getPosition()
	{
		$result = dibi::fetchAll("SELECT  `user_position_x`, `user_position_y` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		foreach( $result as $row )
			$data[] = $row->toArray();
		return $data[0];
	}
	
	public function bann()
	{
		if (Auth::MODERATOR <= Auth::isAuthorized(1, $this->numeric_id)) {
			dibi::query("UPDATE `user` SET `user_status` = '0' WHERE `user_id` = %i", $this->numeric_id);
		}
	}
	
	public function revokeCreationRights()
	{
		if (Auth::MODERATOR <= Auth::isAuthorized(1, $this->numeric_id)) {
			dibi::query("UPDATE `user` SET `user_creation_rights` = '0' WHERE `user_id` = %i", $this->numeric_id);
		}
	}
	

	public function getLastActivity()
	{
		$result = dibi::fetchSingle("SELECT `user_last_activity` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
		return $result;
	}
	
	public function setLastActivity()
	{
		dibi::query("UPDATE `user` SET `user_last_activity` = NOW() WHERE `user_id` = %i", $this->numeric_id);
	}
	
	public function setRegistrationDate()
	{
		dibi::query("UPDATE `user` SET `user_registration` = NOW() WHERE `user_id` = %i", $this->numeric_id);
	}
	
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
	
	public function thatsMe() {
		$logged_user = NEnvironment::getUser()->getIdentity();
		
		if (isset($logged_user) && $logged_user->getUserId() == $this->numeric_id)
			return true;
		else
			return false;
	}

	public static function getImage($user_id,$size='img',$title=null) {
	
		$width=20;
		
		if (isset($title)) $title_tag =' title="'.$title.'"'; else $title_tag='';

		// serving as image file
		$user = User::create($user_id);
		
		switch ($size) {
			case 'img': $src = $user->getAvatar(); $width=160; break;
			case 'icon': $src = $user->getIcon(); $width=20; break;
			case 'large_icon': $src = $user->getBigIcon(); $width=40; break;
		}
		
		if (!empty($src) && (Auth::isAuthorized(1, $user_id)>0)) {
			$hash=md5($src);
			$link = '/images/cache/user/'.$user_id.'-'.$size.'-'.$hash.'.jpg';
			$image = '<img src="'.$link.'" width="'.$width.'"'.$title_tag.'/>';
		} else {
			$image = '<img src="/images/user-'.$size.'.png" width="'.$width.'"'.$title_tag.'/>';
		}
		return $image;

	}
	
	public static function saveImage($id) {
	
		$object = User::create($id);
		
		if (isset($object)) {
		
			$sizes = array('img', 'icon', 'large_icon');
		
			foreach ( $sizes as $size) {
		
				switch ($size) {
					case 'img': $src = $object->getAvatar(); break;
					case 'icon': $src = $object->getIcon(); break;
					case 'large_icon': $src = $object->getBigIcon(); break;
				}
	
				if (!empty($src)) {
					$hash=md5($src);
		
					$link = WWW_DIR.'/images/cache/user/'.$id.'-'.$size.'-'.$hash.'.jpg';
		
					if(!file_exists($link)) {
						$img_r = @imagecreatefromstring(base64_decode($src));
						if (!imagejpeg($img_r, $link)) {
							die(_("Error writing image: ").$link);
						};
					}
				}

			}

		}
		
	}

	public function getLanguage()
	{
		$result = dibi::fetchSingle("SELECT `user_language` FROM `user` WHERE `user_id` = %i ", $this->numeric_id);
		if (!empty($result)) {
			return $result;
		}
		return 0;
	}
	
	public static function getAllUsersForCron()
	{
		$result = dibi::fetchAll("SELECT `user_id`, `user_login`, `user_email` FROM `user` WHERE `user_status` = 1 AND `user_send_notifications` != 0 AND (`user_last_notification` + `user_send_notifications` * 3600 < %i)", time());
		if (sizeof($result) < 1) {
			return false;
			throw new Exception(_("Specified user not found."));
		}
		foreach($result as $user_array)	{	
			$result_array[] = $user_array->toArray();
		}
		
		return $result_array;
	}

	public static function setUserCronSent($user_id)
	{
		dibi::query("UPDATE `user` SET `user_last_notification` = %i WHERE `user_id` = %i", time(), $user_id);
	}
	
	public static function getUnreadMessages($user_id)
	{
		$count = dibi::fetchSingle("SELECT COUNT(`resource`.`resource_id`) FROM `resource`  LEFT JOIN `resource_user_group` ON `resource`.`resource_id` = `resource_user_group`.`resource_id` WHERE `resource_user_group`.`resource_opened_by_user` = 0 AND (`resource`.`resource_type` = 1 OR `resource`.`resource_type` = 9) AND `resource`.`resource_author` <> %i AND `resource_user_group`.`member_type` = 1 AND `resource_user_group`.`member_id` = %i AND `resource`.`resource_status` <> 0",$user_id,$user_id);
		if (isset($count)) {
			return $count;
		} else {
			return 0;
		}
	}
	
	public function getNotificationSetting()
	{
		return dibi::fetchSingle("SELECT `user_send_notifications` FROM `user` WHERE `user_id` = %i", $this->numeric_id);
	}

	public function setNotificationSetting($value)
	{
		dibi::query("UPDATE `user` SET `user_send_notifications` = %i WHERE `user_id` = %i", $value, $this->numeric_id);
	}

}
