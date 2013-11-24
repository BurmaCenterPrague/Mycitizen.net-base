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
 


class UserAuthenticator extends BaseModel implements IAuthenticator {
    public function authenticate(array $credentials) {
        $username = $credentials['username'];
        $password = $credentials['password'];
        
        $result = dibi::fetchAll("SELECT * FROM `user` WHERE `user_login` = %sN", $username);
		
       	if (!$result || !$result[0]) {
           	throw new NAuthenticationException("User '".$username."' not found.", self::IDENTITY_NOT_FOUND);
       	}

		require(LIBS_DIR.'/Phpass/PasswordHash.php');
		$hasher = new PasswordHash(8, false);
		
		if( !$hasher->CheckPassword($password, $result[0]->user_password)) {
           	throw new NAuthenticationException("Your password does not match.", self::INVALID_CREDENTIAL);
        }
        return User::create($result[0]->user_id);
    }
}