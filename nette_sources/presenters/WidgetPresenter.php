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
 

final class WidgetPresenter extends BasePresenter
{
	private $object_id = null;
	public function startup()
	{
		parent::startup();
	}
	
	public function actionPublicComponents($component_type, $object_type, $object_id)
	{
		$this->object_id = $object_id;
		
		switch ($object_type) {
			case 1:
				if ($component_type == 2) {
					$this->template->type = "membergroups";
				}
				if ($component_type == 3) {
					$this->template->type = "userresources";
				}
				break;
			case 2:
				if ($component_type == 1) {
					$this->template->type = "groupmembers";
				}
				if ($component_type == 3) {
					$this->template->type = "groupresources";
				}
				break;
			case 3:
				$this->template->type = "resourcemembers";
				break;
				
		}
		$this->template->object_id = $object_id;
	}

	
	protected function createComponentGroupmembers($name)
	{
		$options = array(
			'itemsPerPage' => 50,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER
			),
			'filter' => array(
				'group_id' => $this->object_id
			),
			'refresh_path' => 'Widget:publicComponents',
			'refresh_path_params' => array(
				'object_type' => 2,
				'object_id' => $this->object_id
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	protected function createComponentUserresources($name)
	{
		$options = array(
			'itemsPerPage' => 50,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'filter' => array(
				'user_id' => $this->object_id
			),
			'refresh_path' => 'Widget:publicComponents',
			'refresh_path_params' => array(
				'component_type' => 3,
				'object_type' => 1,
				'object_id' => $this->object_id
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	protected function createComponentGroupresources($name)
	{
		$options = array(
			'itemsPerPage' => 50,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'filter' => array(
				'group_id' => $this->object_id
			),
			'refresh_path' => 'Widget:publicComponents',
			'refresh_path_params' => array(
				'component_type' => 3,
				'object_type' => 2,
				'object_id' => $this->object_id
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	protected function createComponentMembergroups($name)
	{
		$options = array(
			'itemsPerPage' => 50,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_GROUP
			),
			'filter' => array(
				'user_id' => $this->object_id
			),
			'refresh_path' => 'Widget:publicComponents',
			'refresh_path_params' => array(
				'component_type' => 1,
				'object_type' => 1,
				'object_id' => $this->object_id
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	protected function createComponentResourcemembers($name)
	{
		$options = array(
			'itemsPerPage' => 50,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_USER,
				ListerControlMain::LISTER_TYPE_GROUP
			),
			'filter' => array(
				'resource_id' => $this->object_id
			),
			'refresh_path' => 'Widget:publicComponents',
			'refresh_path_params' => array(
				'object_type' => 3,
				'object_id' => $this->object_id
			)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}
	
	public function actionUserResource($resource_id = null)
	{
		if (empty($resource_id)) {
			
			$this->redirect('Homepage:default');
		} else {
			$this->object_id = $resource_id;
			$user            = NEnvironment::getUser()->getIdentity();
			$resource        = Resource::create($resource_id);
			
			if (!empty($resource)) {
				$resource_id = $resource->getResourceId();
				if (!empty($resource_id)) {
					$access = Auth::isAuthorized(Auth::TYPE_RESOURCE, $resource_id);
					if ($access < 2) {
						$this->redirect("Homepage:default");
					}
				}
			}
		}
	}
	
	public function actionGroupResource($resource_id = null)
	{
		if (empty($resource_id)) {
			
			$this->redirect('Homepage:default');
		} else {
			$this->object_id = $resource_id;
			$user            = NEnvironment::getUser()->getIdentity();
			$resource        = Resource::create($resource_id);
			
			if (!empty($resource)) {
				$resource_id = $resource->getResourceId();
				if (!empty($resource_id)) {
					$access = Auth::isAuthorized(Auth::TYPE_RESOURCE, $resource_id);
					if ($access < 2) {
						$this->redirect("Homepage:default");
					}
				}
			}
		}
	}
	

	protected function createComponentChatwidget($name)
	{
		$query = NEnvironment::getHttpRequest();
		$group_id = $query->getQuery('group_id');
		$group = Group::create($group_id);
		
		$user = NEnvironment::getUser()->getIdentity();
		$user_id = $user->getUserId();
		$user_data = $user->getUserData();
		
		$page = $query->getQuery('page');

		$options = array(
                        'itemsPerPage'=>30,
                        'lister_type'=>array(ListerControlMain::LISTER_TYPE_RESOURCE),
                        'filter' => array(
                        		'type' => 8,
                        		'page' => $page,
								'template_filter' => '',
								'only_active' => true,
                        		'all_members_only'=>array(
                        			array(
                        				'type'=>2,
                        				'id'=>$group_id
                        				)
                        			)
                        		),
						'template_body'=>'ChatLister_ajax.phtml',
                        'refresh_path'=>'Group:default',
                        'refresh_path_params' => array(
								'group_id' => $group_id
							),
                        'template_variables' => array(
                        		'hide_filter' => 1,
                        		'user_login' => $user_data['user_login'],
                        		'is_member' => $group->isMember($user_id),
                        		'group_id' => $group_id
                        		)
                     );


		$control = new ListerControlMain($this, $name, $options);		
		
		// retrieve time of most recent post for http header
		$data=$control->getPageData($control->getFilterArray($options['filter']));
		$row=reset($data);
		$res = Resource::Create($row['id']);
		$r_data = $res->getResourceData();
		if (isset($r_data)) {
			$date=(array)$r_data['resource_creation_date'];
			date_default_timezone_set($date['timezone']);
			$timestamp=strtotime($date['date']);
		} else {
			$timestamp=0;
		}
		$date_formatted = gmstrftime('%A %d-%b-%y %T %Z',$timestamp);
		$httpResponse = NEnvironment::getHttpResponse();
		$httpResponse->setHeader('Last-Modified', $date_formatted);
		
		return $control;
	}
	
}
