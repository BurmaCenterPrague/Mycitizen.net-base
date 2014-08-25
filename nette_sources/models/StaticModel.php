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
 

class StaticModel extends BaseModel {
	const SYSTEM_MESSAGE_WARNING_USER = 1;
	const SYSTEM_MESSAGE_FRIENDSHIPOFFER = 2;
	const SYSTEM_MESSAGE_FRIENDSHIPACCEPTED = 3;
	const SYSTEM_MESSAGE_FRIENDSHIPREJECTED = 4;
	const SYSTEM_MESSAGE_WARNING_GROUP = 5;
	const SYSTEM_MESSAGE_WARNING_RESOURCE = 6;
	const SYSTEM_MESSAGE_FRIENDSHIPTRERMINATED = 7;


   /**
    *	Checks if visitor with an IP address has already visited object. If not, creates entry of visit.
    *	@param int $type_id 1, 2 or 3
    *	@param int $object_id
    *	@param string $ip
    *	@return boolean
    */
	public static function ipIsRegistered($type_id,$object_id,$ip) {
      $result = dibi::fetchSingle("SELECT `ip_address` FROM `visits` WHERE `type_id` = %i AND `object_id` = %i AND `ip_address` = %s",$type_id,$object_id,trim($ip));
		if(!empty($result)) {
			return false;
		} else {
			dibi::query("INSERT INTO `visits`",array('type_id'=>$type_id,'object_id'=>$object_id,'ip_address'=>$ip));
			return true;
		}
   }


	/**
	 *	Sends a system message (warnings, friendship-related) to a user.
	 *	@param constant $message_type
	 *	@param int @from
	 *	@param int @to
	 *	@param string $message
	 *	@param int @object_type
	 *	@param int @object_id
	 *	@return void
	 */
	public static function sendSystemMessage($message_type, $from, $to, $message = null, $object_type = null, $object_id = null) {
		
		$send_email = false;
		
		$sender = User::create($from);
		if(!empty($sender)) {
			$sender_data = $sender->getUserData();
		} else {
			return false;
		}
		$recipient = User::create($to);
		if(!empty($recipient)) {
			$recipient_data = $recipient->getUserData();
		} else {
			return false;
		}
		
		// remember language
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language_prev = $session->language;

		$language = Language::getFlag($recipient_data['user_language']);
		if (empty($language)) {
			$language = 'en_US';
		}
		_t_set($language);
		
		$name = $recipient->getUserLogin($recipient->getUserId());
		
		$data = array();

		switch($message_type) {
			case self::SYSTEM_MESSAGE_FRIENDSHIPOFFER:
				$message_subject = sprintf(_t("User %s requested your friendship."), $sender_data['user_login']);
				$message_text = _t("Friendship request");
				$email_text = $message_subject;
				$data['resource_type'] = 10;
				break;
			case self::SYSTEM_MESSAGE_FRIENDSHIPACCEPTED:
				$message_subject = sprintf(_t("User %s accepted your friendship."), $sender_data['user_login']);
            	$message_text = _t("Friendship accepted");
            	$email_text = $message_subject;
            	$data['resource_type'] = 9;
				break;
			case self::SYSTEM_MESSAGE_FRIENDSHIPREJECTED:
				$message_subject = sprintf(_t("User %s rejected your friendship."), $sender_data['user_login']);
	            $message_text = _t("Friendship request rejected");
	            $email_text = $message_subject;
	            $data['resource_type'] = 9;
				break;
			case self::SYSTEM_MESSAGE_FRIENDSHIPTRERMINATED:
				$message_subject = sprintf(_t("User %s canceled your friendship."), $sender_data['user_login']);
	            $message_text = _t("Friendship canceled");
	            $email_text = $message_subject;
	            $data['resource_type'] = 9;
				break;
			case self::SYSTEM_MESSAGE_WARNING_USER:
				$message_subject = _t("System message: You've received a warning.");
	            $message_text = $message;
	            $email_text = $message_subject."\n\r".$message_text;
	            $data['resource_type'] = 9;
	            $send_email = true;
				break;
			case self::SYSTEM_MESSAGE_WARNING_GROUP:
				$message_subject = _t("System message: You've received a warning about your group.");
				$message_text = $message;
				$email_text = $message_subject."\n\r".$message_text;
				$data['resource_type'] = 9;
	            $send_email = true;
         	  	break;
			case self::SYSTEM_MESSAGE_WARNING_RESOURCE:
				$message_subject = _t("System message:  You've received a warning about your resource.");
         		$message_text = $message;
         		$email_text = $message_subject."\n\r".$message_text;
         		$data['resource_type'] = 9;
	            $send_email = true;
	            break;
			default:
				return false;
		}

    	$resource = Resource::create();
      
		$data['resource_author'] = $sender->getUserId();
		$data['resource_visibility_level'] = 3;
    	$data['resource_name'] = $message_subject;
    	$data['resource_data'] = json_encode(array('message_text'=>$message_text,'message_type'=>$message_type));
      
		$resource->setResourceData($data);
		$resource->save();

		if (!isset($object_id) || !isset($object_type)) {
			if (isset($sender_data['user_login'])) {
				$object_type = 1; $object_id = $from;
			} else {
				$object_type = 0; $object_id = 0;
			}
		}
		
		if ($send_email) {
			$email_text = sprintf(_t("Dear %s"), $name).",\n\r\n\r".$email_text;
			self::addCron(time()+60, 1, $to, $email_text, $object_type, $object_id);
		}
		
    	$resource->updateUser($recipient->getUserId(),array('resource_user_group_access_level'=>1));

		// return to previous language
		_t_set($language_prev);
	}


