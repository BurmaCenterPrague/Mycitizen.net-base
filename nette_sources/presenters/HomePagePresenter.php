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
		// $user = NEnvironment::getUser()->getIdentity();
		
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
		// $user = NEnvironment::getUser()->getIdentity();
		
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
		// $user = NEnvironment::getUser()->getIdentity();
		
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
	
	private function relativeTime($timestamp) {
	
		if (date('Ymd') == date('Ymd', $timestamp)) {
			return _t('Today');
		}

		if (date('Ymd', strtotime('yesterday')) == date('Ymd', $timestamp)) {
			return _t('Yesterday');
		}
		
		return date('j M Y', $timestamp);
	}
	
	public function handleActivity($id = 1) {
		$user = NEnvironment::getUser()->getIdentity();
		if (!isset($user)) $this->terminate();
		
		$user_id = $user->getUserId();
		$header_time = '';
		
		$resource_name = array(
				1=>'1',
				2=>'event',
				3=>'org',
				4=>'doc',
				6=>'website',
				7=>'7',
				8=>'8',
				9=>'friendship',
				'media_soundcloud'=>'audio',
				'media_youtube'=>'video',
				'media_vimeo'=>'video',
				'media_bambuser'=>'live-video'
				);
		
		$now = time();
		// get timeframe from id
		switch ($id) {
			case 2: $header_time = _t("Today"); $from = strtotime('today midnight'); $to = null; break;
			case 3: $header_time = _t("Yesterday"); $from = strtotime('yesterday midnight'); $to = strtotime('today midnight'); break;
			case 4: $header_time = _t("Week"); $from = strtotime('7 days ago midnight'); $to = strtotime('yesterday midnight'); break;
			default: $header_time = _t("Month"); $from = strtotime('1 month ago midnight'); $to = strtotime('7 days ago midnight'); break;
		}

		// for scrolling
		echo '<div id="activity-scroll-target-'.$id.'"></div>';
		
		echo "<h3>".$header_time."</h3>";
		

		$activities = Activity::getActivities($user_id, $from, $to);
		
		if (isset($activities) && count($activities)) {

			foreach ($activities as $activity) {
			
				switch ($activity['object_type']) {
					case 1:
						if (!empty($activity['affected_user_id']) && $activity['object_id'] == $user_id) $object_id = $activity['affected_user_id']; else $object_id = $activity['object_id'];
						$object_link = '/user/?user_id='.$object_id;
						$object_icon = User::getImage($object_id, 'icon');
						$object_name = User::getUserLogin($object_id);
						$time = $this->relativeTime($activity['timestamp']);
					break;
					case 2:
						$object_link = '/group/?group_id='.$activity['object_id'];
						$object_icon = Group::getImage($activity['object_id'], 'icon');
						$object_name = Group::getName($activity['object_id']);
						$time = $this->relativeTime($activity['timestamp']);
					break;
					case 3:
						$object_link = '/resource/?resource_id='.$activity['object_id'];
						$object_icon = '<b class="icon-' . $resource_name[Resource::getResourceType($activity['object_id'])] . '"></b>';
						$object_name = Resource::getName($activity['object_id']);;
						$time = $this->relativeTime($activity['timestamp']);
					break;
				}
			
				switch ($activity['activity']) {
					case Activity::USER_JOINED:
						$description = _t('You signed up.');
					break;
					case Activity::FRIENDSHIP_REQUEST:
						if ($activity['object_id'] != $user_id) {
							$description = sprintf(_t('User %s requested your friendship.'),$object_name);
						} else {
							$description = sprintf(_t('You requested %s\'s friendship.'),$object_name);
						}
					break;
					case Activity::FRIENDSHIP_YES:
						if ($activity['object_id'] != $user_id) {
							$description = sprintf(_t('User %s accepted your friendship.'),$object_name);
						} else {
							$description = sprintf(_t('You accepted %s\'s friendship.'),$object_name);
						}
					break;
					case Activity::FRIENDSHIP_NO:
						if ($activity['object_id'] != $user_id) {
							$description = sprintf(_t('User %s rejected your friendship.'),$object_name);
						} else {
							$description = sprintf(_t('You rejected %s\'s friendship.'),$object_name);
						}
					break;
					case Activity::FRIENDSHIP_END:
						if ($activity['object_id'] != $user_id) {
							$description = sprintf(_t('User %s canceled your friendship.'),$object_name);
						} else {
							$description = sprintf(_t('You canceled the friendship with %s.'),$object_name);
						}
					break;

					case Activity::GROUP_JOINED: $description = _t('You joined the group.'); break;
					case Activity::RESOURCE_SUBSCRIBED: $description = _t('You subscribed to the resource.'); break;
					case Activity::GROUP_LEFT: $description = _t('You left the group.'); break;
					case Activity::RESOURCE_UNSUBSCRIBED: $description = _t('You unsubscribed from the resource.'); break;
					case Activity::GROUP_RESOURCE_ADDED: $description = _t('The group added a resource.'); break;
					case Activity::GROUP_CHAT: $description = _t('A new chat message was posted in the group.'); break;
					case Activity::RESOURCE_COMMENT: $description = _t('The resource has new comments.'); break;
					case Activity::GROUP_CREATED: $description = _t('A new group was created.'); break;
					case Activity::RESOURCE_CREATED: $description = _t('A new resource was created.'); break;
					case Activity::USER_UPDATED: $description = _t('You updated your profile.'); break;
					case Activity::GROUP_RESOURCE_REMOVED: $description = _t('The group unsubscribed from the resource.'); break;
					case Activity::GROUP_UPDATED: $description = _t('The group was updated.'); break;
					case Activity::RESOURCE_UPDATED: $description = _t('The resource was updated.'); break;
					case Activity::LOGIN_FAILED: $description = _t('Somebody tried to login with your name and a wrong password.'); break;
					default: $description = 'Unspecified activity'; break;
				}
			
				printf('<div class="activity-item" onclick="window.location=\'%s\'"><div class="activity-time"><h4>%s</h4></div><div class="activity-link"><h4><a href="%s">%s %s</a></h4></div><div class="activity-description"><h4>%s</h4></div></div>', $object_link, $time, $object_link, $object_icon, $object_name, $description);
			}
		} else {
			echo '<div class="activity-item"><h4>'._t("Nothing to display").'</h4></div>';
		}
		
		if ($id < 5) {
			echo '
	<div id="load-more-'.$id .'">
		<p><a href="javascript:void(0);" id="load_more" class="button">'._t("load more...").'</a></p>
	</div>';

			$id_inc = $id +1;
		
			echo '
	<script>
		$("#load_more").click(function(){
			loadActivity("#load-more-'.$id.'", '.$id_inc.');
		});
	</script>
			';
		}
		
		$this->terminate();
	}
	
	
}
