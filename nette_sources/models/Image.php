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
 

class Image extends BaseModel
{

	public $type;
	public $id;
	public $width;
	public $height;
	public $mime_type;
	
	private $data; // src for largest available image
	private $img;
	private $large_icon;
	private $icon; 
	private $name;
	private $src;
	private $defined_width;
	private $defined_height;
	
	
	public static function createimage($id, $type) {
		if ($type == 1) {
			$object = new User($id);
		} else {
			$object = new Group($id);
		}
		if (empty($object)) {
			return false;
		} else {
			$data = base64_decode($object->getAvatar());
		}

		if (empty($data)) {
			return false;
		}

		// check result
		$img = @imagecreatefromstring($data);
		if ($img === false) {
			return false;
		}
		
		$image = new Image;
		$image->id = $id;
		$image->type = $type;
		$image->data = $data;
		$image->width = imagesx($img);
		$image->height = imagesy($img);
		$image->src = array(
			'img' => $object->getAvatar(),
			'large_icon' => $object->getLargeIcon(),
			'icon' => $object->getIcon()
			);
		$image->defined_width = array(
			'img' => 160,
			'large_icon' => 40,
			'icon' => 20
			);
		$image->defined_height = array(
			'img' => 200,
			'large_icon' => 50,
			'icon' => 25
			);
		if ($image->type == 1) {
			$image->name = 'user';
		} else {
			$image->name = 'group';
		}
		$f = finfo_open();
		$image->mime_type = finfo_buffer($f, $image->data, FILEINFO_MIME_TYPE);

		return $image;
	}
	
	
	/**
	 *	Removes the cached images from the folder.
	 *	@param int $id object id
	 *	@param int $type user, group or resource
	 *	@return void
	 */
	public function __construct()
	{
	}
	
	public function save_data() {
	
		$values = array();

		if (isset($this->img) && !empty($this->img)) {
			$values[$this->name.'_portrait'] = base64_encode($this->img);
		}
		if (isset($this->large_icon) && !empty($this->large_icon)) {
			$values[$this->name.'_largeicon'] = base64_encode($this->large_icon);
		}
		if (isset($this->icon) && !empty($this->icon)) {
			$values[$this->name.'_icon'] = base64_encode($this->icon);
		}
		if (empty($values)) {
			$values[$this->name.'_portrait'] = base64_encode($this->data);
		}
		
		if ($this->type == 1) {
			$object = new User($this->id);
			$object->setUserData($values);
		} else {
			$object = new Group($this->id);
			$object->setGroupData($values);
		}
		$object->save();
		
		return $this;
	}


	/**
	 *	Removes the cached images from the folder.
	 *	@param void
	 *	@return void
	 */
	public function remove_cache() {
		if ($this->type == 1 ) {
			$name = 'user';
		} elseif ($this->type == 2 ) {
			$name = 'group';
		} else {
			return _t("Error deleting image from cache.");
		}

		$files = glob(WWW_DIR . '/images/cache/'.$name.'/'.$this->id.'-*.jpg');
		if (is_array($files)) {
			foreach ($files as $file) {
				unlink($file);
			}
		}
		return $this;
	}


	/**
	 *	Saves the three sizes of the image to the cache folder.
	 *	@param void
	 *	@return void
	 */
	public function create_cache() {
		if ($this->type == 1 ) {
			$object = User::create($this->id);
			$name = 'user';
		} elseif ($this->type == 2 ) {
			$object = Group::create($this->id);
			$name = 'group';
		} else {
			return "Error recreating image.";
		}
		
		if (isset($object)) {
		
			$sizes = array('img', 'icon', 'large_icon');
		
			foreach ( $sizes as $size) {
		
				switch ($size) {
					case 'img': $src = $object->getAvatar(); break;
					case 'icon': $src = $object->getIcon(); break;
					case 'large_icon': $src = $object->getLargeIcon(); break;
				}
	
				if (!empty($src)) {
					$hash=md5($src);
					$link = WWW_DIR . '/images/cache/'.$name.'/'.$this->id.'-'.$size.'-'.$hash.'.jpg';
		
					if(!file_exists($link)) {
						$img_r = @imagecreatefromstring(base64_decode($src));
						if ($img_r === false || !imagejpeg($img_r, $link)) {
							return _t("Error writing image: ").$link;
						}
					}
				}

			}
			return true;
		}
	}


