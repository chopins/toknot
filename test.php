<?php

////this is test file
include_once __DIR__.'/Toknot/Control/Application.php';
$app = new \Toknot\Control\Application();
////$cgi = $app->runCGIServer('testRouter');
////$cgi->registerApplicationRouter('testRouter');
////$cgi->setWorkOnCurrentUser();
////$cgi->startServer();
//
//$r = new \Toknot\Di\StringObject('test');
//var_dump($r->supportStringMethod());

throw new Toknot\Exception\BadClassCallException('test');

///**
// * 
// * @param mixed $expression
// */
//function debugPrint($expression) {
//    $arr = debug_backtrace(TRUE);
//    $filename = basename($arr[0]['file']);
//    $expression = print_r($expression, true);
//    //$pid = posix_getpid();
//    print("$pid:$filename:{$arr[0]['line']}:{$expression}\n");
//}

