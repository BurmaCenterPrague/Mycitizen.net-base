<?php
/**
 * mycitizen.net - Open source social networking for civil society
 *
 * @version 0.2.2 beta
 *
 * @author http://mycitizen.org
 * @copyright  Copyright (c) 2013 Burma Center Prague (http://www.burma-center.org)
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */

class API_Base extends API implements iAPI
{
	public $format = "application/json";
	protected $user_id = null;
	protected $partner_id = null;
	public function __construct() {
		if((isset($_GET['PASS']) && isset($_GET['USER'])) || (isset($_POST['PASS']) && isset($_POST['USER']))) {
			$user = NEnvironment::getUser();
      	try {
		if(isset($_GET['USER'])) {
         		$user->login($_GET['USER'], $_GET['PASS']);
		} else {
			$user->login($_POST['USER'], $_POST['PASS']);

		}
		if($user->getIdentity()->firstLogin()) {
			$user->getIdentity()->registerFirstLogin();
			$user->getIdentity()->setLastActivity();	
		} else {
			$user->getIdentity()->setLastActivity();
		}

			}catch(Exception $e) {

			}	
		}
		$this->isLoggedIn();
		$this->setFormat();
	}
	
	protected function setFormat() {
		if(!empty($_GET['format'])) {
			$this->format = $_GET['format'];
		}
		if(!empty($_POST['format'])) {
			$this->format = $_POST['format'];
		}

	}

	protected function checkTime($time) {
		$lovest_time = strtotime("- 2 years");//date('U',mktime(0,0,0,1,1,2000));
		$highest_time = strtotime("+ 1 day");
		
		if(!is_numeric($time)) {
			$time = strtotime($time);
		}
		if(!$time || $time < $lovest_time  || $time > $highest_time) {
			return false;
		}
		return $time;
	}
		
	protected function isLoggedIn() {
    $user_id = NEnvironment::getUser()->getIdentity();
    if (empty($user_id)) {
		return false;
    }

	if(!NEnvironment::getUser()->getIdentity()->isActive()) {

	 	return false;
	}
    $this->userId = $user_id->getUserId();

    $request = array_merge($_GET, $_POST);
	 /*
    if (!empty($request['PARTNERID'])) {
      $this->partner_id = $request['PARTNERID'];
    } elseif (!empty($request['PARTNERKEY'])) {
      $partner_id = Db::fetchSingle("SELECT `partner_id` FROM `partner` WHERE `partnerkey` = %s", $request['PARTNERKEY']);
      if (empty($partner_id)) {
        return false;
      }
      $this->partner_id = $partner_id;
    } else {
        return false;
    }
	 */
    return true;
  }

	/**
	 * Comment
	 *
	 * @url	POST	/Login
	 */	
	public function getLogin() {
		if(!$this->isLoggedIn()) {
			$error = "unkonwn_user";
			$user_id = NEnvironment::getUser()->getIdentity();
    			if (!empty($user_id)) {
				if(!NEnvironment::getUser()->getIdentity()->isActive()) {
					$error = "not_active";
				}
			}
			return array('result'=>false,'error'=>$error);
		}
		$user = User::create($this->userId);
		$data = $user->getUserData();

    return array('result'=>true, 'user_id'=>$this->userId,'data'=>$data);//Allready checked by common authorize function in rest server
	}
	
	/**
	 * Comment
	 *
	 * @url	POST	/Data
	 */	
	public function getData() {
		 if(!$this->isLoggedIn()) {
         throw new RestException('401',null);
      }
		if(!empty($_POST['type'])) {
			foreach($_POST['type'] as $t) {
				$types[] = $t;
			}
		} else {
			$types = array(ListerControlMain::LISTER_TYPE_USER,ListerControlMain::LISTER_TYPE_GROUP,ListerControlMain::LISTER_TYPE_RESOURCE);
		}
		$filter = array();
		if(!empty($_POST['filter'])) {
			$filter= $_POST['filter'];
		}
		return Administration::getData($types,$filter);
	}

	/**
    * Comment
    *
    * @url  POST   /UserData
    */
   public function getUserData() {
		 if(!$this->isLoggedIn()) {
         throw new RestException('401',null);
      }
		if(!empty($_POST['user_id'])) {
      	$user_id = $_POST['user_id'];
      }

		if(Auth::isAuthorized(1,$_POST['user_id']) == Auth::ADMINISTRATOR || Auth::isAuthorized(1,$_POST['user_id']) != Auth::UNAUTHORIZED) {
			$user = User::create($user_id);
			$data = $user->getUserData();
         if(empty($data)) {
            throw new RestException('402',null);
         }
	$logged_user = NEnvironment::getUser()->getIdentity();

	$user = User::Create($_POST['user_id']);
	$friend_user_relationship = $user->friendsStatus($logged_user->getUserId());
   	$user_friend_relationship = $logged_user->friendsStatus($user->getUserId());

        $data['logged_user_user'] = $user_friend_relationship;
        $data['user_logged_user'] = $friend_user_relationship;

         return $data;

		} else {
			throw new RestException('403',null);
		}
		
   }

