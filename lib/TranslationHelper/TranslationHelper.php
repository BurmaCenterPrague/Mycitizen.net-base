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


/**
 *	Replacement for native gettext library and helper for GettextTranslator class.
 *	Supports any language code.
 *	@param string|array $message
 *	$return string 
 */
function _t($message) {
	global $t;
	$message = (string) $message;
	if (!isset($t)) {
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}
		$t = new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/messages.mo', $language);
	}
	$args = func_get_args();
      if (count($args) > 1) {
         array_shift($args);
         $message = vsprintf($message, $args);
      } else {
      	$message = $args[0];
      }
	return $t->translate($message);
}

/**
 *	Same as _t() but strings come from tags.mo (if file exists)
 *	@param string|array $message
 *	$return string
 */
function _t_tags($message) {
	global $td;
	$message = (string) $message;
	$args = func_get_args();
	$domain = 'tags';
	if (!isset($td)) {
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}
		if (file_exists('../locale/' . $language . '/LC_MESSAGES/'.$domain.'.mo')) {
			$td = new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/'.$domain.'.mo', $language);
		} else {
			$td = new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/messages.mo', $language);
		}
	}

      if (count($args) > 1) {
         array_shift($args);
         $message = vsprintf($message, $args);
      } else {
      	$message = $args[0];
      }
	return $td->translate($message);
}