<?php
class test {
	public function __construct() {
	}
	public function t() {
		
	}
}
echo 'ReflectionArray:';
$start = microtime(true);
$i = 0;
while($i<10000) {

    $r = new ReflectionClass('test');
	$a = $r->newInstanceArgs(array(1));

	$a->t(1);

	
	$i++;
}


$t = microtime(true) - $start;
echo $t;
echo "\n";

echo 'Reflection:';
$start = microtime(true);
$i = 0;
while($i<10000) {

    $r = new ReflectionClass('test');
	$a = $r->newInstance(1);

	call_user_func(array($a, 't'));

	
	$i++;
}


$t = microtime(true) - $start;
echo $t;
echo "\n";


echo 'new:';
$start = microtime(true);
$i = 0;
while ($i<10000) {
	$a = new test(1);
	$r = new ReflectionMethod($a,'t');
	$r->invokeArgs($a, array(1));
	$i++;
}
$t = microtime(true) - $start;
echo $t;

echo "\n";
echo 'eval:';
$i = 0;
$start = microtime(true);
while ($i<10000) {
	eval('$a = new test(1);');

	$i++;
}

$t = microtime(true) - $start;
echo $t;
echo "\n";
echo 'variable:';
$i = 0;
$start  = microtime(true);
while ($i<10000) {
	$class = 'test';
	$a = new $class(1);
	$i++;
}
$t = microtime(true) - $start;
echo $t;