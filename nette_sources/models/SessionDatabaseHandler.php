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
 

class SessionDatabaseHandler {


   /**
    *	@todo ### Description
    *	@param
    *	@return
    */
   public static function open() {

   }


   /**
    *	@todo ### Description
    *	@param
    *	@return
    */
   public static function close() {

   }


   /**
    *	@todo ### Description
    *	@param
    *	@return
    */
   public static function read($id) {
   	$session_data = dibi::fetchSingle("SELECT `session_data` FROM `phpsessions` WHERE `id` = %s",$id);
      if(!empty($session_data)) {
         return $session_data;
      }
   }


   /**
    *	@todo ### Description
    *	@param
    *	@return
    */
   public static function write($id, $data) {
      try {
         dibi::begin();

         dibi::query("DELETE FROM `phpsessions` WHERE `id` = %s",$id); 
         $session_data = array(
                        'id'=>$id,
                        'session_expires'=>time(),
                        'session_data'=>$data
                      );
         dibi::query("INSERT INTO `phpsessions`",$session_data);
      } catch (Exception $e) {
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
   public static function destroy($id) {
      dibi::query("DELETE FROM `phpsessions` WHERE `id` = %s",$id);
   }


   /**
    *	@todo ### Description
    *	@param
    *	@return
    */
   public static function clean($maxlifetime) {
      $old = (time() - $maxlifetime);
      dibi::query("DELETE FROM `phpsessions` WHERE `session_expires` < %i",$old);
   }
}
?>