<?php return array (
  'map0' => 
  array (
    'pattern' => '/User/(.*)/Profile',
    'action' => '\\User\\Info',
  ),
  'map1' => 
  array (
    'pattern' => '/User/(.*)/Avatar',
    'action' => '\\User\\Info',
  ),
  'map2' => 
  array (
    'pattern' => '/User/(.*)/Password',
    'action' => '\\User\\Safe',
  ),
);