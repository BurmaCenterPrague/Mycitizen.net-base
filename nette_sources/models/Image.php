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
	
	/**
	 *	Removes the cached images from the folder.
	 *	@param int $id object id
	 *	@param int $type user, group or resource
	 *	@return void
	 */
	public function __construct($id, $type) {
		$this->id = $id;
		$this->type = $type;
	}
	
	
	/**
	 *	Removes the cached images from the folder.
	 *	@param void
	 *	@return void
	 */
	public function remove_cache() {
		if ($this->type == 1 ) {
			$object = User::create($this->id);
			$name = 'user';
		} elseif ($this->type == 2 ) {
			$object = Group::create($this->id);
			$name = 'group';
		} else {
			return _t("Error recreating image.");
		}

		if (isset($object)) {
			$sizes = array('img', 'icon', 'large_icon');
		
			foreach ( $sizes as $size) {
				switch ($size) {
					case 'img': $src = $object->getAvatar(); break;
					case 'icon': $src = $object->getIcon(); break;
					case 'large_icon': $src = $object->getBigIcon(); break;
					default: $this->terminate(); break;
				}

				if (!empty($src)) {
					$hash=md5($src);
					$link = NEnvironment::getVariable("URI") . '/images/cache/'.$name.'/'.$this->id.'-'.$size.'-'.$hash.'.jpg';

					if(file_exists($link)) {
						if (!unlink($link)) {
							return _t("Error deleting image from cache: ").$link;
						}
					}
				}
			}
			return true;
		}
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
			return _t("Error recreating image.");
		}
		
		if (isset($object)) {
		
			$sizes = array('img', 'icon', 'large_icon');
		
			foreach ( $sizes as $size) {
		
				switch ($size) {
					case 'img': $src = $object->getAvatar(); break;
					case 'icon': $src = $object->getIcon(); break;
					case 'large_icon': $src = $object->getBigIcon(); break;
				}
	
				if (!empty($src)) {
					$hash=md5($src);
					$link = WWW_DIR . '/images/cache/'.$name.'/'.$this->id.'-'.$size.'-'.$hash.'.jpg';
		
					if(!file_exists($link)) {
						$img_r = @imagecreatefromstring(base64_decode($src));
						if (!imagejpeg($img_r, $link)) {
							return _t("Error writing image: ").$link;
						}
					}
				}

			}
			return true;
		}
	}


	/**
	 *	Crops and scales the image from the database for 3 sizes and saves them back to database.
	 *	@param void
	 *	@return void
	 */	
	public function crop($x, $y, $w, $h) {
		if ($this->type == 1) {
			$object = new User($this->id);
			$name = 'user';
		} else {
			$object = new Group($this->id);
			$name = 'group';
		}

		if (!empty($object)) {
			$data = base64_decode($object->getAvatar());
		
			if (isset($data)) {
				// target sizes
				$avatar_w = 160;
				$avatar_h = 200;
				$large_icon_w = 40;
				$large_icon_h = 50;
				$icon_w = 20;
				$icon_h = 25;
				// ->sharpen() causes problems for CMYK?
				$avatar = base64_encode(NImage::fromString($data)->crop($x, $y, $w, $h)->resize($avatar_w, $avatar_h)->toString(IMAGETYPE_JPEG,90));
				$large_icon = base64_encode(NImage::fromString($data)->crop($x, $y, $w, $h)->resize($large_icon_w, $large_icon_h)->toString(IMAGETYPE_JPEG,90));
				$icon = base64_encode(NImage::fromString($data)->crop($x, $y, $w, $h)->resize($icon_w, $icon_h)->toString(IMAGETYPE_JPEG,90));
				
				$values = array (
					$name.'_portrait' => $avatar,
					$name.'_largeicon' => $large_icon,
					$name.'_icon' => $icon,
					);

				if ($this->type == 1) {			
					$object->setUserData($values);
				} else {
					$object->setGroupData($values);
				} 
				$object->save();			
			}
		}
	}


	/**
	 *	Creates html img tag to retrieve image from cache
	 *	@param string $size
	 *	@param string $title
	 *	@return string
	 */
	public function toImg($size='img', $title=null) {
		if (isset($title)) $title_tag =' title="'.$title.'"'; else $title_tag='';

		if ($this->type == 1) {
			$object = new User($this->id);
			$name = 'user';
		} else {
			$object = new Group($this->id);
			$name = 'group';
		}
		
		switch ($size) {
			case 'img': $src = $object->getAvatar(); $width=160; break;
			case 'icon': $src = $object->getIcon(); $width=20; break;
			case 'large_icon': $src = $object->getBigIcon(); $width=40; break;
		}
		
		if (!empty($src) && (Auth::isAuthorized($this->type, $this->id)>0)) {
			$hash=md5($src);
			$link = NEnvironment::getVariable("URI") . '/images/cache/'.$name.'/'.$this->id.'-'.$size.'-'.$hash.'.jpg';
			$image = '<img src="'.$link.'" width="'.$width.'"'.$title_tag.'/>';
		} else {
			// default image
			$image = '<img src="' . NEnvironment::getVariable("URI") . '/images/user-'.$size.'.png" width="'.$width.'"'.$title_tag.'/>';
		}
		return $image;
	}
}