<?php

class UserAuthenticator extends BaseModel implements IAuthenticator {
    public function authenticate(array $credentials) {
        $username = $credentials['username'];
        $password = $credentials['password'];
        
        $result = dibi::fetchAll("SELECT * FROM `user` WHERE `user_login` = %sN", $username);
		
       	if (!$result || !$result[0]) {
           	throw new NAuthenticationException("User '".$username."' not found.", self::IDENTITY_NOT_FOUND);
       	}

        if ($result[0]->user_password !== User::encodePassword($password)) {
           	throw new NAuthenticationException("Your password does not match.", self::INVALID_CREDENTIAL);
        }
        return User::create($result[0]->user_id);
    }
}
