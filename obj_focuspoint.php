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

  public function prepareImage($w, $h, $dir = Pivot::CENTRE_MASS, $text = [], $level = 0, $dpi = 72) {

    $crop = [];

    // Calculate how much pixels to crop, depending on source and target aspect ratios
    $extra_px = $this->calcExtraPx($w, $h);

    // Only crop if source and target aspect ratios are different, else resize only
    if ($extra_px['w'] != $extra_px['h']){

      // Combine focus points to one big area
      $focus_plane = $this->getFocusPlane();

      // Set the cropping point relative to the plane and as per the direction
      $pivot = $this->getPivot($focus_plane, $dir);

      // How much to crop relative to pivot
      $crop_factor = [
        'left' => $pivot['x'] / $this->width,
        'top'  => $pivot['y'] / $this->height,
      ];

      // Crop dimensions
      $crop = [
        'x' => round($extra_px['w'] * $crop_factor['left']),
        'y' => round($extra_px['h'] * $crop_factor['top']),
        'w' => round($this->width - $extra_px['w']),
        'h' => round($this->height - $extra_px['h'])
      ];
    }

    FocuspointFunc::convert($this->file, $w, $h, $crop, $text);
  }

  private function calcExtraPx($trgt_w, $trgt_h) {

    // Only crop from one side, so there is no unnecessary cropping done,

    $src_ratio = $this->width / $this->height;
    $trgt_ratio = $trgt_w / $trgt_h;

    if($src_ratio == $trgt_ratio){
      // No cropping because source and target aspect ratios are the same
      return [
        'w' => 0,
        'h' => 0
      ];
    }
    elseif($src_ratio > $trgt_ratio){
      // Horizontal crop
      return [
        'w' => $this->width - ($this->height * $trgt_ratio),
        'h' => 0
      ];
    }
    else{
      // Vertical crop
      return [
        'w' => 0,
        'h' => $this->height - ($this->width * ($trgt_h / $trgt_w))
      ];
    }
  }

  private function getFocusPlane() {

    // Defaults to the whole image
    $plane = [
      'x' => 0,
      'y' => 0,
      'w' => $this->width,
      'h' => $this->height
    ];

    // Calculate nearest and furthest points to get the plane
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

  private function getPivot($plane, $dir){

    // Centre of mass depends on focus points, so if none exists fallback to centre
    if($dir == Pivot::CENTRE_MASS && empty($this->focus)){
      $dir = Pivot::CENTRE;
    }

    // Calculate the pivot coordinates on the plane depending on the direction
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
    // Calculate the weighted average of all focus points depending on their masses

    $centre_mass = ['x' => 0, 'y' => 0];
    $combined_masses = 0;

    foreach ($this->focus as $focus) {
      $mass              = $focus['width'] * $focus['height'];
      $centre_mass['x'] += ($focus['x'] + ($focus['width']) / 2) * $mass;
      $centre_mass['y'] += ($focus['y'] + ($focus['height']) / 2) * $mass;
      $combined_masses  += $mass;
    }

    $centre_mass['x'] /= $combined_masses;
    $centre_mass['y'] /= $combined_masses;

    return $centre_mass;
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

  static function convert($file, $w, $h, $crop, $text) {
    $cmd = ["convert {$file} -quiet"];

    // Crop if necessary
    if (!empty($crop)){
      $cmd[] = "-crop {$crop['w']}x{$crop['h']}+{$crop['x']}+{$crop['y']} +repage";
    }

    // Add text if available
    if (!empty($text)){
      $cmd[] = self::textParser($text);
    }

    // Finally, resize
    $cmd[] = "-resize {$w}x{$h}! output.jpg";

    exec(implode(" ", $cmd));
  }

  static function textParser($text){
    $defaults = [
      'family' => 'Arial',
      'fill' => 'black',
      'pointsize' => '12',
      'weight' => '400',
      'style' => 'Normal',
      'gravity' => 'center'
    ];

    $cmd = [];
    foreach ($text as $line){

      // If no text string
      if (empty($line['text'])) continue;

      $str = $line['text'];
      unset($line['text']);

      // Include all the defaults to reset each line's options
      $line = array_merge($defaults, $line);

      foreach ($line as $arg => $value){
        if (empty($value)) $value = $defaults[$arg];

        // Font names contain spaces, wrap in quotes
        if ($arg == 'family') $value = "'{$value}'";

        $cmd[] = "-{$arg} $value";
      }

      $cmd[] = "-annotate 0 '{$str}'";
    }

    return implode(" ", $cmd);
  }

  static function debugPlot($file, $pv = [], $plane = []) {
    // Outputs a debug image with the pivot/plane drawn on top of it
    // Pivot has to be x,y
    // Plane has to be x,y,w,h

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
    // Helper to print multiple vars
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
