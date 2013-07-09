<?php

function strrand($min, $max = 0, $all = false) {
	if ($min < 1) {
		throw new StandardException("StringObject::rand() 1 parameter must greater 1, $min given");
	}
	if ($max > 0) {
		$len = mt_rand($min, $max);
	} else {
		$len = $min;
	}
	$char = '0987654321qwertyuiopasdfghjklmnbvcxzQWERTYUIUIOPLKJHGFDSAZXCVBNM';
	$len = 61;
	if ($all) {
		$char .= '~`!@#$%^&*()_+-={}|[]\\:";\',./<>?';
		$len = 93;
	}
	$randStr = '';
	for ($i = 0; $i < $len; $i++) {
		$randStr = $char[mt_rand(0, $len)];
	}
	return $randStr;
}

function genRandomString($len) {
	$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz-=/?~!@#$%^&*()";
	$charsLen = strlen($chars) - 1;
	$output = "";
	for ($i = 0; $i < $len; $i++) {
		$output .= $chars[mt_rand(0, $charsLen)];
	}
	return $output;
}

$startTime = microtime(true);
for ($i = 0; $i < 10000; $i++) {
	strrand(8);
}
echo microtime(true) - $startTime;
echo '|';
$startTime = microtime(true);
for ($i = 0; $i < 10000; $i++) {
	genRandomString(8);
}

echo microtime(true) - $startTime;