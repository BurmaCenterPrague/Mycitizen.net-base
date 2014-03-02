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
 

final class WidgetPresenter extends BasePresenter
{
	private $object_id = null;

/**
 *	@todo ### Description
 *	@param
 *	@return
*/
	public function startup()
	{
		parent::startup();
	}



	/**
	 *	@todo Prepares the window content for the group chat to be loaded with AJAX.
	 *	@param string $name namespace
	 *	@return
	 */
	protected function createComponentChatwidget($name)
	{
		$query = NEnvironment::getHttpRequest();
		$group_id = $query->getQuery('group_id');
		$group = Group::create($group_id);
		
		$user = NEnvironment::getUser()->getIdentity();
		$user_id = $user->getUserId();
		$user_data = $user->getUserData();
		
		$page = $query->getQuery('page');
		$owner_name = $query->getQuery('owner');
		if (!empty($owner_name)) {
			$owner_ids = User::getOwnerIdsFromLogin($owner_name);
		} else {
			$owner_ids = null;
		}
		
		$options = array(
                        'itemsPerPage'=>30,
                        'lister_type'=>array(ListerControlMain::LISTER_TYPE_RESOURCE),
                        'filter' => array(
                        		'type' => 8,
                        		'page' => $page,
								'template_filter' => '',
								'only_active' => true,
								'owner' => $owner_ids,
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


	/**
	 *	@todo Prepares the window content for image browser (called by ckeditor).
	 *	@param void
	 *	@return
	 */
	public function actionBrowse()
	{
//		$this->template->URI = NEnvironment::getVariable("URI");
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';
		$query = NEnvironment::getHttpRequest();
		$CKEditorFuncNum = $query->getQuery("CKEditorFuncNum");
		$this->template->CKEditorFuncNum = (int)$CKEditorFuncNum;
		$user = NEnvironment::getUser()->getIdentity();
		if ($user && $user->getAccessLevel() > 0) {
			$user_id = $user->getUserId();
		} else {
			$this->flashMessage('Access denied. Did you sign in?');
			$this->terminate();
		}

		if (NEnvironment::getVariable("EXTERNAL_JS_CSS")) {
			$this->template->load_external_js_css = true;
		}
		$this->template->user_id = $user_id;
		$this->template->user_name = User::getFullName($user_id);
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';

		$allowed_extensions = array('jpg', 'jpeg', 'gif', 'png');
		$allowed_types = array('image/jpeg', 'image/gif', 'image/png');
		$path = WWW_DIR.'/images/uploads/user-'.$user_id;

		if(!file_exists($path) || !is_dir($path)) {
			mkdir($path);
		}

		// retrieve files
		$file_names = array_diff(scandir($path), array('.','..'));

		$data = array();
		if (count($file_names)) {
			foreach ($file_names as $file_name) {
				$file_path = $path.'/'.$file_name;
				$image = NImage::fromFile($file_path);
				$data[] = array(
					'file_name' => $file_name,
					'web_path' => NEnvironment::getVariable("URI") . '/images/uploads/user-'.$user_id.'/'.$file_name,
					'width' => $image->width,
					'height' => $image->height,
					'img_b64' => base64_encode($image->resize(120, 100)->toString(IMAGETYPE_JPEG,90)) // no sharpen for CMYK
				);
			}
		}
		$this->template->data = $data;

	}
}
