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
 
final class AdministrationPresenter extends BasePresenter
{

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function startup()
	{
		$user   = NEnvironment::getUser()->getIdentity();
		$access = $user->getAccessLevel();
		if ($access < 2) {
			$this->redirect("Homepage:default");
		}
		parent::startup();
		
//		if (class_exists('NDebug') && (NDebug::isEnabled())) { NDebug::enableProfiler(); }
		$this->template->baseUri = NEnvironment::getVariable("URI") . '/';
		
		$user = NEnvironment::getUser();
		if ($user->isLoggedIn()) {
			$user   = NEnvironment::getUser()->getIdentity();
			$access = $user->getAccessLevel();
			if ($access < 2) {
				$this->redirect("Homepage:default");
			}
		} else {
			$this->redirect("Homepage:default");
		}
		
		// check every 60 mins for updates
		// update info is CSV with columns: version, yyyymmdd, info
		if (!isset($_SESSION['update_ping']) || $_SESSION['update_ping']+3600 < time()) {
			$url = "https://mycitizen.net/versioncheck/version.csv";
			if ($fp = @fopen ($url, 'r')) {
			$data = fgetcsv($fp,1000,"\t");
			fclose($fp);
			$_SESSION['update_ping'] = time();
			
			if (PROJECT_DATE < $data[1]) {
				$this->flashMessage('New update available: '.$data[0]);
				$this->flashMessage('For more information see "Setup and Maintenance".');
				$this->template->new_version = $data[0];
				$this->template->new_version_info = $data[2];
				$_SESSION['update_new_version'] = $data[0];
				$_SESSION['update_new_version_info'] = $data[2];
			} else {
				unset($_SESSION['update_new_version']);
				unset($_SESSION['update_new_version_info']);
			}
			
			} else {
				$this->flashMessage('Cannot retrieve update information.', 'error');
			}
		} else {
			if (isset($_SESSION['update_new_version'])) $this->template->new_version = $_SESSION['update_new_version'];
			if (isset($_SESSION['update_new_version_info'])) $this->template->new_version_info = $_SESSION['update_new_version_info'];
		}
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionDefault()
	{
		$this->template->stats = Administration::getStatistics();
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionUsers()
	{
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionReports()
	{	
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionTags()
	{
		$user   = NEnvironment::getUser()->getIdentity();
		$access = $user->getAccessLevel();
		if ($access < 3) {
			$this->flashMessage(_t('Only administrators can visit this section!'), 'error');
			$this->redirect("Administration:default");
		}
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionTag()
	{
		$user   = NEnvironment::getUser()->getIdentity();
		$access = $user->getAccessLevel();
		if ($access < 3) {
			$this->flashMessage(_t('Only administrators can visit this section!'), 'error');
			$this->redirect("Administration:default");
		}
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionPiwik()
	{
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionNoticeboard()
	{
		$this->template->load_js_css_editor = true;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	*/
	public function actionSettings()
	{
		$user   = NEnvironment::getUser()->getIdentity();
		$access = $user->getAccessLevel();
		if ($access < 3) {
			$this->flashMessage(_t('Only administrators can visit this section!'), 'error');
			$this->redirect("Administration:default");
		}
		$session  = NEnvironment::getSession()->getNamespace("GLOBAL");
		$language = $session->language;
		if (empty($language)) {
			$session->language = 'en_US';
			$language          = $session->language;
		}
		
		$this->template->setTranslator(new GettextTranslator('../locale/' . $language . '/LC_MESSAGES/messages.mo', $language));
		$this->template->PROJECT_VERSION = NEnvironment::getVariable("PROJECT_VERSION");
	
		# workaround to get labels of settings into GetText:
		$setting_labels = array(
		// TRANSLATORS: Email from where messages are sent.
			_t('from_email'),
		// TRANSLATORS: Default GPS latitude.
			_t('gps_default_latitude'),
		// TRANSLATORS: Default GPS longitude.
			_t('gps_default_longitude'),
		// TRANSLATORS: Minimum registration time in seconds to receive creation rights.
			_t('object_creation_min_time')
		);
	
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function actionSetupmaintenance() {
		$user   = NEnvironment::getUser()->getIdentity();
		$access = $user->getAccessLevel();
		if ($access < 3) {
			$this->flashMessage(_t('Only administrators can visit this section!'), 'error');
			$this->redirect("Administration:default");
		}	

		$this->template->stats = Administration::getStatistics();
		$this->template->MySQL_version = dibi::fetchSingle("SELECT VERSION() as mysql_version");
		
		$rows = dibi::fetchall("SHOW TABLE STATUS");  
		$size = 0;  
		foreach($rows as $row) {  
		    $size += $row["Data_length"] + $row["Index_length"];  
		}
		$decimals = 2;  
		$mbytes = number_format($size/(1024*1024),$decimals);
		$this->template->database_size = $mbytes;

	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentRegistertag()
	{
		$form = new NAppForm($this, 'registertag');
		$form->addText('tag_name', _t('Tag name:'));
		$form->addComponent(new ContainerTreeSelectControl(_t('Parent tag:')), 'tag_parent_id');
		$form['tag_parent_id']->addRule(~NForm::EQUAL, _t("The tag cannot be its own parent."), $form['tag_name']);
		$form->addSubmit('register', _t('Add'));
		$form->addProtection(_t('Error submitting form.'));
		
		$form->onSubmit[] = array(
			$this,
			'registerformSubmitted'
		);
		$form->setDefaults(array(
			'tag_parent_id' => 0
		));
		return $form;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function registerformSubmitted(NAppForm $form)
	{
		$values  = $form->getValues();
		$new_tag = Tag::create();
		
		$new_tag->setTagData($values);
		$new_tag->save();
		$this->flashMessage(sprintf(_t('Tag "%s" saved.'),$values['tag_name']));
		$this->redirect("Administration:tag");
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleUserAdministration($user_id, $values)
	{
		$user = User::create($user_id);
		
		$user->setUserData($values);
		$user->save();
		$this->terminate();
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentUserlister($name)
### needed?
	{
		$options = array(
			'itemsPerPage' => 50,
			'access_mode' => UserListerControl::ADMINISTRATION_MODE
		);
		$control = new UserListerControl($this, $name, $options);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentTaglister($name)
	{
		$control = new TagListerControl($this, $name, 20);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentReportlister($name)
	{
		$options = array(
			'itemsPerPage' => 50,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'ReportLister_body.phtml',
			'filter' => array(
				'type' => 7
			),
			'refresh_path' => 'Administration:reports',
			'template_variables' => array(
                    'hide_filter' => true)
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentAdminmenu($name)
	{

		$user = NEnvironment::getUser()->getIdentity();
		$access = $user->getAccessLevel();
		$menu_admin = array();

		
		$menu_mod = array(
			'1' => array(
				'title' => _t('Statistics'),
				'presenter' => 'administration',
				'action' => 'default',
				'parameters' => array(),
				'parent' => 0
			)
			);

		if (!empty($this->template->PIWIK_URL) && !empty($this->template->PIWIK_ID) && !empty($this->template->PIWIK_TOKEN)) {
			$menu_mod['2'] = array(
					'title' => _t('Web Statistics'),
					'presenter' => 'administration',
					'action' => 'piwik',
					'parameters' => array(),
					'parent' => 1
				);	
		}
		
		$menu_mod['3'] = array(
				'title' => _t('Reports'),
				'presenter' => 'administration',
				'action' => 'reports',
				'parameters' => array(),
				'parent' => 0
			);

		$menu_mod['4'] = array(
				'title' => _t('Notice Board'),
				'presenter' => 'administration',
				'action' => 'noticeboard',
				'parameters' => array(),
				'parent' => 0
			);

		
		if ($access == 3) {
			$menu_admin    = array(
				'5' => array(
					'title' => _t('Tags'),
					'presenter' => 'administration',
					'action' => 'tags',
					'parameters' => array(),
					'parent' => 0
				),
				'6' => array(
					'title' => _t('Add tag'),
					'presenter' => 'administration',
					'action' => 'tag',
					'parameters' => array(),
					'parent' => 5
				),
				'7' => array(
					'title' => _t('Settings'),
					'presenter' => 'administration',
					'action' => 'settings',
					'parameters' => array(),
					'parent' => 0
				),
				'8' => array(
					'title' => _t('Setup and Maintenance'),
					'presenter' => 'administration',
					'action' => 'setupmaintenance',
					'parameters' => array(),
					'parent' => 0
				)
			);
		}		

		$menu = ($menu_mod+$menu_admin);

		$control = new MenuControl($this, $name, $menu);
		$control->setStyle(MenuCOntrol::MENU_STYLE_CLASSIC);
		$control->setOrientation(MenuControl::MENU_ORIENTATION_VERTICAL);
		return $control;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	protected function createComponentVariablesform()
	{
		$form     = new NAppForm($this, 'variablesform');
		$vars     = Settings::getAllVariables();
		$defaults = array();
		foreach ($vars as $key => $value) {
			$defaults[$key] = $value;
			$form->addText($key, Settings::getVariableLabel($key) . ":");
		}
		$form->addSubmit('send', _t('Submit'));
		$form->addProtection(_t('Error submitting form.'));
		
		$form->setdefaults($defaults);
		$form->onSubmit[] = array(
			$this,
			'variablesformSubmitted'
		);
		
		return $form;
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function variablesformSubmitted(NAppForm $form)
	{
		$values = $form->getValues();
		
		if (isset($values['maintenance_mode'])) {
			if (preg_match("/^in (\d+) minutes?$/i", $values['maintenance_mode'], $matches) ) {
				$values['maintenance_mode'] = time() + $matches[1] * 60;
			}
			$values['maintenance_mode'] = (int) $values['maintenance_mode'];
			
			if ($values['maintenance_mode'] == 0 && Settings::getVariable('maintenance_mode') != 0) {
				if (@file_exists(WWW_DIR.'/.maintenance.php')) {
					unlink(WWW_DIR.'/.maintenance.php');
				}
				$this->flashMessage(_t("Maintenance mode deactivated."));
			}

			if ($values['maintenance_mode'] != 0 && Settings::getVariable('maintenance_mode') == 0) {
				if (!@file_exists(WWW_DIR.'/.maintenance.php')) {
					file_put_contents(WWW_DIR.'/.maintenance.php',$values['maintenance_mode']);
				}
				$this->flashMessage(_t("Maintenance mode activated."));
			}


		}
		foreach ($values as $key => $value) {
			Settings::setVariable($key, $value);
		}
	}


	/**
	 *	Delete reports
	 *	@param int $report_id
	 *	@return void
	*/
	public function handleDeleteReport($report_id) {
		if (NEnvironment::getUser()->getIdentity()->getAccessLevel()>1 && !empty($report_id)) {
			Resource::delete($report_id);
			echo "true";
			$storage = new NFileStorage(TEMP_DIR);
			$cache = new NCache($storage, "Filter.number");
			$cache->clean(array(NCache::ALL => TRUE));
		}
		
		$this->terminate();
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleRevokePermission($object_type, $object_id) {

		if (NEnvironment::getUser()->getIdentity()->getAccessLevel()<2) $this->terminate();
		
		if ($object_type == 1) {
			$revoke_id = $object_id;
		}
		
		if ($object_type == 2) {
			$object = Group::create($object_id);
			if (!empty($object)) {
				$owner = $object->getOwner();
				$revoke_id = $owner->getUserId();
			}
		}

		if ($object_type == 3) {
			$object = Resource::create($object_id);
			if (!empty($object)) {
				$owner = $object->getOwner();
				$revoke_id = $owner->getUserId();
			}
		}

		if (!empty($revoke_id)) {
			$user = User::create($revoke_id);
			if (!empty($user) && $user->getAccessLevel()<2) {
				$user->revokeCreationRights();
			}
		}

		$this->terminate();
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleSendWarning($object_type, $object_id, $warning_type)
	{
		if (NEnvironment::getUser()->getIdentity()->getAccessLevel()<2) $this->terminate();
	
		$user = NEnvironment::getUser()->getIdentity();
		if ($object_type == 1) {
			if ($warning_type == "0") {
				$message = _t("We have received complaints about you spaming other users. Please stop or your account will be deactivated.");
			}
			if ($warning_type == "1") {
				$message = "";
			}
			if ($warning_type == "2") {
				$message = _t("We have received complaints that you use inappropriate language. Please stop or your account will be deactivated.");
			}
			
			StaticModel::sendSystemMessage(1, $user->getUserId(), $object_id, $message);
		}
		
		if ($object_type == 2) {
			if ($warning_type == "0") {
				$message = _t("We have received complaints that your group contains spam. Please delete inappropriate content or your group will be deactivated.");
			}
			if ($warning_type == "1") {
				$message = "";
			}
			if ($warning_type == "2") {
				$message = _t("We have received complaints about inappropriate language in your group. Please make neccessary adjustments or your group will be deactivated.");
			}
			
			$object = Group::create($object_id);
			$message .= "\n"._t('Name').": ".$object->getName();
			if (!empty($object)) {
				$owner = $object->getOwner();
				if (!empty($owner)) {
					StaticModel::sendSystemMessage(5, $user->getUserId(), $owner->getUserId(), $message, $object_type, $object_id);
				}
			}
		}
		
		if ($object_type == 3) {
			if ($warning_type == "0") {
				$message = _t("We have received complaints that your resource contains spam. Please delete inappropriate content or your resource will be deactivated.");
			}
			if ($warning_type == "1") {
				$message = "";
			}
			if ($warning_type == "2") {
				$message = _t("We have received complaints about inappropriate language in your resource. Please make necessary adjustments or your resource will be deactivated.");
			}
			$object = Resource::create($object_id);
			$message .= "\n"._t('Name').": ".$object->getName();
			if (!empty($object)) {
				$owner = $object->getOwner();
				if (!empty($owner)) {
					StaticModel::sendSystemMessage(6, $user->getUserId(), $owner->getUserId(), $message, $object_type, $object_id);
				}
			}
			
		}
		$this->terminate();
	}


	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleDisableObject($object_type, $object_id)
	{
		if (NEnvironment::getUser()->getIdentity()->getAccessLevel()<2) $this->terminate();
		
		if ($object_type == 1) {
			$object = User::create($object_id);
			if (!empty($object) && $object->getAccessLevel()<2) {
				$object->bann();
			}
		}
		if ($object_type == 2) {
			$object = Group::create($object_id);
			if (!empty($object)) {
				$object->bann();
			}
		}
		if ($object_type == 3) {
			$object = Resource::create($object_id);
			if (!empty($object)) {
				$object->bann();
			}
		}
		
		$this->terminate();
		
	}


	/**
	 *	Do the system check.
	 *	@param
	 *	@return
	*/
	public function handleSystemCheck() {	
	
		$result = Administration::systemCheck();
		$this->template->problem = false;
		
		if ($result['groups_wo_owner']) {
			$this->template->groups_wo_owner = $result['groups_wo_owner'];
			$this->template->problem = true;
		}

		if ($result['resources_wo_owner']) {
			$this->template->resources_wo_owner = $result['resources_wo_owner'];
			$this->template->problem = true;
		}
		
		
		$this->flashMessage(_t('System check finished. Find the results below.'));
		
		// no redirect here!
		
	}
	

	/**
	 *	Purge inactive users.
	 *	@param
	 *	@return
	*/
	public function handlePurgeUsers($months) {	
	
		$number_cleared = Administration::clearUsers($months);
		
		$this->flashMessage(sprintf(_t('%d user(s) purged.'), $number_cleared));
		
		$this->redirect("Administration:setupmaintenance");
	}
	

	/**
	 *	Checks the sub-folders in 'locale' and adds the codes to the database, if not yet present.
	 *	@param
	 *	@return
	*/
	public function handleLocales() {
		
		$languages = Language::getAllCodes();
		$dirs = scandir(LOCALE_DIR);
		$error = null;

		// adding new languages to database
		foreach ($dirs as $dir) {
			if ($dir === '.' or $dir === '..') continue;

			if (is_dir(LOCALE_DIR.'/'.$dir)) {
        		if (in_array($dir,$languages)) {
        			$result = 'Already installed.';
					if (!$information=@file_get_contents(LOCALE_DIR.'/'.$dir.'/'.'LC_MESSAGES/language.txt')) {
						$result .= ' We need a file language.txt in the LC_MESSAGES folder containing the name and the ISO 639-3 code!';
						$error = 'error';
					} else {
				
						if (!@file_get_contents(WWW_DIR.'/files/'.$dir.'/footer.phtml') || !@file_get_contents(WWW_DIR.'/files/'.$dir.'/intro.phtml')) {
							$result .= ' We need files footer.phtml and intro.phtml in the '.WWW_DIR.'/files/'.$dir.' folder!';
							$error = 'error';
						} else {
							$information_a = explode("\n", $information);
							$name = trim($information_a[0]);
							$code = trim($information_a[1]);
							if (Language::updateCode($dir, $code, $name)!==false) {
								$result .= ' Updated "'.$name.'" with code "'.$code.'".';
							} else {
								$result .= ' Database error: '.$result;
								$error = 'error';
							}
						}
					}
        		} else {
        			$result = 'It seems to be new.';
        			$dirs_inside = scandir(LOCALE_DIR.'/'.$dir);
        			if (!in_array('LC_MESSAGES',$dirs_inside)) {
        				$result .= ' Sub-directory LC_MESSAGES is missing!';
        				$error = 'error';
        			} else {
        				$dirs_inside = scandir(LOCALE_DIR.'/'.$dir.'/'.'LC_MESSAGES');
        				if (!in_array('messages.mo',$dirs_inside)) {
        			    	$result .= ' File messages.mo is missing!';
        					$error = 'error';
        				} else {
							// try to retrieve name of language
							if (!$information=@file_get_contents(LOCALE_DIR.'/'.$dir.'/'.'LC_MESSAGES/language.txt')) {
								$result .= ' We need a file language.txt in the LC_MESSAGES folder containing the name and the ISO 639-3 code!';
		        				$error = 'error';
							} else {
						
								if (!@file_get_contents(WWW_DIR.'/files/'.$dir.'/footer.phtml') || !@file_get_contents(WWW_DIR.'/files/'.$dir.'/intro.phtml')) {
									$result .= ' We need files footer.phtml and intro.phtml in the '.WWW_DIR.'/files/'.$dir.' folder!';
			        				$error = 'error';
								} else {
									$information_a = explode("\n", $information);
									$name = trim($information_a[0]);
									$code = trim($information_a[1]);
									if (Language::addCode($dir, $code, $name)) {
										$result .= ' Added "'.$name.'" with code "'.$code.'".';
									} else {
										$result .= ' Database error.';
										$error = 'error';
									}
								}
							}
        				}
        				
        			}
        		}
        		
        		$this->flashMessage("Found locale '".$dir."': ".$result,$error);
		    }
		}
		
		// removing old locales
		foreach ($languages as $lang) {
			if (!in_array($lang,$dirs)) {
				$this->flashMessage(sprintf(_t("Found unused locale '%s', removing from database."),$lang));
				
				if (!Language::removeCode($lang)) {
					$this->flashMessage(_t("Database error."), 'error');
				}	
			}
		}

		$this->flashMessage(_t("Done updating locales."));
	}
	
	
	/**
	*	Imports a file located in the web/files folder and adds the entries as new resources.
	*	default file name: resources.csv
	*	call it from the setup menu or with: /administration/?do=import
	*	optional queries:
	*		file=filename (defualt: resources.csv)
	*		delimiter=comma (default: tabulator)
	*		enclosure=null	(default: ")
	*	
	*	Data must be provided in tab- or comma-separated values, optionally text in enclosing quotes (particularly if containing the delimiter). Data starts from the first row.
	*	Name, Description, Type [organization,document,youtube,vimeo,soundcloud,bambuser,other], Location (string), URL, Media Code (for multimedia), Long Info (for documents), Language (id; default is 1), visibility (1: world, 2: registerd, 3: subscribers), Tags (comma separated names)
	*	Name, Description and Type are mandatory.
	*	Events not yet fully supported.
	*
	*	You will find an error log with the name of the file and appended '.log'.
	*	@param
	*	@return
	*/
	public function handleImport($test_run = null) {
	
		$user = NEnvironment::getUser()->getIdentity();
		
		if ($user->getAccessLevel()<3) $this->terminate();
		
		$messages = array();
		$messages[] ='Importing resources from a file';
		$messages[] ="===============================".PHP_EOL;
		if (isset($test_run)) {
			$messages[] ='!!! test run !!!';
		}
		$messages[] = '### '.date("Y-m-d H:i:s").' ###';

		
		$user_id = $user ->getUserId();
		
		$query = NEnvironment::getHttpRequest();
		
		$file_name = $query->getQuery("file");
		
		$delimiter = (strtolower($query->getQuery("delimiter")) == 'comma') ? ',' : "\t";
		
		$enclosure = (strtolower($query->getQuery("enclosure")) == 'null') ? '' : '"';
		
		if (empty($file_name)) {
			$file_name='resources.csv';
			$messages[] = 'Using default file name.';
		}
		
		$path_file = WWW_DIR.'/files/'.$file_name;
		
		$fp = fopen($path_file,'r');
		
		$delimiter_display = ( $delimiter == "\t" ) ? '<tab>' : $delimiter;
		$messages[] = 'Using file at '.$path_file;
		$messages[] = 'Fields delimited by '.$delimiter_display;
		$messages[] = 'Strings enclosing with '.$enclosure;
				
		if ($fp) {
	
			$messages[] = 'File opened. Starting processing.';
			$count = 0;
		
			while ($fields = fgetcsv($fp,0,$delimiter,$enclosure)) {
				
				$count++;
				
				if (empty($fields[0])) {
					$messages[] = $count.': Empty name.';
					continue;
				}
				
				if (empty($fields[1])) {
					$messages[] = $count.': Empty description.';
					continue;
				}
				
				if (substr($fields[0], 0, 1)=='#') {
					$messages[] = $count.': Line identified as comment.';
					continue;
				}
				
				if (count($fields) != 10) {
					$messages[] = $count.': Wrong number of fields.';
					continue;
				}
			
				$fields = array_map('trim',$fields);
			
				$location['longitude'] = NULL;
				$location['latitude'] = NULL;
			
				if (!empty($fields[3])) {
					if (!$location = $this->lookup_address($fields[3])) {
						$messages[] = $count.': Cannot find address '.$fields[3].'.';
					}
				}
			
				switch (strtolower($fields[2])) {
					case 'organization':
					case 'organisation': $type = 3; $media_type = ''; break;
					case 'document':
					case 'doc': $type = 4; $media_type = ''; break;
					case 'youtube': $type = 5; $media_type = 'media_youtube'; break;
					case 'vimeo': $type = 5; $media_type = 'media_vimeo'; break;
					case 'soundcloud': $type = 5; $media_type = 'media_soundcloud'; break;
					case 'bambuser': $type = 5; $media_type = 'media_bambuser'; break;
					case 'event': $type = 2; $media_type = ''; $messages[] = $count.'Cannot import time of event.'; break;
					case 'other':
					case 'link': $type = 6; $media_type = ''; break;
					default: $messages[] = $count.': Unrecognized type '.$fields[2].'.'; $type=0; continue; break;
				}
			
				if ($type == 5) {
					$resource_data = array(
						'media_type' => $media_type,
						'media_link' => $fields[5],
					);
				}
			
				if ($type == 3) {
					$resource_data['organization_url'] = $fields[4];
					$resource_data['organization_information'] = $fields[6];
				} elseif ($type == 4) {
					$resource_data['text_information_url'] = $fields[4];
					$resource_data['text_information'] = $fields[6];
				} else {
					$resource_data['other_url'] = $fields[4];
				}

				$tags = explode(',', $fields[9]);
				$tags = array_map('trim',$tags);
				
				$tag_ids = array();
				
				if (!empty($tags[0])) {
					foreach ($tags as $tag) {
						$result = dibi::fetchAll("SELECT * FROM `tag` WHERE LOWER(`tag_name`) = LOWER(%s)", $tag);
						if (sizeof($result) > 2) {
							// more than one tag
							$messages[] = $count.': Multiple tags match '.$tag.'.';
							foreach ($result as $result_item) {
								$data_array= $result_item->toArray();
								$tag_ids[] = $data_array['tag_id'];
							}
						} elseif (sizeof($result) < 1) {
							// create missing tag; for now notification
							$messages[] = $count.': No tag matches "'.$tag.'". Please create manually.';
						} else {
							$data_array = $result[0]->toArray();
							$tag_ids[] = $data_array['tag_id'];
						}
					}
				}
			
				$data = array(
					'resource_name' => $fields[0],
					'resource_visibility_level' => $fields[8],
					'resource_language' => $fields[7],
					'resource_description' => $fields[1],
					'resource_data' => json_encode($resource_data)
				);
			
				$data['resource_author'] = $user_id;
				$data['resource_type'] = $type;
				$data['resource_position_y'] = $location['longitude'];
				$data['resource_position_x'] = $location['latitude'];
			
				if (!isset($test_run)) {
					$resource = Resource::create();
					$resource->setResourceData($data);
					$resource->save();
					$resource->setLastActivity();
				}

				if (isset($tag_ids)) {
					foreach($tag_ids as $tag_id) {
							if (!isset($test_run)) {
								$resource->insertTag($tag_id);
							}
							$messages[] = 'Inserting tag '.$tag_id;
					}
				}
				if (!isset($test_run)) {
					unset($resource);
				}
				unset($tag_ids);

			}

			fclose($fp);	

			$messages[] = 'Finishing processing.';
			$messages[] = $count.' resources processed. Please don\'t process again lines that were successfully imported. It is recommended to remove the source file.';
			
		} else {
			$messages[] = 'Error opening file for writing.';
		}
		$messages[] = '### '.date("Y-m-d H:i:s").' ###';
		
		// save the log to a file with extension .log
		$messages = array_map(function($s) { return $s.PHP_EOL; }, $messages);
		if (!@file_put_contents($path_file.'.log',$messages)) {
			$this->flashMessage(_t("Done importing resources. Error writing log file."), 'error');
		} else {
			$this->flashMessage(_t("Done importing resources. Please check the log file."));
		}
		if (isset($test_run)) {
			$this->flashMessage(_t("This was just a dry run - nothing was saved."));
		}
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function lookup_address($string) {
 
	   $string = str_replace (" ", "+", urlencode($string));
	   $details_url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$string."&sensor=false";
 
	   $ch = curl_init();
	   curl_setopt($ch, CURLOPT_URL, $details_url);
	   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	   $response = json_decode(curl_exec($ch), true);
 
	   // If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
	   if ($response['status'] != 'OK') {
			return null;
	   }
 
	   $geometry = $response['results'][0]['geometry'];
 
		$array = array(
			'latitude' => $geometry['location']['lat'],
			'longitude' => $geometry['location']['lng'],
			'location_type' => $geometry['location_type'],
		);
 
		return $array;
 
	}

	/**
	 *	@todo ### Description
	 *	@param
	 *	@return
	 */
	public function handleMove($tag_id, $direction) {
		$result = dibi::fetchAll("SELECT * FROM `tag` WHERE `tag_parent_id` = 0 ORDER BY `tag_position`,`tag_id`");
		$tags = array();
		
		foreach($result as $tag) {
			$tags[$tag['tag_id']] = $tag;
		}
		
		if ($direction == 'down') {
			$tags = array_reverse($tags, true);
		}
//var_dump($tags);die();
			// find the one above
			$id_above = 0;
			foreach($tags as $tag) {
				if ($tag['tag_id'] == $tag_id) break;
				$id_above = $tag['tag_id'];
			}

			if ($id_above > 0) {
				// swap $id_above and $tag_id
				if ($direction == 'up') {
					if ($tags[$tag_id]['tag_position'] == $tags[$id_above]['tag_position'] ) {
						$tags[$id_above]['tag_position'] = $tags[$tag_id]['tag_position'] + 1;
						dibi::query('UPDATE `tag` SET `tag_position` = `tag_position` + 1 WHERE `tag_position` > %i', $tags[$tag_id]['tag_position']);
					} else {
						$tmp = $tags[$id_above]['tag_position'];
						$tags[$id_above]['tag_position'] = $tags[$tag_id]['tag_position'];
						$tags[$tag_id]['tag_position'] = $tmp;
					}
				} else {
					if ($tags[$id_above]['tag_position'] == $tags[$tag_id]['tag_position'] ) {
						$tags[$tag_id]['tag_position'] = $tags[$id_above]['tag_position'] + 1;					
						dibi::query('UPDATE `tag` SET `tag_position` = `tag_position` + 1 WHERE `tag_position` > %i', $tags[$id_above]['tag_position']);
					} else {
						$tmp = $tags[$tag_id]['tag_position'];
						$tags[$tag_id]['tag_position'] = $tags[$id_above]['tag_position'];
						$tags[$id_above]['tag_position'] = $tmp;
					}
				}
				
				dibi::query('UPDATE `tag` SET `tag_position` = %i WHERE `tag_id` = %i', $tags[$id_above]['tag_position'], $id_above);			
				dibi::query('UPDATE `tag` SET `tag_position` = %i WHERE `tag_parent_id` = %i', $tags[$id_above]['tag_position'], $id_above);				

				dibi::query('UPDATE `tag` SET `tag_position` = %i WHERE `tag_id` = %i', $tags[$tag_id]['tag_position'], $tag_id);
				dibi::query('UPDATE `tag` SET `tag_position` = %i WHERE `tag_parent_id` = %i', $tags[$tag_id]['tag_position'], $tag_id);

			}


	}

	/**
	 *	Preparing form submission for comments on Resources
	 *	@param
	 *	@return
	 */
	protected function createComponentNoticeboardform()
	{
		$form = new NAppForm($this, 'noticeboardform');
		$form->addTextarea('message_text', '');
		$form['message_text']->getControlPrototype()->class('ckeditor-small');
		$form->addSubmit('send', _t('Post'));
		$form->addProtection(_t('Error submitting form.'));
		
		$form->onSubmit[] = array(
			$this,
			'noticeboardformSubmitted'
		);
		
		return $form;
	}


	/**
	 *	Processing form submission for comments on Resources
	 *	@param
	 *	@return
	 */
	public function noticeboardformSubmitted(NAppForm $form)
	{
		$user = NEnvironment::getUser()->getIdentity();
		
		$values                  = $form->getValues();
		$resource                = Resource::create();
		$data                    = array();
		$data['resource_author'] = 0;
		$data['resource_type']   = 11;
		$data['resource_data']   = json_encode(array(
			'message_text' => $values['message_text']
		));
		$resource->setResourceData($data);
		
		$resource->save();
		
		Activity::addActivity(Activity::NOTICEBOARD_MESSAGE, $resource->getResourceId(), 3);
		
		$storage = new NFileStorage(TEMP_DIR);
		$cache = new NCache($storage, "Lister.noticeboard");
		$cache->clean(array(NCache::ALL => TRUE));
		
		$this->redirect("Administration:noticeboard");
	}


	/**
	 *	Lists the notices for admin
	 *	@param
	 *	@return
	 */
	protected function createComponentNoticeboardlister($name)
	{
		$options = array(
			'itemsPerPage' => 20,
			'lister_type' => array(
				ListerControlMain::LISTER_TYPE_RESOURCE
			),
			'template_body' => 'ListerControlMain_messages.phtml',
			'filter' => array(
				'type' => 11,
				'status' => 1
			),
			'refresh_path' => 'Administration:noticeboard',
			'template_variables' => array(
                    'hide_filter' => true,
                    'remove_enabled' => true
                    )
		);
		$control = new ListerControlMain($this, $name, $options);
		return $control;
	}


	/**
	 *	For the moderation of noticeboard messages
	 *	@param int $message_id
	 *	@return
	*/
	public function handleRemoveMessage($message_id)
	{
		if (NEnvironment::getUser()->getIdentity()->getAccessLevel() < 2) {
			echo "false";
			$this->terminate();
		}

		$resource = Resource::create($message_id);
		if (!empty($resource)) {
			if ($resource->remove_message()) {
				Activity::removeActivity(Activity::NOTICEBOARD_MESSAGE, $resource->getResourceId(), 3);
				echo "true";
				$storage = new NFileStorage(TEMP_DIR);
				$cache = new NCache($storage, "Lister.noticeboard");
				$cache->clean(array(NCache::ALL => TRUE));
				$cache = new NCache($storage, "Activity.stream");
				$cache->clean(array(NCache::ALL => TRUE));

			} else {
				echo "false";
			}
		} else {
			echo "false";
		}
		$this->terminate();	
	}
}
