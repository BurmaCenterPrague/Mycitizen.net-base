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
 


final class HomepagePresenter extends BasePresenter
{
	public function startup()
	{
		parent::startup();
	}
	
	public function actionDefault()
	{
		// detecting mobile devices
		require_once LIBS_DIR.'/Mobile-Detect/Mobile_Detect.php';
		$detect = new Mobile_Detect;
		if ($detect->isMobile()) $this->template->mobile = true;
		unset($detect);
		
		$this->template->tooltip_position = 'bottom center';
	}
	
	protected function createComponentFilter($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$options = array(
			'components' => array(
				'userHomepage',
				'groupHomepage',
				'resourceHomepage'
			),
			'refresh_path' => 'Homepage:default',
			'include_map' => true,
			'include_name' => true,
			'include_language' => true,
			'include_type' => true,
			'include_tags' => true,
			'include_pairing' => true
		);
		if (!empty($user)) {
			$options['include_suggest'] = true;
		}
		$control = new ExternalFilter($this, $name, $options);
		return $control;
	}
	
	/**
	*	top items
	*/
	protected function createComponentUserHomepage($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$options = array(
			'itemsPerPage' => 10,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'show_extended_columns' => true,
				'front_page' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	*	top items
	*/	
	protected function createComponentGroupHomepage($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$options = array(
			'itemsPerPage' => 10,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_GROUP
			),
			'template_body' => 'ListerControlMain_groups.phtml',
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'show_extended_columns' => true,
				'front_page' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}

	/**
	*	top items
	*/
	protected function createComponentResourceHomepage($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$options = array(
			'itemsPerPage' => 10,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'ListerControlMain_resources.phtml',
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'show_extended_columns' => true,
				'front_page' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	/**
	*	list friends on home page when logged in
	*/
	protected function createComponentHomepagefriendlister($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'template_body' => 'ListerControlMain_users.phtml',
			'filter' => array(
				'user_id' => $user->getUserId()
			),
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'front_page' => true,
				'your_connections' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	/**
	*	list groups where user is member on home page when logged in
	*/
	protected function createComponentHomepagegrouplister($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_GROUP
			),
			'template_body' => 'ListerControlMain_groups.phtml',
			'filter' => array(
				'user_id' => $user->getUserId()
			),
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'your_connections' => true,
				'front_page' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	/**
	*	list resources which user has subscribed to on home page when logged in
	*/
	protected function createComponentHomepageresourcelister($name)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$options = array(
			'itemsPerPage' => 15,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'ListerControlMain_resources.phtml',
			'filter' => array(
				'user_id' => $user->getUserId()
			),
			'refresh_path' => 'Homepage:default',
			'template_variables' => array(
				'your_connections' => true,
				'front_page' => true
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	public function isAccessible()
	{
		return true;
	}
	
	
}
