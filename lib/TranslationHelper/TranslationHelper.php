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
function _t_set($language) {
	global $t;
	if (empty($language)) {
		$language = 'en_US';
	}
	$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
	$session->language = $language;
	$t = new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/messages.mo', $language);
	return $t->locale;
}

/**
 *	Replacement for native gettext library and helper for GettextTranslator class.
 *	Supports any language code.
 *	@param string|array $message
 *	$return string 
 */
function _t($message) {
	global $t;
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
	return call_user_func_array(array($t,"translate"),$args);
}

/**
 *	Same as _t() but strings come from tags.mo (if file exists)
 *	@param string|array $message
 *	$return string
 */
function _t_tags($message) {
	global $td;
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

	return call_user_func_array(array($td,"translate"),$args);
}