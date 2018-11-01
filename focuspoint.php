<?php
require_once ('obj_focuspoint.php');

$fid = 1;

$f = new Focuspoint();
$f->loadImage("img/{$fid}.jpg");

$areas = [
  '1' => '[
    {"id":1, "width":182, "height":263, "x":134,  "y":54, "z":0},
    {"id":2, "width":193, "height":228, "x":409, "y":277, "z":0}
  ]',

  '2' => '[
    {"id":1, "width":193, "height":228, "x":409, "y":277, "z":0},
    {"id":2, "width":264, "height":372, "x":643, "y":266, "z":0}
  ]',

  '3' => '[
    {"id":1, "width":182, "height":263, "x":134,  "y":54, "z":0},
    {"id":2, "width":102, "height":102, "x":599,  "y":95, "z":0},
    {"id":3, "width":154, "height":175, "x":744,  "y":0, "z":0}
  ]',

  '4' => '[
    {"id":1, "width":264, "height":372, "x":643, "y":266, "z":0}
  ]',

  '5' => '[
    {"id":1, "width":182, "height":263, "x":134,  "y":54, "z":0},
    {"id":1, "width":277, "height":140, "x":977,  "y":244, "z":0}
  ]',
];

$focus_arr = json_decode($areas[$fid], true);
$f->setFocus($focus_arr);

$f->prepareImage(400, 500);
