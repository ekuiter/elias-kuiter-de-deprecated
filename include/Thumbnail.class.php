<?php

class Thumbnail {
  private $file;
 
  public function __construct($file) {
    $this->file = $file;
  }

  public function render($maxwidth = 0, $maxheight = 0) {
    if (!file_exists($this->file)) throw new Exception("File does not exist: " . htmlspecialchars($this->file));
    $imagesize = @getimagesize($this->file);
    $imagewidth = $imagesize[0];
    $imageheight = $imagesize[1];
    $imagetype = $imagesize[2];
    if ($imagetype === 1) $image = @imagecreatefromgif($this->file);
    if ($imagetype === 2) $image = @imagecreatefromjpeg($this->file);
    if ($imagetype === 3) $image = @imagecreatefrompng($this->file);
    if (!$image) throw new Exception("Image type not supported");
    $thumbwidth = $imagewidth;
    $thumbheight = $imageheight;
    if ($maxwidth > 0 && $thumbwidth > $maxwidth) {    
      $factor = $maxwidth / $thumbwidth;
      $thumbwidth *= $factor;
      $thumbheight *= $factor;
    }
    if ($maxheight > 0 && $thumbheight > $maxheight) {
      $factor = $maxheight / $thumbheight;
      $thumbwidth *= $factor;
      $thumbheight *= $factor;
    }
    $thumbnail = imagecreatetruecolor($thumbwidth, $thumbheight);
    imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $thumbwidth, $thumbheight, $imagewidth, $imageheight);
    header('Content-Type: image/jpeg');
    imagejpeg($thumbnail, null, 85);
    $path = $this->path($maxwidth, $maxheight);
    if ($this->file !== $path)
      imagejpeg($thumbnail, $path, 85);
    imagedestroy($thumbnail);
  }

   public function path($maxwidth = 0, $maxheight = 0) {
    if (!$this->file) return;
    if (stristr($this->file, '.gif')) $suffix = '.gif';
    if (stristr($this->file, '.jpg')) $suffix = '.jpg';
    if (stristr($this->file, '.png')) $suffix = '.png';
    if (!isset($suffix)) throw new Exception("Image type not supported");
    $path = str_replace($suffix, '', $this->file);
    $path .= $maxwidth === 0 && $maxheight === 0 ? $suffix : 
      ($maxwidth === 0 ? ".{$maxheight}h$suffix" : 
        ($maxheight === 0 ? ".{$maxwidth}w$suffix" : $suffix)
      );
    return $path;
  }
}

?>
