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
 

/**
 * Base class for all the APIv2 classes
 */
class API {

  /**
   * Authorizes the user from the given username, password and partner key
   *
   * @return true | false
   */
  public function isAuthorized() {
    $user_id = User::getCurrentUserId();
    if(empty($user_id)) {
      return false;//Authorization in config_main failed
    }

    //Check partner key if it should be there
    $request = array_merge($_GET, $_POST);
    if(isset($request['USER']) && isset($request['PASS'])) {
      if(!isset($request['PARTNERKEY'])) {
        return false;//Partner key must be specified when being authorized by unencrypted password
      }
      $partner_id = Db::fetchSingle("SELECT `partner_id` FROM `partner` WHERE `partnerkey` = %s", $request['PARTNERKEY']);
      if(empty($partner_id)) {
        return false;//Partner key must be valid when being authorized by unencrypted password
      }
    }

    return true;
  }
}
