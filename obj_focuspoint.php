<?php

class Focuspoint{

  private $file, $focus = [], $width, $height, $dpi;

  public function setFocus($focus) {
    $this->focus = $focus;
  }

  public function loadImage($file) {
    $this->file = $file;

    $dim          = FocuspointFunc::identify($this->file);
    $this->width  = $dim['width'];
    $this->height = $dim['height'];
    $this->dpi    = $dim['dpi'];
  }

  public function prepareImage($w, $h, $level = 0, $dir = 'c', $dpi = 72) {
    // Focus Points Centre of Mass
    $centre_pt = $this->getCentreMass();
    $cropL_ratio = $centre_pt['x'] / $this->width;
    $cropT_ratio = $centre_pt['y'] / $this->height;

    // Crop Coordinates Relative to Pivot
    $extra_px = $this->calcExtraPx($w, $h);
    $cropX = round($extra_px['w'] * $cropL_ratio);
    $cropY = round($extra_px['h'] * $cropT_ratio);
    $cropW = round($this->width - $extra_px['w']);
    $cropH = round($this->height - $extra_px['h']);

    FocuspointFunc::crop($this->file, $cropW, $cropH, $cropX, $cropY, $w, $h);
  }

  private function calcExtraPx($trgt_w, $trgt_h) {
    $src_ratio = $this->width / $this->height;
    $trgt_ratio = $trgt_w / $trgt_h;

    if($src_ratio == $trgt_ratio){
      return [
        'w' => 0,
        'h' => 0
      ];
    }
    elseif($src_ratio > $trgt_ratio){
      return [
        'w' => $this->width - ($this->height * $trgt_ratio),
        'h' => 0
      ];
    }
    else{
      return [
        'w' => 0,
        'h' => $this->height - ($this->width * ($trgt_h / $trgt_w))
      ];
    }
  }

  private function getCentreMass() {
    $center_mass = ['x' => 0, 'y' => 0];
    $combined_masses = 0;

    if(!empty($this->focus)) {
      foreach ($this->focus as $focus) {
        $mass             = $focus['width'] * $focus['height'];
        $center_mass['x'] += (($focus['x'] + $focus['width']) / 2) * $mass;
        $center_mass['y'] += (($focus['y'] + $focus['height']) / 2) * $mass;
        $combined_masses  += $mass;
      }

      $center_mass['x'] /= $combined_masses;
      $center_mass['y'] /= $combined_masses;
    }
    else{
      $center_mass['x'] = $this->width / 2;
      $center_mass['y'] = $this->height / 2;
    }

    return $center_mass;
  }

}

// ---

class FocuspointFunc {

  static function identify($file) {
    if (file_exists($file)) {
      list ($width, $height, $dpi) = explode(' ', exec("identify -quiet -format '%w %h %x' {$file}"));

      return ['width' => $width, 'height' => $height, 'dpi' => $dpi];
    }
    else {
      return false;
    }
  }

  static function crop($file, $cW, $cH, $cX, $cY, $w, $h) {
    exec("convert {$file} -quiet -crop {$cW}x{$cH}+{$cX}+{$cY} +repage -resize {$w}x{$h}! test.jpg");
  }

}