	/**
    * Comment
    *
    * @url  POST   /GroupData
    */
   public function getGroupData() {
		 if(!$this->isLoggedIn()) {
         throw new RestException('401',null);
      }
		if(!empty($_POST['group_id'])) {
         $group_id = $_POST['group_id'];
      }
		if(Auth::isAuthorized(2,$_POST['group_id']) == Auth::ADMINISTRATOR || Auth::isAuthorized(2,$_POST['group_id']) != Auth::UNAUTHORIZED) {
	$logged_user = NEnvironment::getUser()->getIdentity();

      	$group = Group::create($group_id);
			$data = $group->getGroupData();
         if(empty($data)) {
            throw new RestException('402',null);
         }
	
	if($group->userIsRegistered($logged_user->getUserId())) {
         	$data['logged_user_member'] = 1;
            	} else {
               	$data['logged_user_member'] = 0;
            	}

         return $data;

		} else {
			throw new RestException('403',null);
		}

   }

	/**
    * Comment
    *
    * @url  POST   /ResourceData
    */
   public function getResourceData() {
		 if(!$this->isLoggedIn()) {
         throw new RestException('401',null);
      }
		if(!empty($_POST['resource_id'])) {
         $resource_id = $_POST['resource_id'];
      }
		if(Auth::isAuthorized(3,$_POST['resource_id']) == Auth::ADMINISTRATOR || Auth::isAuthorized(3,$_POST['resource_id']) != Auth::UNAUTHORIZED) {
	$logged_user = NEnvironment::getUser()->getIdentity();

      	$resource = Resource::create($resource_id);
			$data = $resource->getResourceData();
			if(empty($data)) {
				throw new RestException('402',null);
			}
	if($resource->userIsRegistered($logged_user->getUserId())) {
                  $data['logged_user_member'] = 1;
               } else {
                  $data['logged_user_member'] = 0;
               }

      	return $data;
		} else {
			throw new RestException('403',null);
		}
   }

	 /**
    * Comment
    *
    * @url  POST   /Tags
    */
   public function getTags() {
       if(!$this->isLoggedIn()) {
         throw new RestException('401',null);
      }
		
		return array('result'=>true,'tags'=>Tag::getTreeArray());
   }

	/**
    * Comment
    *
    * @url  POST   /Subscribe
    */
   public function setSubscription() {
       	if(!$this->isLoggedIn()) {
         	throw new RestException('401',null);
      	}
	if(!empty($_POST['objectType']) && !empty($_POST['objectId']) && isset($_POST['objectAction'])) {

		$logged_user = NEnvironment::getUser()->getIdentity();
	
		if($_POST['objectType'] == "user") {
			$user = User::Create($_POST['objectId']);
			if($_POST['objectAction'] == 1) {
				$logged_user->updateFriend($_POST['objectId'],array());
			} else if($_POST['objectAction'] == 0) {
				$logged_user->removeFriend($_POST['objectId']);	
			}
			$friend_user_relationship = $user->friendsStatus($logged_user->getUserId());
   			$user_friend_relationship = $logged_user->friendsStatus($user->getUserId());
        		$res =  $user_friend_relationship."".$friend_user_relationship;
		} else if($_POST['objectType'] == "group") {
			$group = Group::create($_POST['objectId']);
			if($_POST['objectAction'] == 1) {
				$group->updateUser($logged_user->getUserId(),array()); 
			} else if($_POST['objectAction'] == 0) {

				$group->removeUser($logged_user->getUserId());
			}

			if($group->userIsRegistered($logged_user->getUserId())) {
         			$res = "1";
            		} else {
               			$res = "0";
            		}

		} else if($_POST['objectType'] == "resource") {
			$resource = Resource::create($_POST['objectId']);
			if($_POST['objectAction'] == 1) {
				$resource->updateUser($logged_user->getUserId(),array());
			} else if($_POST['objectAction'] == 0) {
				$resource->removeUser($logged_user->getUserId());
			}

			if($resource->userIsRegistered($logged_user->getUserId())) {
                  		$res = "1";
               		} else {
                  		$res = "0";
               		}

		}
	}

	return array('result'=>true,'result_str'=>$res);
		
   }

