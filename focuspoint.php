<?php
require_once ('obj_focuspoint.php');

// Demo images from 0-5
$fid = 3;

$f = new Focuspoint();
$f->loadImage("img/{$fid}.jpg");

// Focus points according to each demo
$areas = json_decode( file_get_contents('focuspoints.json'), true );
$f->setFocus($areas[$fid]);

$text = [
  [
    'family' => 'Ubuntu Mono',
    'pointsize' => '50',
    'fill' => 'purple',
    'text' => 'Text 1',
    'gravity' => 'southwest'
  ],
  [
    'family' => 'DejaVu Sans Mono',
    'fill' => 'white',
    'pointsize' => '40',
    'weight' => '800',
    'style' => 'Italic',
    'text' => 'Text 2\nLine 2',
    'gravity' => 'northeast'
  ]
];

$f->prepareImage(720, 300, 'c', $text);
