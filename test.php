<?php
date_default_timezone_set('UTC');
$time = microtime(true);
echo $time . PHP_EOL;
class ap {
    
	public function plus($a, $b) {
	return $a+$b;
	}
}

$b = 10;
$a = 10;

for($i=0;$i<10000000;$i++) {
   $b = $b+$i;
   $ap = new ap();
   $a = $ap->plus($a,$b);
}

echo $a . PHP_EOL;
echo  $b . PHP_EOL;
echo microtime(true) - $time;
echo PHP_EOL;