	/**
	* Comment
	*
	* @url POST	/Register
	*/
	public function postRegister() {
		if(!empty($_POST['login']) && !empty($_POST['email']) && !empty($_POST['password'])) {
			if(User::loginExists($_POST['login'])) {
				return array('result'=>false,'error'=>'login_exists');
			}

			if(User::emailExists($_POST['email'])) {
				return array('result'=>false,'error'=>'email_exists');

      			}

			if(StaticModel::isSpamEmail($_POST['email'])) {
				return array('result'=>false,'error'=>'spam_email');

			}

			if(!StaticModel::validEmail($_POST['email'])) {
				return array('result'=>false,'error'=>'invalid_email');
      			}


      			$new_user = User::create();;

      			$password = User::encodePassword($_POST['password']);
			$hash = User::generateHash();
     

			$values = array('user_login'=>$_POST['login'],'user_email'=>$_POST['email'],'user_password'=>$password,'user_hash'=>$hash);
 
			$new_user->setUserData($values);
      			$new_user->save();
		
			$new_user->setRegistrationDate();

			$link = $new_user->sendConfirmationEmail();

			return array('result'=>true);
		}
	}

	/**
	* Comment
	*
	* @url POST	/Userexists
	*/
	public function postUserexists() {
		if(!empty($_POST['username'])) {
			if(User::loginExists($_POST['username'])) {
				return array('result'=>false,'error'=>'login_exists');
			} else {
				return array('result'=>true);
			}
		}
	}

	/**
	* Comment
	*
	* @url POST	/Hasmessage
	*/
	public function postHasmessage() {
		if(!$this->isLoggedIn()) {
         		throw new RestException('401',null);
      		}
			
		return array('result' => true, 'message_count' => Resource::getUnreadMessages());
	}


	/**
	* Comment
	*
	* @url POST	/SendMessage
	*/
	public function postSendMessage() {
		if(!$this->isLoggedIn()) {
         		throw new RestException('401',null);
      		}
		if(!empty($_POST['objectType']) && !empty($_POST['objectId']) && !empty($_POST['message'])) {
			$logged_user = NEnvironment::getUser()->getIdentity();

			if($_POST['objectType'] == 'user') {
				$resource = Resource::create();
      				$data = array();
      				$data['resource_author'] = $logged_user->getUserId();
      				$data['resource_type'] = 1;
				$data['resource_visibility_level'] = 3;
      				$data['resource_name'] = $_POST['message'];
      				$data['resource_data'] = json_encode(array('message_text'=>$_POST['message']));
      				$resource->setResourceData($data);
      				$resource->save();
      				$resource->updateUser($_POST['objectId'],array('resource_user_group_access_level'=>1));

      				$resource->updateUser($logged_user->getUserId(),array('resource_user_group_access_level'=>1,'resource_opened_by_user'=>1));
			} else if($_POST['objectType'] == "group") {
				$resource = Resource::create();
      				$data = array();
      				$data['resource_author'] = $logged_user->getUserId();
      				$data['resource_type'] = 8;
      				$data['resource_name'] = $_POST['message'];
      				$data['resource_data'] = json_encode(array('message_text'=>$_POST['message']));
     	 			$resource->setResourceData($data);
      				$resource->save();
				$group = Group::Create($_POST['objectId']);
				$group->setLastActivity();
      				$resource->updateUser($logged_user->getUserId(),array('resource_user_group_access_level'=>1));
				$resource->updateGroup($_POST['objectId'],array('resource_user_group_access_level'=>1));

			} else if($_POST['objectType'] == "resource") {
				$object_resource = Resource::Create($_POST['objectId']);
				$resource = Resource::create();
      				$data = array();
      				$data['resource_author'] = $logged_user->getUserId();
      				$data['resource_type'] = 8;
      				$data['resource_name'] = $_POST['message'];
      				$data['resource_data'] = json_encode(array('message_text'=>$_POST['message']));
      				$resource->setResourceData($data);
				$resource->setParent($object_resource->getResourceId());

      				$resource->save();
				$object_resource->setLastActivity();
      				$resource->updateUser($logged_user->getUserId(),array('resource_user_group_access_level'=>1));

			}
			return array('result'=>true);
		} else {
     			return array('result'=>false);
		}

	}

	/**
	* Comment
	*
	* @url POST	/ChangeProfile
	*/
	public function postChangeProfile() {
	
		$values['user_name'] = $_POST['firstName'];
		$values['user_surname'] = $_POST['lastName'];
		$email = $_POST['email'];
		$values['user_visibility_level'] = $_POST['visibility'];
		$values['user_description'] = $_POST['description'];
		$values['user_position_x'] = $_POST['position_gpsx'];
		$values['user_position_y'] = $_POST['position_gpsy'];
		if($_POST['image'] != "") {
	//		$values['user_portrait'] = $_POST['image'];
		}
      		$logged_user = NEnvironment::getUser()->getIdentity();
		$user = User::Create($logged_user->getUserId());
		$data = $user->getUserData();
		if($data['user_email'] != $email && User::emailExists($email)) {
			return array('result'=>false,'error'=>'email_exists');
		}
      		if($data['user_email'] != $email && !StaticModel::validEmail($email)) {
			return array('result'=>false,'error'=>'invalid_email');
      		}
		if($data['user_email'] != $email) {
			$access = $user->getAccessLevel();
			if($access < 2) {
				$values['user_email_new'] = $email;
				$values['user_email'] = $data['user_email'];

				$user->sendEmailchangeEmail();
			}
		}
        	$user->setUserData($values);
        	$user->save();
		
		return array('result'=>true);

	}

