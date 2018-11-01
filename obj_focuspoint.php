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

  public function prepareImage($w, $h, $dir = Pivot::CENTRE_MASS, $level = 0, $dpi = 72) {

    $crop = [];
    $extra_px = $this->calcExtraPx($w, $h);

    // If source and target aspect ratios are equal, then resize only.
    if ($extra_px['w'] != $extra_px['h']){
      // Focus Points Centre of Mass
      $centre_pt = $this->getCentreMass();

      $crop_factor = [
        'left' => $centre_pt['x'] / $this->width,
        'top'  => $centre_pt['y'] / $this->height,
      ];

      // Crop Coordinates and Size Relative to Pivot
      $crop = [
        'x' => round($extra_px['w'] * $crop_factor['left']),
        'y' => round($extra_px['h'] * $crop_factor['top']),
        'w' => round($this->width - $extra_px['w']),
        'h' => round($this->height - $extra_px['h'])
      ];
    }

    FocuspointFunc::convert($this->file, $w, $h, $crop);
  }

  private function getCentreMass() {

    $center_mass = ['x' => 0, 'y' => 0];
    $combined_masses = 0;

    if(!empty($this->focus)) {
      foreach ($this->focus as $focus) {
        $mass             = $focus['width'] * $focus['height'];
        $center_mass['x'] += $focus['x'] * $mass;
        $center_mass['y'] += $focus['y'] * $mass;
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

  static function convert($file, $w, $h, $crop = []) {
    $cmd = ["convert {$file} -quiet"];
    if (!empty($crop)) $cmd[] = "-crop {$crop['w']}x{$crop['h']}+{$crop['x']}+{$crop['y']} +repage";
    $cmd[] = "-resize {$w}x{$h}! test.jpg";

    exec(implode(" ", $cmd));
  }
}

// ---

interface Pivot {

  const CENTRE_MASS = 'cm';
  const NORTH = 'n';
  const SOUTH = 's';
  const EAST = 'e';
  const WEST = 'w';
  const NORTH_EAST = 'ne';
  const SOUTH_EAST = 'se';
  const NORTH_WEST = 'nw';
  const SOUTH_WEST = 'sw';
}
