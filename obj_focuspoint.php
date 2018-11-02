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

      $focus_plane = $this->getFocusPlane();
      $pivot = $this->getPivot($focus_plane, $dir);

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
    }

    FocuspointFunc::convert($this->file, $w, $h, $crop);
  }

  private function getFocusPlane() {
    $plane = [
      'x' => 0,
      'y' => 0,
      'w' => $this->width,
      'h' => $this->height
    ];

    if (!empty($this->focus)){
      $focus = [];
      foreach ($this->focus as $key => $arr){
        $focus[] = array_merge($arr, [
          'max_x' => $arr['x'] + $arr['width'],
          'max_y' => $arr['y'] + $arr['height'],
        ]);
      }

      $min = [
        'x' => min(array_column($focus, 'x')),
        'y' => min(array_column($focus, 'y'))
      ];

      $max = [
        'x' => max(array_column($focus, 'max_x')),
        'y' => max(array_column($focus, 'max_y'))
      ];

      // Combine
      $plane = [
        'x' => $min['x'],
        'y' => $min['y'],
        'w' => $max['x'] - $min['x'],
        'h' => $max['y'] - $min['y']
      ];
    }

    return $plane;
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

  private function getPivot($plane, $dir){

    if($dir == Pivot::CENTRE_MASS && empty($this->focus)){
      $dir = Pivot::CENTRE;
    }

    switch ($dir){
      case Pivot::CENTRE_MASS:
        $pivot = $this->getCentreMass();
        break;

      case Pivot::NORTH:
        $pivot = [
          'x' => ($plane['w'] / 2) + $plane['x'],
          'y' => $plane['y']
        ];
        break;

      case Pivot::SOUTH:
        $pivot = [
          'x' => ($plane['w'] / 2) + $plane['x'],
          'y' => $plane['h'] + $plane['y']
        ];
        break;

      case Pivot::EAST:
        $pivot = [
          'x' => $plane['w'] + $plane['x'],
          'y' => ($plane['h'] / 2) + $plane['y']
        ];
        break;

      case Pivot::WEST:
        $pivot = [
          'x' => $plane['x'],
          'y' => ($plane['h'] / 2) + $plane['y']
        ];
        break;

      case Pivot::NORTH_EAST:
        $pivot = [
          'x' => $plane['w'] + $plane['x'],
          'y' => $plane['y']
        ];
        break;

      case Pivot::SOUTH_EAST:
        $pivot = [
          'x' => $plane['w'] + $plane['x'],
          'y' => $plane['h'] + $plane['y']
        ];
        break;

      case Pivot::NORTH_WEST:
        $pivot = [
          'x' => $plane['x'],
          'y' => $plane['y']
        ];
        break;

      case Pivot::SOUTH_WEST:
        $pivot = [
          'x' => $plane['x'],
          'y' => $plane['h'] + $plane['y']
        ];
        break;

      default:
        // Default -> Centre
        $pivot = [
          'x' => ($plane['w'] / 2) + $plane['x'],
          'y' => ($plane['h'] / 2) + $plane['y']
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

  static function debugPlot($file, $pv = [], $plane = []) {
    $cmd = ["convert {$file} -quiet"];

    if(!empty($pv)){
      $pvx = round($pv['x']);
      $pvy = round($pv['y']);
      $pv_rad = $pvy + 5;

      $cmd[] = "-fill purple -draw 'circle {$pvx},{$pvy} {$pvx},{$pv_rad}'";
    }

    if(!empty($plane)){
      $pt1 = ['x' => $plane['x'], 'y' => $plane['y']];
      $pt2 = [
        'x' => $plane['x'] + $plane['w'],
        'y' => $plane['y'] + $plane['h']
      ];

      $cmd[] = "-stroke purple -fill transparent -draw 'rectangle {$pt1['x']},{$pt1['y']} {$pt2['x']},{$pt2['y']}'";
    }

    $cmd[] = "debug.jpg";

    exec(implode(" ", $cmd));
  }

  static function debugVars(...$args) {
    foreach ($args as $arg){
      print_r($arg);
      echo PHP_EOL;
    }
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
