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

    // Only crop if source and target aspect ratios are different
    if ($extra_px['w'] != $extra_px['h']){

      // If no focus points, crop as to pivot
      if (empty($this->focus)){
        $pivot = $this->getPlainPivot($dir);
      }
      else {
        $pivot = $this->getCentreMass();
      }

      // Crop Coordinates and Size Relative to Pivot
      $crop_factor = [
        'left' => $pivot['x'] / $this->width,
        'top'  => $pivot['y'] / $this->height,
      ];
      $crop = [
        'x' => round($extra_px['w'] * $crop_factor['left']),
        'y' => round($extra_px['h'] * $crop_factor['top']),
        'w' => round($this->width - $extra_px['w']),
        'h' => round($this->height - $extra_px['h'])
      ];

      //FocuspointFunc::debug($extra_px, $pivot, $crop_factor, $crop);die;

      FocuspointFunc::plotPv($this->file, $pivot);
    }

    FocuspointFunc::convert($this->file, $w, $h, $crop);
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

  private function getPlainPivot($dir){

    switch ($dir){
      case Pivot::CENTRE_MASS:
      case Pivot::CENTRE:
        $pivot = [
          'x' => $this->width / 2,
          'y' => $this->height / 2
        ];
        break;

      case Pivot::NORTH:
        $pivot = [
          'x' => $this->width / 2,
          'y' => 0
        ];
        break;

      case Pivot::SOUTH:
        $pivot = [
          'x' => $this->width / 2,
          'y' => $this->height
        ];
        break;

      case Pivot::EAST:
        $pivot = [
          'x' => $this->width,
          'y' => $this->height / 2
        ];
        break;

      case Pivot::WEST:
        $pivot = [
          'x' => 0,
          'y' => $this->height / 2
        ];
        break;

      case Pivot::NORTH_EAST:
        $pivot = [
          'x' => $this->width,
          'y' => 0
        ];
        break;

      case Pivot::SOUTH_EAST:
        $pivot = [
          'x' => $this->width,
          'y' => $this->height
        ];
        break;

      case Pivot::SOUTH_WEST:
        $pivot = [
          'x' => 0,
          'y' => $this->height
        ];
        break;

      default:
        // Default -> NW
        $pivot = [
          'x' => 0,
          'y' => 0
        ];
    }

    return $pivot;
  }

  private function getCentreMass() {

    $center_mass = ['x' => 0, 'y' => 0];
    $combined_masses = 0;

    foreach ($this->focus as $focus) {
      $mass              = $focus['width'] * $focus['height'];
      $center_mass['x'] += ($focus['x'] + ($focus['width']) / 2) * $mass;
      $center_mass['y'] += ($focus['y'] + ($focus['height']) / 2) * $mass;
      $combined_masses  += $mass;
    }

    $center_mass['x'] /= $combined_masses;
    $center_mass['y'] /= $combined_masses;

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

  static function convert($file, $w, $h, $crop = []) {
    $cmd = ["convert {$file} -quiet"];
    if (!empty($crop)) $cmd[] = "-crop {$crop['w']}x{$crop['h']}+{$crop['x']}+{$crop['y']} +repage";
    $cmd[] = "-resize {$w}x{$h}! output.jpg";

    exec(implode(" ", $cmd));
  }

  static function plotPv($file, $pt = ['x' => 0, 'y' => 0]) {
    $x = round($pt['x']);
    $y = round($pt['y']);
    $rad = $y + 5;

    $cmd = ["convert {$file} -quiet"];
    $cmd[] = "-fill purple -draw 'circle {$x},{$y} {$x},{$rad}'";
    $cmd[] = "debug.jpg";

    exec(implode(" ", $cmd));
  }

  static function debug(...$args) {
    foreach ($args as $arg) print_r($arg);
  }
}

// ---

interface Pivot {

  const CENTRE_MASS = 'cm';
  const CENTRE = 'c';
  const NORTH = 'n';
  const SOUTH = 's';
  const EAST = 'e';
  const WEST = 'w';
  const NORTH_EAST = 'ne';
  const SOUTH_EAST = 'se';
  const NORTH_WEST = 'nw';
  const SOUTH_WEST = 'sw';
}
