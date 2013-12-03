<?php
$file = file('/proc/net/tcp');
$tmp = array();
foreach($file as $k=> $line) {
    $connect = explode(' ',$line);
    $i = 0;
    if($k == 0) {
        continue;
    }
    foreach($connect as $v) {
        $v = trim($v);
        if(empty($v)) continue;
        if($i===0) {
            $v = $k >9 ? $k: '0'.$k;
        } elseif($i==1 || $i == 2) {
             list($ip,$port) = explode(':', $v);
             $v = implode('.', array_reverse(explode('.', long2ip(hexdec($ip))))).':'. hexdec($port);
        } elseif($i==4||$i==5) {
            list($t1,$t2) = explode(':', $v);
            $v = hexdec($t1).':'.hexdec($t2);
        } else {
            $v = hexdec($v);
        }
        $i++;
        $tmp[$k][] = $v;
        echo "$v   ";
    }
   echo "\n";
}
