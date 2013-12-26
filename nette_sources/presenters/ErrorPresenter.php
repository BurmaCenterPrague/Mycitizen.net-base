<?php
/**
 * mycitizen.net - Open source social networking for civil society
 *
 * @version 0.3 beta
 *
 * @author http://mycitizen.org
 * @copyright  Copyright (c) 2013 Burma Center Prague (http://www.burma-center.org)
 * @link http://mycitizen.net
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3
 *
 * @package mycitizen.net
 */
 
class ErrorPresenter extends BasePresenter
{

	/**
	 * @param  Exception
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

		$this->template->setTranslator(new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/messages.mo', $language));

	
		if ($this->isAjax()) { // AJAX request? Just note this error in payload.
			$this->payload->error = TRUE;
			$this->terminate();

		} elseif (isset($exception)) {
			$code = $exception->getCode();

			$this->setView(in_array($code, array(403, 404, 405, 410, 500)) ? $code: '4xx');
			NDebug::processException($exception, false);

		} else {
			$this->setView('500');
			NDebug::processException($exception, false);
		}
	}

	protected function isAccessible()
	{
		return true;
	}

}