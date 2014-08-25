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
 
class ErrorPresenter extends BasePresenter
{

	/**
	 * Processes errors
	 * @param Exception
	 * @return void
	 * from Nette Framework
	 */
	public function renderDefault($exception)
	{
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}

		$this->template->setTranslator(new GettextTranslator(LOCALE_DIR . '/' . $language . '/LC_MESSAGES/messages.mo', $language));

	
		if ($this->isAjax()) { // AJAX request? Just note this error in payload.
			$this->payload->error = TRUE;
			$this->terminate();

		} elseif (isset($exception)) {
			$code = $exception->getCode();
			$this->setView(in_array($code, array(403, 404, 500)) ? $code: '4xx'); //405, 410, 
			NDebug::processException($exception, false);

		} else {
			$this->setView('500');
			NDebug::processException($exception, false);
		}
	}

	/**
	 *	Makes sure that the error pages are visible without login
	 *	@param void
	 *	@return boolean true
	 */
	protected function isAccessible()
	{
		return true;
	}

}