	/*
	 *	enlarges the image so that both dimensions have the minimum size
	 */
	public function fill_canvas()
	{
		$min_w = 160;
		$min_h = 200;
		$image_o = NImage::fromString($this->data);
		if ($image_o->width < $min_w ) {
			$this->data = $image_o->resize($min_w, NULL, NImage::ENLARGE)->toString(IMAGETYPE_JPEG,90);
		} elseif ($image_o->height < $min_h ) {
			$this->data = $image_o->resize(NULL, $min_h, NImage::ENLARGE)->toString(IMAGETYPE_JPEG,90);
		}
		
		return $this;
	}


	/**
	 *	recreates src for img
	 */
	public function src()
	{
		
		return base64_encode($this->data);
	}


	/**
	 *	resize
	 */
	public function resize($x,$y)
	{
		$this->data = NImage::fromString($this->data)->resize($x, $y)->toString(IMAGETYPE_JPEG,90);
		return $this;
	}


	/**
	 *	Crops and scales the image from the database for 3 sizes
	 *	@param void
	 *	@return void
	 */	
	public function crop($x, $y, $w, $h) {
		// target sizes
		$avatar_w = 160;
		$avatar_h = 200;
		$large_icon_w = 40;
		$large_icon_h = 50;
		$icon_w = 20;
		$icon_h = 25;
		$image_o = NImage::fromString($this->data)->sharpen();
		// ->sharpen() causes problems for CMYK?
		$this->img = $image_o->crop($x, $y, $w, $h)->resize($avatar_w, $avatar_h)->toString(IMAGETYPE_JPEG,90);
		$image_o = NImage::fromString($this->data);
		$this->large_icon = $image_o->crop($x, $y, $w, $h)->resize($large_icon_w, $large_icon_h)->toString(IMAGETYPE_JPEG,90);
		$image_o = NImage::fromString($this->data);
		$this->icon = $image_o->crop($x, $y, $w, $h)->resize($icon_w, $icon_h)->toString(IMAGETYPE_JPEG,90);
		return $this;
	}


	/**
	 *	Creates html img tag to retrieve image from cache
	 *	@param string $size
	 *	@param string $title
	 *	@return string
	 */
	public function renderImg($size='img', $title=null) {
		if (isset($title)) $title_tag =' title="'.$title.'"'; else $title_tag='';
		if (isset($this->src[$size])) {
			$src = $this->src[$size];
		}
		if ($size=='img' && empty($src)) {
			$src = $this->data;
		}

		$width = $this->defined_width[$size];
		
//		if (!empty($src) && (Auth::isAuthorized($this->type, $this->id) > Auth::UNAUTHORIZED)) {
		if (!empty($src)) {
			$hash = md5($src);
			$link = NEnvironment::getVariable("URI") . '/images/cache/'.$this->name.'/'.$this->id.'-'.$size.'-'.$hash.'.jpg';
			if (file_exists(WWW_DIR . '/images/cache/'.$this->name.'/'.$this->id.'-'.$size.'-'.$hash.'.jpg')) {
				$image = '<img src="'.$link.'" width="'.$width.'"'.$title_tag.'/>';
			} else {
				$avatar_w = $this->defined_width[$size];
				$avatar_h = $this->defined_height[$size];
				$img = @imagecreatefromstring($this->data);
				if ($img !== false) {
					$image_o = new NImage($img);
					$avatar = base64_encode($image_o->resize($avatar_w, $avatar_h)->toString(IMAGETYPE_JPEG,90));
					$image = '<img src="data:'.$this->mime_type.';base64,'.$avatar.'" '.$title_tag.'/>';
				} else {
					// default image
					$image = '<img src="' . NEnvironment::getVariable("CDN") . '/images/user-'.$size.'.png" width="'.$width.'"'.$title_tag.'/>';
				}
			}
		} else {
			// default image
			$image = '<img src="' . NEnvironment::getVariable("CDN") . '/images/user-'.$size.'.png" width="'.$width.'"'.$title_tag.'/>';
		}
		return $image;
	}


	/**
	 *	returns default image
	 */
	public static function default_img($size,$title=null)
	{
		$defined_width = array(
			'img' => 160,
			'large_icon' => 40,
			'icon' => 20
			);
		$width = $defined_width[$size];
		if (isset($title)) $title_tag =' title="'.$title.'"'; else $title_tag='';
		return '<img src="' . NEnvironment::getVariable("CDN") . '/images/user-'.$size.'.png" width="'.$width.'"'.$title_tag.'/>';
	}
}