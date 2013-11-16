<?php

class API_Base extends API implements iAPI
{
	public $format = "application/json";
	protected $user_id = null;
	protected $partner_id = null;
	public function __construct() {
		if(isset($_GET['PASS']) && isset($_GET['USER'])) {
			$user = NEnvironment::getUser();
      	try {
         	$user->login($_GET['USER'], $_GET['PASS']);
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
	 * @url	GET	/Login
	 */	
	public function getLogin() {
		if(!$this->isLoggedIn()) {
			throw new RestException('401',null);
		}
    return array('result'=>"OK");//Allready checked by common authorize function in rest server
	}
	
	/**
	 * Comment
	 *
	 * @url	GET	/Data
	 */	
	public function getData() {
		 if(!$this->isLoggedIn()) {
         throw new RestException('401',null);
      }
		if(!empty($_GET['type'])) {
			foreach($_GET['type'] as $t) {
				$types[] = $t;
			}
		} else {
			$types = array(ListerControlMain::LISTER_TYPE_USER,ListerControlMain::LISTER_TYPE_GROUP,ListerControlMain::LISTER_TYPE_RESOURCE);
		}
		$filter = array();
		if(!empty($_GET['filter'])) {
			$filter= $_GET['filter'];
		}
		return Administration::getData($types,$filter);
	}

	/**
    * Comment
    *
    * @url  GET   /UserData
    */
   public function getUserData() {
		 if(!$this->isLoggedIn()) {
         throw new RestException('401',null);
      }
		if(!empty($_GET['user_id'])) {
      	$user_id = $_GET['user_id'];
      }

		if(Auth::isAuthorized(1,$_GET['user_id']) == Auth::ADMINISTRATOR || Auth::isAuthorized(1,$_GET['user_id']) != Auth::UNAUTHORIZED) {
			$user = User::create($user_id);
			$data = $user->getUserData();
         if(empty($data)) {
            throw new RestException('402',null);
         }
         return $data;

		} else {
			throw new RestException('403',null);
		}
		
   }

	/**
    * Comment
    *
    * @url  GET   /GroupData
    */
   public function getGroupData() {
		 if(!$this->isLoggedIn()) {
         throw new RestException('401',null);
      }
		if(!empty($_GET['group_id'])) {
         $group_id = $_GET['group_id'];
      }
		if(Auth::isAuthorized(2,$_GET['group_id']) == Auth::ADMINISTRATOR || Auth::isAuthorized(2,$_GET['group_id']) != Auth::UNAUTHORIZED) {
      	$group = Group::create($group_id);
			$data = $group->getGroupData();
         if(empty($data)) {
            throw new RestException('402',null);
         }
         return $data;

		} else {
			throw new RestException('403',null);
		}

   }

	/**
    * Comment
    *
    * @url  GET   /ResourceData
    */
   public function getResourceData() {
		 if(!$this->isLoggedIn()) {
         throw new RestException('401',null);
      }
		if(!empty($_GET['resource_id'])) {
         $resource_id = $_GET['resource_id'];
      }
		if(Auth::isAuthorized(3,$_GET['resource_id']) == Auth::ADMINISTRATOR || Auth::isAuthorized(3,$_GET['resource_id']) != Auth::UNAUTHORIZED) {
      	$resource = Resource::create($resource_id);
			$data = $resource->getResourceData();
			if(empty($data)) {
				throw new RestException('402',null);
			}
      	return $data;
		} else {
			throw new RestException('403',null);
		}
   }

	 /**
    * Comment
    *
    * @url  GET   /Tags
    */
   public function getTags() {
       if(!$this->isLoggedIn()) {
         throw new RestException('401',null);
      }
		
		return Tag::getTreeArray();
   }

	
}