	/**
	*Comment
	* @url POST     /ChangeProfileTag
        */
        public function postChangeProfileTag() {

                $logged_user = NEnvironment::getUser()->getIdentity();
		$tag_id = $_POST['tagId'];
		if($_POST['tagStatus'] == "true") {
                        $logged_user->insertTag($tag_id);
		} else {
			$logged_user->removeTag($tag_id);
		}
                return array('result'=>true);

        }

	/**
	*Comment
	* @url POST	/RequestPasswordChange
	*/
	public function postRequestPasswordChange() {
		$user = User::getEmailOwner($_POST['user_email']);
		if(!empty($user)) {
			$user->sendLostpasswordEmail();
			return array('result'=>true);

		} else {
			return array('result'=>false);
		}
	}

	/**
	*Comment
	* @url POST     /MoveToTrash
        */
        public function postMoveToTrash() {
		$user = NEnvironment::getUser()->getIdentity();	
		$resource_id = $_POST['message_id'];
      		$resource = Resource::create($resource_id);
		if(!empty($resource)) {
			if(!empty($user)) {
				if($resource->userIsRegistered($user->getUserId())) {
					Resource::moveToTrash($resource_id);
				}
			}
		}
                return array('result'=>true);

        }


	/**
	*Comment
	* @url POST     /MoveFromTrash
        */
        public function postMoveFromTrash() {
		$user = NEnvironment::getUser()->getIdentity();	
		$resource_id = $_POST['message_id'];
      		$resource = Resource::create($resource_id);
		if(!empty($resource)) {
			if(!empty($user)) {
				if($resource->userIsRegistered($user->getUserId())) {
					Resource::moveFromTrash($resource_id);
				}
			}
		}
                return array('result'=>true);

        }

	/**
	*Comment
	* @url POST     /AcceptFriendship
        */
        public function postAcceptFriendship() {
		$user = NEnvironment::getUser()->getIdentity();	
		$friend_id = $_POST['friend_id'];
		$resource_id = $_POST['message_id'];
      		$resource = Resource::create($resource_id);
		if(!empty($resource)) {
			if(!empty($user)) {
				$user->updateFriend($friend_id,array());

				if($resource->userIsRegistered($user->getUserId())) {
					Resource::moveToTrash($resource_id);
				}
			}
		}
                return array('result'=>true);

        }

	/**
	*Comment
	* @url POST     /DeclineFriendship
        */
        public function postDeclineFriendship() {
		$user = NEnvironment::getUser()->getIdentity();	
		$friend_id = $_POST['friend_id'];
		$resource_id = $_POST['message_id'];
      		$resource = Resource::create($resource_id);
		if(!empty($resource)) {
			if(!empty($user)) {
				$user->removeFriend($friend_id,array());

				if($resource->userIsRegistered($user->getUserId())) {
					Resource::moveToTrash($resource_id);
				}
			}
		}
                return array('result'=>true);

        }

	/**
	*Comment
	* @url POST     /CreateGroup
        */
        public function postCreateGroup() {
		$user = NEnvironment::getUser()->getIdentity();	
		
		$group_name = $_POST['name'];
		$group_description = $_POST['description'];
		$group_visibility = $_POST['visibility'];
		$group_gpsx = $_POST['position_gpsx'];
		$group_gpsy = $_POST['position_gpsy'];
		$group_tags = $_POST['tags'];
		$tags = explode(',',$group_tags);
		if(Auth::VIP > $user->getAccessLevel()) {
      			if(!$user->hasRightsToCreate()) {
				return array('result'=>false,'message'=>'no_rights');
      			}
		}

		$group = Group::create();

		$values['group_name'] = $group_name;
		$values['group_description'] = $group_description;
		$values['group_visibility_level'] = $group_visibility;
		$values['group_position_x'] = $group_gpsx;
		$values['group_position_y'] = $group_gpsy;

		$values['group_author'] = $user->getUserId();
        	$group->setGroupData($values);
        	$group->save();
		$group->setLastActivity();
		$group->updateUser($user->getUserId(),array('group_user_access_level'=>3));
		$group_id = $group->getGroupId();
		
		foreach($tags as $tag_id) {
			$group->insertTag($tag_id);
		}	
                return array('result'=>true,'group_id'=>$group_id);

        }


}
