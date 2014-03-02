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
 

class ExternalAuth extends BaseModel
{

	public function __construct() {
	}
	
	
	/**
	 *	Authentication via Facebook API. Returns $url to FB API for login, true if login succeeded or false if login not yet completed.
	 *	@param bool $captcha TRUE: captcha has been answered correctly, FALSE: no captcha has been answered
	 *	@return string | bool
	 */
	public static function facebook($captcha = false) {
  
		$facebook_app_id = NEnvironment::getVariable("FACEBOOK_APP_ID");
		$facebook_app_secret = NEnvironment::getVariable("FACEBOOK_APP_SECRET");
		$facebook_app_url = NEnvironment::getVariable("URI");
		$fb_logout_url = '';
		$fb_login_url = '';
			
		if (!empty($facebook_app_id) && !empty($facebook_app_secret) && !empty($facebook_app_url)) {
			
			include_once LIBS_DIR."/Facebook/facebook.php";
			
			$facebook = new Facebook( array(
				'appId'		=> $facebook_app_id,
				'secret'	=> $facebook_app_secret,
				)
			);

			$fb_login_url = $facebook->getLoginUrl(array(
				'scope'		=> 'email',
				'redirect_uri'	=> $facebook_app_url.'/signin/'
			));

			$fb_user = $facebook->getUser();

			// check if app is authorized
			if ($fb_user) {
				try{
					// Proceed knowing you have a logged in user who's authenticated.
					$user_profile = $facebook->api('/me');
				} catch(FacebookApiException $e) {
					$fb_user = '';
				}
			}
			
			if ($fb_user) {
				$fb_logout_url = $facebook->getLogoutUrl(array(
					'next'	=> $facebook_app_url
				));
				define('FB_LOGOUT_URL', $fb_logout_url);

				// FB user needs email address (no phone-only signups admitted)
				if (empty($user_profile['email'])) {
					$this->flashMessage(_t("Your Facebook account doesn't have an email address."), 'error');
					$this->redirect('Homepage:default');
				}

				// check if username is known (we use same username as on Facebook)
				if (User::loginExists($user_profile['username'])) {
				
					// check if email is known
					if ($user_profile['username'] == User::userloginFromEmail($user_profile['email'])) {
						// name and email match -> let's assume that user is registered (In Facebook we trust.)
						if (Settings::getVariable('sign_in_disabled')) {
							$this->flashMessage(_t("Sign in is disabled. Please try again later."), 'error');
							$this->redirect("Homepage:default");
						}
						$user_e = NEnvironment::getUser();
						$user = User::getEmailOwner($user_profile['email']);
						if (isset($user) && $user->isActive() && $user->isConfirmed()) {
							$user_e->login($user_profile['username'], NULL, 'facebook');	
							$user->setLastActivity();
							return true;
						} else {
							// user not yet confirmed
							$question = Settings::getVariable('signup_question');
					
							if ($question && !$captcha) {
								$this->flashMessage(_t("The administrator asks you to answer a security question before you can enter."));
								$this->redirect('User:captcha');
							} else {
								$user->finishExternalRegistration();
								$user->registerFirstLogin();
								$user_e->login($user_profile['username'], '', 'facebook');	
								$user->setLastActivity();
								return true;
							}
						}
					} else {
						// username exists, but email doesn't match
						$this->flashMessage(_t("User with the same name already exists."), 'error');
						$this->redirect('Homepage:default');
					}
				
				} else {
					// user is new
					// check email (same user cannot log in with same email with two different methods)
					if (User::emailExists($user_profile['email'])) {
						$this->flashMessage(_t("Email is already registered with another account."), 'error');
						$this->redirect('Homepage:default');
					}
					
					// spam check
					if (StaticModel::isSpamSFS($user_profile['email'], '')) {
						$this->flashMessage(_t("Your email or IP is known at www.stopforumspam.com as spam source and was blocked."), 'error');
						$this->redirect('Homepage:default');
					}

					// check the security question - skipped if already answered
					if (!$captcha) {
						$question = Settings::getVariable('signup_question');
					
						if ($question) {
							$this->flashMessage(_t("The administrator asks you to answer a security question before you can enter."));
							$this->redirect('User:captcha');
						}
					}
					
					if (Settings::getVariable('sign_up_disabled')) {
						$this->flashMessage(_t("Sign up is disabled. Please try again later."), 'error');
						$this->redirect("Homepage:default");
					} 
					$user = User::create();
					$values['user_login'] = $user_profile['username'];
					$values['user_email'] = $user_profile['email'];
					$values['user_name'] = $user_profile['first_name'];
					$values['user_surname'] = $user_profile['last_name'];
					$values['user_password'] = User::encodePassword(md5(rand())); // create dummy password with sufficient security
					$values['user_hash'] = User::generateHash();
					$values['user_url'] = $user_profile['link'];
					$values['user_visibility_level'] = 2; // by default all signups from Facebook are hidden from the world
					
					// find location
					if (isset($user_profile['location']['name']) && !empty($user_profile['location']['name']))
						$fb_location = $user_profile['location']['name'];					
					elseif (isset($user_profile['hometown']['name']) && !empty($user_profile['hometown']['name']))
						$fb_location = $user_profile['hometown']['name'];
					
					if (isset($fb_location)) {
						$location = $this->lookup_address($fb_location);
						$values['user_position_y'] = $location['longitude'];
						$values['user_position_x'] = $location['latitude'];
					}
										
					// retrieve image
					$fb_img = file_get_contents( "https://graph.facebook.com/".$user_profile['username']."/picture?type=large&height=200&width=160" );
					$avatar_w = 160;
					$avatar_h = 200;
					$avatar = base64_encode(NImage::fromString($fb_img)->resize($avatar_w, $avatar_h)->sharpen()->toString(IMAGETYPE_JPEG,90));

					// make icon and large_icon
					$large_icon_w = 40;
					$large_icon_h = 50;
					$icon_w = 20;
					$icon_h = 25;
					$large_icon = base64_encode(NImage::fromString($fb_img)->resize($large_icon_w, $large_icon_h)->sharpen()->toString(IMAGETYPE_JPEG,90));
					$icon = base64_encode(NImage::fromString($fb_img)->resize($icon_w, $icon_h)->sharpen()->toString(IMAGETYPE_JPEG,90));
					$values['user_portrait'] = $avatar;
					$values['user_largeicon'] = $large_icon;
					$values['user_icon'] = $icon;
					$user->setUserData($values);
					$user->save();
					// generate cache
					User::saveImage($user->getUserId());
			
					$user->setRegistrationDate();
					$user->finishExternalRegistration();
					$user_e = NEnvironment::getUser();
					$user_e->login($user_profile['username'], '', 'facebook');
					$user_id = $user->getUserId();
					Activity::addActivity(Activity::USER_JOINED, $user_id, 1);
					$user->registerFirstLogin();
					$user->setLastActivity();

					$this->flashMessage(_t("Success!"));
					$this->flashMessage(_t("Please check now your profile and enter a description and tags."));
					
					$this->redirect("User:edit", array('user_id' => $user_id, 'registration' => 'facebook'));
				}
				
			} else {
				// user has not authorized Facebook, or is not logged in
				if ($captcha) {
					// If after the captcha screen the Facebook permission got lost
					NEnvironment::getSession()->destroy();
					$this->redirect("Homepage:default");
				}
				return $fb_login_url;
			}
			
		}
	}

}