	/**
	 *	For compatibility with API_Base which receives from the mobile application only the email address.
	 *	@param string $email
	 *	@return boolean
	 */
	public static function isSpamEmail($email) {
		return self::isSpamSFS($email);
	}


	/**
	 *	Checks email address and optionally ip address against www.stopforumspam.com 
	 *	@param string $email
	 *	@param string $ip
	 *	@return boolean
	 */
	public static function isSpamSFS($email,$ip = '') {
		if (!NEnvironment::getVariable("CHECK_STOP_FORUM_SPAM")) {
			return true;
		}
		
		if (empty($ip)) {
			$contents = file_get_contents("http://www.stopforumspam.com/api?email=".$email."&f=json");
		} else {
			$contents = file_get_contents("http://www.stopforumspam.com/api?email=".$email."&ip=".$ip."&f=json");
		}
		$json = json_decode($contents,true);
		if (isset($json['success']) && $json['success']) {
			if ((isset($json['email']['appears']) && $json['email']['appears']) || (isset($json['ip']['appears']) && $json['ip']['appears'])) {
				return true;
			}
		}
		return false;
	}


	/**
	 *	Checks whether string is a valid email address.
	 *	@param string $email
	 *	@return boolean
	 */
	public static function validEmail($email) {
      $isValid = true;
      $atIndex = strrpos($email, "@");
      if (is_bool($atIndex) && !$atIndex) {
         $isValid = false;
      } else {
         $domain = substr($email, $atIndex+1);
         $local = substr($email, 0, $atIndex);
         $localLen = strlen($local);
         $domainLen = strlen($domain);
         if ($localLen < 1 || $localLen > 64) {
            // local part length exceeded
            $isValid = false;
         } else if ($domainLen < 1 || $domainLen > 255) {
            // domain part length exceeded
            $isValid = false;
         } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
            // local part starts or ends with '.'
            $isValid = false;
         } else if (preg_match('/\\.\\./', $local)) {
            // local part has two consecutive dots
            $isValid = false;
         } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
            // character not valid in domain part
            $isValid = false;
         } else if (preg_match('/\\.\\./', $domain)) {
            // domain part has two consecutive dots
            $isValid = false;
         } else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',str_replace("\\\\","",$local))) {
            // character not valid in local part unless 
            // local part is quoted
            if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local))) {
               $isValid = false;
            }
         }
         if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
            // domain not found in DNS
            $isValid = false;
         }
      }
      return $isValid;
   }


	/**
	 * Translates reference signs to readable form for mobile app messaging
	 *
	 * @param string $text
	 * @return string
	 */
	public static function messageAPI($text) {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Attr.EnableID', true);
		$config->set('Attr.IDBlacklistRegexp', '/^(?!((quoting_\d+)|(reply\d+))).*/'); // blacklisting all id attributes that don't start with "quoting_" followed by a number
		$config->set('HTML.Nofollow', true);
		$config->set('HTML.Allowed', 'h2,h3,h4,a[href|target|rel],strong,b,div,br,img[src|alt|height|width|style],dir,span[style],blockquote[id],ol,ul,li[type],pre,u,hr,code,strike,sub,sup,p[style],table,tr,td[colspan],th,iframe[src|width|height|frameborder]');
		$config->set('Attr.AllowedFrameTargets', array('_blank', '_top'));
		$config->set('HTML.SafeIframe', true);
		$config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www.youtube.com/embed/.*)|(player.vimeo.com/video/)%'); //allow YouTube and Vimeo
		$config->set('Filter.YouTube', true);
		$purifier = new HTMLPurifier($config);
		$text = $purifier->purify($dirty_html);
		
		// converting references
		$pattern = array(
			'/(\s|^|>)(@{1,3}[^@\s<>"\'!?,:;()]+@?)([!?.,:;()\Z\s<])/u',
			'/(\s|^|\W)(#[^#\s<>"\'!?,:;()]+#?)([!\?\,:;()\Z\s<])/u'
		);
		$text = preg_replace_callback(
			$pattern,
			function($lighter){
				$title = '';
				$lighter[2] = trim($lighter[2]);
				if (preg_match("/^@([0-9]+)@?/", $lighter[2], $ids) === 1) {
					if (Auth::isAuthorized(1, $ids[1]) > Auth::UNAUTHORIZED) {
						$label = User::getFullName($ids[1]);
						$link = 'user/?user_id='.$ids[1];
						$title = _t("go to '%s'", $label);
					}
				} elseif (preg_match("/^@@([0-9]+)@?/", $lighter[2], $ids) === 1) {
					if (Auth::isAuthorized(2, $ids[1]) > Auth::UNAUTHORIZED) {
						$label = Group::getName($ids[1]);
						$link = 'group/?group_id='.$ids[1];
						$title = _t("go to '%s'", $label);
					}
				} elseif (preg_match("/^@@@([0-9]+)@?/", $lighter[2], $ids) === 1) {
					if (Auth::isAuthorized(3, $ids[1]) > Auth::UNAUTHORIZED) {
						$label = Resource::getName($ids[1]);
						$link = 'resource/?resource_id='.$ids[1];
						$title = _t("go to '%s'", $label);
					}
				}
				If (!isset($label) || !isset($link)) {
					$label = str_replace('_',' ',$lighter[2]);
					$link = '?do=search&string='.urlencode($lighter[2]);
					$title = _t("search for '%s'", $label);
				}
				if (!isset($lighter[3])) {
					$lighter[3] = '';
				}
				return $lighter[1].'<a href="'.NEnvironment::getVariable("URI").'/'.$link.'"><b>'.$title.'</b></a>'.$lighter[3];
			},
			$text);
		return $text;
	}


	/**
	 * applies HTML purification and referrer conversion
	 *
	 * @param string $text
	 * @return string
	 */
	public static function purify_and_convert($dirty_html) {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Attr.EnableID', true);
		$config->set('Attr.IDBlacklistRegexp', '/^(?!((quoting_\d+)|(reply\d+))).*/'); // blacklisting all id attributes that don't start with "quoting_" followed by a number
		$config->set('HTML.Nofollow', true);
		$config->set('HTML.Allowed', 'h2,h3,h4,a[href|target|rel],strong,b,div,br,img[src|alt|height|width|style],dir,span[style],blockquote[id],ol,ul,li[type],pre,u,hr,code,strike,sub,sup,p[style],table,tr,td[colspan],th,iframe[src|width|height|frameborder]');
		$config->set('Attr.AllowedFrameTargets', array('_blank', '_top'));
		$config->set('HTML.SafeIframe', true);
		$config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www.youtube.com/embed/.*)|(player.vimeo.com/video/)%'); //allow YouTube and Vimeo
		$config->set('Filter.YouTube', true);
		$purifier = new HTMLPurifier($config);
		$text = $purifier->purify($dirty_html);
		
		// converting references
		$pattern = array(
			'/(\s|^|>)(@{1,3}[^@\s<>"\'!?,:;()]+@?)([!?.,:;()\Z\s<])/u',
			'/(\s|^|\W)(#[^#\s<>"\'!?,:;()]+#?)([!\?\,:;()\Z\s<])/u'
		);
		$text = preg_replace_callback(
			$pattern,
			function($lighter){
				$title = '';
				$lighter[2] = trim($lighter[2]);
				if (preg_match("/^@([0-9]+)@?/", $lighter[2], $ids) === 1) {
					if (Auth::isAuthorized(1, $ids[1]) > Auth::UNAUTHORIZED) {
						$label = User::getFullName($ids[1]);
						$link = 'user/?user_id='.$ids[1];
						$title = _t("go to '%s'", $label);
					}
				} elseif (preg_match("/^@@([0-9]+)@?/", $lighter[2], $ids) === 1) {
					if (Auth::isAuthorized(2, $ids[1]) > Auth::UNAUTHORIZED) {
						$label = Group::getName($ids[1]);
						$link = 'group/?group_id='.$ids[1];
						$title = _t("go to '%s'", $label);
					}
				} elseif (preg_match("/^@@@([0-9]+)@?/", $lighter[2], $ids) === 1) {
					if (Auth::isAuthorized(3, $ids[1]) > Auth::UNAUTHORIZED) {
						$label = Resource::getName($ids[1]);
						$link = 'resource/?resource_id='.$ids[1];
						$title = _t("go to '%s'", $label);
					}
				}
				If (!isset($label) || !isset($link)) {
					$label = str_replace('_',' ',$lighter[2]);
					$link = '?do=search&string='.urlencode($lighter[2]);
					$title = _t("search for '%s'", $label);
				}
				if (!isset($lighter[3])) {
					$lighter[3] = '';
				}
				return $lighter[1].'<a href="'.NEnvironment::getVariable("URI").'/'.$link.'" title="'.$title.'">'.$label.'</a>'.$lighter[3];
			},
			$text);
		return $text;
	}


	/**
	 *	Turns ASCII smylies into images
	 *	@param string $text
	 *	@return string
	 *
	 */
	public static function imagifySmileys($text)
	{
		$smileys = array(
				':-o' => 'omg_smile.png',
				':-O' => 'omg_smile.png',
				':-)' => 'regular_smile.png',
				':)' => 'regular_smile.png',
				';-)' => 'wink_smile.png',
				';)' => 'wink_smile.png',
				':-(' => 'sad_smile.png',
				':(' => 'sad_smile.png',
				':-D' => 'teeth_smile.png',
				':D' => 'teeth_smile.png',
				':-P' => 'tongue_smile.png',
				':P' => 'tongue_smile.png',
				'(n)' => 'thumbs_down.png',
				'(y)' => 'thumbs_up.png',
				'8-)' => 'shades_smile.png',
				'<3' => 'heart.png'
			);
		array_walk($smileys, function(&$value, $key){
			$value='<img src="'.NEnvironment::getVariable("URI").'/js/ckeditor/plugins/smiley/images/'.$value.'"/>';
		});
		return strtr($text, $smileys);	
	}


   /**
    *	Sends email with PHPMailer
    *	@param string $to_name Name of recipient
    *	@param string $to_email Email address of recipient
    *	@param string $subject Subject in email
    *	@param string $text Main text in body
    *	@param string $template Optional file name in folder /templates/Email/
    *	@param string $additional_footer_text Optional additional text for footer
    *	@return bool
    */
	public static function send_email($to_name, $to_email, $subject, $text, $additional_footer_text = null, $template = 'default.html') {
		require_once(LIBS_DIR.'/PHPMailer/PHPMailerAutoload.php');
 		
 		$deployment_name = NEnvironment::getVariable("PROJECT_NAME");
 		$from_email = Settings::getVariable("from_email");
 		$from_name = $deployment_name;
 		$uri = NEnvironment::getVariable("URI");
 		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
 		$language = $session->language;
		$footer_text = _t("You receive this email because you have signed up at %s.", $uri);
 		if (isset($additional_footer_text)) {
 			$footer_text .= "\n". $additional_footer_text;
 		}

 		// check if text is html formatted
		if (strip_tags($text) != $text) {
			// html
	 		$text .= "<br>"._t('Yours,')."<br><br>".$deployment_name."<br><br>";
			$html_text = $text;
			// create plain text version
			$text = preg_replace("#</td>#", "$0\t", $text);
			$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
			$text = preg_replace("/$regexp/siU", "$3 ($2)", $text);
		} else {
	 		$text .= "\r\n\r\n"._t('Yours,')."\r\n\r\n".$deployment_name."\r\n\r\n";
			// convert line breaks to <br> tag
			$html_text = nl2br($text);
			$html_text = self::markup_links($html_text);
			// convert other entities to corresponding tags
			$html_text = preg_replace("/\t/", " &nbsp;&nbsp;&nbsp;&nbsp;", $html_text);
			$html_text = preg_replace("/----/", "<hr>", $html_text);
			$html_text = self::make_links_clickable($html_text);
		}

 		// check if text is html formatted
		if (strip_tags($footer_text) != $footer_text) {
			// html
			$html_footer_text = $footer_text;
		} else {
			// convert line breaks to <br> tags
			$html_footer_text = nl2br($footer_text);
			$html_footer_text = self::markup_links($html_footer_text);
			// convert tabs to &nbsp;
			$html_footer_text = preg_replace("/\t/", " &nbsp;&nbsp;&nbsp;&nbsp;", $html_footer_text);
			$html_footer_text = preg_replace("/----/", "<hr>", $html_footer_text);
			$html_footer_text = self::make_links_clickable($html_footer_text);
		}

		$body = file_get_contents(APP_DIR.'/templates/Email/'.$template);

		$replace = array(
				'%FROM_EMAIL%' => $deployment_name,
				'%FROM_NAME%' => $from_name,
				'%DEPLOYMENT_NAME%' => $deployment_name,
				'%SUBJECT%' => $subject,
				'%URI%' => $uri,
				'%LOGO_URI%' => $uri.'/images/logo.png',
				'%TEXT%' => $html_text,
				'%FOOTER_TEXT%' => $html_footer_text
			);
		$body = strtr($body, $replace);

		$body = self::imagifySmileys($body);

		$mail = new PHPMailer;	

		if (NEnvironment::getVariable("SMTP_HOST")) {
			$mail->isSMTP();
			$mail->Host = NEnvironment::getVariable("SMTP_HOST");
			$mail->SMTPAuth = true;
			$mail->Username = NEnvironment::getVariable("SMTP_USERNAME");
			$mail->Password = NEnvironment::getVariable("SMTP_PASSWORD");
			$mail->SMTPSecure = NEnvironment::getVariable("SMTP_ENCRYPTION"); // 'tls' or 'ssl'
		}


		$mail->AltBody = strip_tags($text."\r\n\r\n".$footer_text);
		$mail->SetFrom($from_email, $from_name);
		$mail->AddAddress($to_email, $to_name);
		$mail->Subject = $subject;
		$mail->MsgHTML($body);
		$mail->CharSet = mb_detect_encoding($body); //'UTF-8';
//		Hint for future extensions:
//		$mail->addAttachment("email/attachment.pdf");

		if($mail->Send()) {
		  return true;
		} else {
		  return false; // echo "Mailer Error: " . $mail->ErrorInfo;
		}
	}


	/**
	 *	Converts links in markup format to html.
	 *	formats:
	 *		[abc] (http://www.example.com)
	 *		"abc" (http://www.example.com)
	 *	@param string $input
	 *	@return string
	 */
	public static function markup_links($input) {
		$input = preg_replace('/\[([^\]]+)\]\s*\((https?:\/\/[^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $input); // [abc] (http://www.example.com)
		$input = preg_replace('/"([^"]+)"\s*\((https?:\/\/[^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $input); // "abc" (http://www.example.com)
		return $input;
	}


   /**
    *	Converts plain-text links (with prepended protocol) to clickable html.
    *	@param string $input
    *	@return string
    */
	public static function make_links_clickable($input) {
		// make links clickable
		// credits: http://stackoverflow.com/a/1188652 (modified)
		$output = '';
		$validTlds = array_fill_keys(explode(" ", ".aero .asia .biz .cat .com .coop .edu .gov .info .int .jobs .mil .mobi .museum .name .net .org .pro .tel .travel .ac .ad .ae .af .ag .ai .al .am .an .ao .aq .ar .as .at .au .aw .ax .az .ba .bb .bd .be .bf .bg .bh .bi .bj .bm .bn .bo .br .bs .bt .bv .bw .by .bz .ca .cc .cd .cf .cg .ch .ci .ck .cl .cm .cn .co .cr .cu .cv .cx .cy .cz .de .dj .dk .dm .do .dz .ec .ee .eg .er .es .et .eu .fi .fj .fk .fm .fo .fr .ga .gb .gd .ge .gf .gg .gh .gi .gl .gm .gn .gp .gq .gr .gs .gt .gu .gw .gy .hk .hm .hn .hr .ht .hu .id .ie .il .im .in .io .iq .ir .is .it .je .jm .jo .jp .ke .kg .kh .ki .km .kn .kp .kr .kw .ky .kz .la .lb .lc .li .lk .lr .ls .lt .lu .lv .ly .ma .mc .md .me .mg .mh .mk .ml .mm .mn .mo .mp .mq .mr .ms .mt .mu .mv .mw .mx .my .mz .na .nc .ne .nf .ng .ni .nl .no .np .nr .nu .nz .om .pa .pe .pf .pg .ph .pk .pl .pm .pn .pr .ps .pt .pw .py .qa .re .ro .rs .ru .rw .sa .sb .sc .sd .se .sg .sh .si .sj .sk .sl .sm .sn .so .sr .st .su .sv .sy .sz .tc .td .tf .tg .th .tj .tk .tl .tm .tn .to .tp .tr .tt .tv .tw .tz .ua .ug .uk .us .uy .uz .va .vc .ve .vg .vi .vn .vu .wf .ws .ye .yt .yu .za .zm .zw .xn--0zwm56d .xn--11b5bs3a9aj6g .xn--80akhbyknj4f .xn--9t4b11yi5a .xn--deba0ad .xn--g6w251d .xn--hgbk6aj7f53bba .xn--hlcj6aya9esc7a .xn--jxalpdlp .xn--kgbechtv .xn--zckzah .arpa"), true);

		$position = 0;
		$rexProtocol = '(https?://)'; //'(https?://)?'
		$rexDomain   = '((?:[-a-zA-Z0-9]{1,63}\.)+[-a-zA-Z0-9]{2,63}|(?:[0-9]{1,3}\.){3}[0-9]{1,3})';
		$rexPort     = '(:[0-9]{1,5})?';
		$rexPath     = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
		$rexQuery    = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
		$rexFragment = '(#[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
		while (preg_match("{([^\"'>])$rexProtocol$rexDomain$rexPort$rexPath$rexQuery$rexFragment(?=[?.!,;:]?(\s|$|\)|\.))}i", $input, $match, PREG_OFFSET_CAPTURE, $position))
		{
			list($url, $urlPosition) = $match[0];

			$output .= substr($input, $position, $urlPosition - $position);

			$domain = $match[3][0];
			$port   = $match[4][0];
			$path   = $match[5][0];
			$query	= $match[6][0];
			
			$tld = strtolower(strrchr($domain, '.'));
			if (preg_match('{\.[0-9]{1,3}}', $tld) || isset($validTlds[$tld]))
			{
				$completeUrl = $match[2][0] ? $url : "http://$url";
				$output .= $match[1][0].sprintf('<a href="%s" target="_blank">%s</a>', htmlspecialchars($completeUrl), htmlspecialchars("$domain$port$path$query"));
			}
			else
			{
				$output .= htmlspecialchars($url);
			}
			$position = $urlPosition + strlen($url);
		}
		$output .= substr($input, $position);
		
		return $output;
	}

	
	/**
	 *	Turns beginning of HTML text into a short intro in plain text.
	 *	@param string $html
	 *	@return string
	 */
	public static function makeAbstract($html)
	{
		$max_length = 70;
		$search_pattern = array(
			"/<br\s*\/?>/",
			"/<\/p\s*>/"
			);
		$end_of_word = array(
			"\n",
			" ",
			".",
			",",
			";",
			":",
			"(",
			")",
			"'",
			'"',
			);
			
		$html = self::purify_and_convert($html);
		
		$plain_text = strip_tags(preg_replace($search_pattern, "\n", $html));
		if (mb_strlen($plain_text) > $max_length) {
			foreach ($end_of_word as $s) {
				$length = strpos($plain_text, $s, $max_length);
				if ($length !== false) break;
			}
			
			if ($length === false) {
				$length = $max_length;
			}
			$plain_text = substr($plain_text, 0, $length).' ...';
		}
		return $plain_text;
	}


	/**
	 *	Adds the user's IP address to the list of failed login attempts.
	 *	@param void
	 *	@return boolean
	 */
	public static function addLoginFailure()
	{
		$ip = ip2long(self::getIpAddress());
		
		$data = array('ip' => $ip, 'time' => time(), 'event' => 1);
		dibi::query('INSERT INTO `failed_logins`', $data);

		$result = dibi::fetchSingle('SELECT COUNT(`failed_logins_id`) FROM `failed_logins` WHERE `event` = 1 AND `ip` = %s AND `time` > %i ', $ip, time()-Settings::getVariable('ip_failure_time_minutes')*60);
		
		if ($result > Settings::getVariable('ip_max_failed_logins')) {
			$data = array('ip' => $ip, 'time' => time(), 'event' => 2);
			dibi::query('INSERT INTO `failed_logins`', $data);
		}
	}


	/**
	 *	Checks if the used IP address is known for more than x failed login attempts in the past hour. Returns true if and only if all is OK and user may proceed.
	 *	@param void
	 *	@return boolean
	 */
	public static function checkLoginFailures()
	{
		$ip = ip2long(self::getIpAddress());

		$result = dibi::fetchSingle('SELECT `failed_logins_id` FROM `failed_logins` WHERE `event` = 2 AND `ip` = %s AND `time` > %i  LIMIT 1', $ip, time()-Settings::getVariable('ip_blocking_time_hours')*3600);
		if (!empty($result)) {
			return false;
		} else {
			return true;
		}	
	}
	
	
	/**
	 *	Returns the visitor's IP address, accounting for possible proxies.
	 *	supported proxies:
	 *		Cloudflare
	 *	@param void
	 *	@return boolean|string
	 */
	public static function getIpAddress()
	{
		if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
			// check if IP is actually from Cloudflare
			if (file_exists(LIBS_DIR.'/proxy_ips/cloudflare.txt')) {
				// create array with valid IPs
				$valid_ips = array();
				$rows = explode("\n", file_get_contents(LIBS_DIR.'/proxy_ips/cloudflare.txt'));
				foreach ($rows as $row) {
					if (substr($row,0,1) == '#') continue; // skip comments
					if (preg_match('#^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$#', $row, $matches)) {
						 // one address
						$valid_ips[] = $matches[1];
					} elseif (preg_match('#^(\d{1,3}\.\d{1,3}\.\d{1,3}\.)(\d{1,3})\/(\d{1,3})$#', $row, $matches)) {
						// range of addresses
						for ($i=$matches[2]; $i<=$matches[3]; $i++) {
							$valid_ips[] = $matches[1].$i;
						}
					}
				}
				if (in_array($_SERVER["REMOTE_ADDR"], $valid_ips)) {
					$_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
				} else {
					return false;
				}
			} else {
				// having no data file to check, letting user pass
				$_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
			}
		}
		return $_SERVER['REMOTE_ADDR'];
	}


	/**
	 *	Adds a new entry to the time logger, or returns the log
	 *
	 *	@param string $text
	 *	@return string|void
	 */
	 public static function logTime($text = null)
	 {
		static $time_log_start = null;
		static $time_log = '';
		static $last_time = 0.0;
		
		
		if (!NEnvironment::getVariable("LOG_TIME")) return;

		if (empty($text)) return "\n<!--\nTime Log:".htmlspecialchars($time_log)."\n-->\n";

		if ($time_log_start == null) $time_log_start = microtime();

		$time = microtime() - $time_log_start;

		$time_log .= sprintf("\n%.3f: %s (+%.3f)", $time, $text, $last_time-$time);
		
		$last_time = $time;
		
	 }


}
