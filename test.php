<?php

$st = microtime(true);

for($i=0;$i<10000;$i++) {
    $ini = file_get_contents(__DIR__.'/demos/AppAdmin/Data/Config/config.ini.cache', true);
    $a = unserialize($ini);
} 

$et= microtime(true);
echo $st."\n";
echo $et."\n";

echo $et - $st ."\n";