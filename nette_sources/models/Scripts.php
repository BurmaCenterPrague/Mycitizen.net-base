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
 

class Scripts {

	private $base_origin_url = '/';
	private $base_target_url = '/';
	private $base_target_path;
	private $script_url = array();


	public function __construct()
	{
		$this->script_url['js'] = array();
		$this->script_url['css'] = array();
	}


	/**
	 *	Sets the default URL of the source files
	 *	@param string $url
	 *	@return void
	 */
	 public function setBaseOriginUrl($url)
	 {
	 	if (substr($url,-1,1) != '/') {
	 		$url .= '/';
	 	}
	 	$this->base_origin_url = $url;
	 }


	/**
	 *	Sets the default URL of the target files (as used in the <head> section>
	 *	@param string $url
	 *	@return void
	 */
	 public function setBaseTargetUrl($url)
	 {
	 	if (substr($url,-1,1) != '/') {
	 		$url .= '/';
	 	}
	 	$this->base_target_url = $url;
	 }


	/**
	 *	Sets the default path of the target files (for writing the files)
	 *	@param string $url
	 *	@return void
	 */
	 public function setBaseTargetPath($path)
	 {
	 	if (substr($path,-1,1) != '/') {
	 		$path .= '/';
	 	}
	 	$this->base_target_path = $path;
	 }


	/**
	 *	Queues scripts for later combined output.
	 *	@param string|array $url
	 *	@param string @type ('css' or 'js')
	 */
	public function queueScript($type, $url)
	{
		$allowed_types = array('js', 'css');
		if (!in_array($type, $allowed_types)) {
			return false;
		}

		if (is_array($url)) {
			array_walk($url, array($this, 'addBase'));
			$this->script_url[$type] = array_merge($this->script_url[$type], $url);
		} else {
			$this->script_url[$type][] = $this->addBase($url);
		}
	}


	/**
	 *	Adds the base url if url appears to be relative.
	 *	@param $url (reference for callback)
	 *	@param $key to avoid warning
	 *	@return void
	 */
	 private function addBase(&$url, $key=null)
	 {
	 	if (substr($url,0,4) != 'http' && substr($url,0,2) != '//') {
			$url = $this->base_origin_url.$url;		
		}
		return $url;
	 }


	/**
	 *	Returns a link for HTML pointing to a file that combines all given scripts. Wrapped in html tags to be echoed in the <head> section.
	 *	Errors will be rendered as comments enclosed in <!-- and -->.
	 *	Files must be complete in themselves and not pointing to other folders or files (URL).
	 *	@param string $type ('css' or 'js')
	 *	@param bool $force Forces re-write of cached file.
	 *	@return void
	 */
	public function outputScripts($type, $force=false)
	{
		$allowed_types = array('js', 'css');
		if (!in_array($type, $allowed_types)) {
			return false;
		}
		if (count($this->script_url[$type]) == 0) return;
		
		$output = array();
		$cache_key = md5(json_encode($this->script_url[$type]));
		$file_path = $this->base_target_path.$type.'/combined-'.$cache_key.'.'.$type;
		$file_url = $this->base_target_url.$type.'/combined-'.$cache_key.'.'.$type;
		if (!file_exists($file_path) || $force) {
			// load all file contents
			foreach ($this->script_url[$type] as $script) {
				if (substr($script,0,2) == '//') {
					$script = 'http:'.$script;
				}
				$data[] = "\n\n/* source: ".$script." */\n\n";
				if ($content = @file_get_contents($script)) {
					$content = preg_replace('#^//@ sourceMappingURL=.*$#', '', $content);
					$data[] = $content;
					$data[] = "\n";
				} else {
					$data[] = "\n/* Error: Source file not found. */\n\n";
					$output[] = "<!-- Error reading ".$script." -->\n";
				}
			}
			// write them into one file
			if (!file_put_contents($file_path, $data)) {
				$output[] = "<!-- Error creating ".$file_path." -->\n";
			}
		}
		if ($type == 'css') {
			$output[] = '<link rel="stylesheet" href="'.$file_url.'" />';
		} elseif ($type == 'js') {
			$output[] = '<script type="text/javascript" src="'.$file_url.'"></script>';
		}
		$output[] = "\n";
		$this->script_url[$type] = array();
		
		return implode("\n", $output);
	}
}