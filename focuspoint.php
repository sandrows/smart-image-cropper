<?php
require_once ('obj_focuspoint.php');

$f = new Focuspoint();
$f->loadImage('example.jpg');

$areas = '[
  {"id":1, "width":73, "height":62, "x":35.5,  "y":255, "z":0},
  {"id":2, "width":50, "height":93, "x":180.5, "y":158, "z":0},
  {"id":3, "width":73, "height":77, "x":281.5, "y":161, "z":0}
]';

$focus_arr = json_decode($areas, true);
$f->setFocus($focus_arr);

$f->prepareImage(200, 300);
