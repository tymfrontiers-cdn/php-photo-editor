<?php
namespace TymFrontiers\Helper;

trait PhotoEditor{

  public $image_thumb_size = [250,0];
  public $image_watermark;
  public $image_watermark_pos_x = 10;
  public $image_watermark_pos_y = 10;

  private $_croppable_image_files = ['image/png','image/jpeg','image/gif','image/bmp','image/tiff'];


  public function imageCreateFromAny($filepath) {
    $type = \exif_imagetype($filepath); // [] if you don't have exif you could use getImageSize()
		$allowed_types = [1,2,3,6];

    if (!\in_array($type, $allowed_types)) {  return false;  }
    switch ($type) {
        case 1 :
            $im = \imagecreatefromgif($filepath);
        break;
        case 2 :
            $im = \imagecreatefromjpeg($filepath);
        break;
        case 3 :
            $im = \imagecreatefrompng($filepath);
        break;
        case 6 :
            $im = \imagecreatefromwbmp($filepath);
        break;
    }
    return $im;
	}
  public function cropImage(){
  	$prop = $this->crop_img;
		if( \is_file($this->fullPath()) && \is_array($prop) ){
			$type = \exif_imagetype($this->fullPath());
			$img = $this->imageCreateFromAny($this->fullPath());
			$real_width = \imagesx($img);
			$real_height = \imagesy($img);
			$width = (\array_key_exists('w',$prop) && (int)$prop['w'] > 10) ? (int)$prop['w'] : 280;
			$height = (\array_key_exists('h',$prop) && (int)$prop['h'] > 10) ? (int)$prop['h'] : 280;
			$x_axis = \array_key_exists('x',$prop) ? (int)$prop['x'] : 0;
			$y_axis = \array_key_exists('y',$prop) ? (int)$prop['y'] : 0;

			for($i=$x_axis; $i+$width >= $real_width; $i-- ){	$x_axis = (int)$i;	}
			for($i=$y_axis; $i+$height >= $real_height; $i-- ){	$y_axis = (int)$i;	}

			$crop_prop = [
				"x"	=> $x_axis,
				"y"	=> $y_axis,
				"width"	=> $width,
				"height" => $height
			];
			$cropped = \imagecrop($img, $crop_prop);
			// $type = exif_imagetype($filename);
			switch ($type) {
        case 1 :
          \imagegif($cropped, $this->fullPath(), 100);
        break;
        case 2 :
          \imagejpeg($cropped, $this->fullPath(), 100);
        break;
        case 3 :
          \imagepng($cropped, $this->fullPath(), 9);
        break;
        case 6 :
          \imagewbmp($cropped, $this->fullPath(), 100);
        break;
				default: return false;
	    }
			// $resizer = new \Gumlet\ImageResize($this->fullPath());
			// $resizer->resizeToWidth($this->image_thumb_size[0]);
			// $resizer->save($this->_path."thumb_{$this->_name}");
			if( !empty($this->watermark_img) && \is_file($this->watermark_img)){ $this->watermarkImage(); }
			\imagedestroy($cropped);
			return true;
		}
		return false;
	}
  public function watermarkImage(string $wm, string $pos = "center"){
    if (!\file_exists())
  	$type = \exif_imagetype($this->fullPath());
    // $filename = "testfile.jpg";
    $filename = $this->fullPath();
		$stamp = $this->imageCreateFromAny($this->image_watermark);
		$img = $this->imageCreateFromAny($this->fullPath());
		if(!$img || !$stamp){ return false; }
    if( \file_exists($this->fullPath()) ) @ \unlink($this->fullPath());
		// Set the margins for the stamp and get the height/width of the stamp image
		$marge_right = $this->image_watermark_pos_x;
		$marge_bottom = $this->image_watermark_pos_y;
		$sx = \imagesx($stamp);
		$sy = \imagesy($stamp);

		// Copy the stamp image onto our photo using the margin offsets and the photo
		// width to calculate positioning of the stamp.
		\imagecopy($img, $stamp, \imagesx($img) - $sx - $marge_right, \imagesy($img) - $sy - $marge_bottom, 0, 0, \imagesx($stamp), \imagesy($stamp));
    $return = false;
		switch ($type) {
        case 1 :
            if( \imagegif($img, $filename, 100) ){ $return = true; };
        break;
        case 2 :
            if( \imagejpeg($img, $filename, 100) ){ $return = true; }
        break;
        case 3 :
            if( \imagepng($img, $filename, 9)){ $return = true; }
        break;
        case 6 :
            if( \imagewbmp($img, $filename, 100) ){ $return = true; }
        break;
				default: $return = false;
    }
		\imagedestroy($img);
		\imagedestroy($stamp);
    return $return;
	}
	public function rotateImage(int $degree = 90){
		if( $this->type_group == "image" ){
			$type = \exif_imagetype($this->fullPath());
			$img = $this->imageCreateFromAny($this->fullPath());
			$rotate = \imagerotate($img, $degree, 0);
			switch ($type) {
        case 1 :
          \imagegif($rotate, $this->fullPath(), 100);
        break;
        case 2 :
          \imagejpeg($rotate, $this->fullPath(), 100);
        break;
        case 3 :
          \imagepng($rotate, $this->fullPath(), 9);
        break;
        case 6 :
          \imagewbmp($rotate, $this->fullPath(), 100);
        break;
				default: return false;
	    }
			// $resizer = new \Gumlet\ImageResize($this->fullPath());
			// $resizer->resizeToWidth($this->image_thumb_size[0]);
			// $resizer->save($this->_path."thumb_{$this->_name}");
			\imagedestroy($img);
			\imagedestroy($rotate);
			return true;
		}
		return false;
	}
  public function resizeImage(int $w=0, int $h=0){
    $rz = new \Gumlet\ImageResize($this->fullPath());
    if( $w && $h ){
      $rz->resize($w, $h, true);
    }else if( $h && !$w ){
      $rz->resizeToHeight($h);
    }elseif($w && !$h){
      $rz->resizeToWidth($w);
    }else{
      throw new \Exception("Provide one or both of width/height for resize.", 1);
    }
    $rz->save($this->fullPath());
    if( !empty($this->id)  ){
      $this->size( \filesize($this->fullPath()) );
      $this->update();
    }
    return true;
  }
  protected function _wmPosition (string $setting) {
    $pos = ["x"=>10];
  }
}
