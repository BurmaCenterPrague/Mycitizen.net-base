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
 

class SessionDatabaseHandler {

   public static function open() {

   }

   public static function close() {

   }

   public static function read($id) {
   	$session_data = dibi::fetchSingle("SELECT `session_data` FROM `phpsessions` WHERE `id` = %s",$id);
      if(!empty($session_data)) {
         return $session_data;
      }
   }

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

   public static function destroy($id) {
      dibi::query("DELETE FROM `phpsessions` WHERE `id` = %s",$id);
   }

   public static function clean($maxlifetime) {
      $old = (time() - $maxlifetime);
      dibi::query("DELETE FROM `phpsessions` WHERE `session_expires` < %i",$old);
   }
}
?>