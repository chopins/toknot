<?php
$boardList = array('a','sss','v','2232','ee','3daf','33', 'sdafa');

$newArray = array();
array_walk($boardList,function($value,$key) {
    global $newArray,$boardList;
    $key = $key+1;
    if($key>0 && $key %2 == 0) {
        $prekey = $boardList[$key-2];
        $newArray[$prekey] = $value;
    } else {
        $newArray[$value] = null;
    }
});

print_r($newArray);
