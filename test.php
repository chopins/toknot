<?php


$s = microtime(true);

for($i=0;$i<1000;$i++) {
	$ref = new ReflectionExtension('standard');
	$ref->getFunctions();
}
var_dump(microtime(true) - $s);

$s = microtime(true);

for($i=0;$i<1000;$i++) {
	get_extension_funcs('standard');

}
var_dump(microtime(true) - $s);