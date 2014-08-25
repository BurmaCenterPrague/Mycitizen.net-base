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
 


class UserAuthenticator extends BaseModel implements IAuthenticator {

/**
 *	@todo ### Description
 *	@param
 *	@return
*/
    public function authenticate(array $credentials) {
        $username = $credentials['username'];
        $password = $credentials['password'];
        
        $result = dibi::fetchAll("SELECT * FROM `user` WHERE `user_login` = %sN", $username);
		
       	if (!$result || !$result[0]) {
           	throw new NAuthenticationException(_t("User %s not found.", $username), self::IDENTITY_NOT_FOUND);
       	}

		if ($credentials['extra'] != 'facebook') {		
			require(LIBS_DIR.'/Phpass/PasswordHash.php');
			$hash = new PasswordHash(8, false);
		
			if( !$hash->CheckPassword($password, $result[0]->user_password)) {
				Activity::addActivity(Activity::LOGIN_FAILED, $result[0]->user_id, 1);
				StaticModel::addLoginFailure();
				throw new NAuthenticationException(_t("The password is wrong."), self::INVALID_CREDENTIAL);
			}
        }
        return User::create($result[0]->user_id);
    }

}
