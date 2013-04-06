<?php
//this is test file
include_once dirname(__FILE__).'/Toknot/Control/Application.php';
$app = new \Toknot\Control\Application();
//$app->runDefaultRouter();
//$app->runUserRouter('testRouter');
$cgi = $app->runCGIServer('testRouter');
$cgi->registerApplicationRouter('testRouter');
$cgi->setWorkOnCurrentUser();
$cgi->startServer();

function debugPrint($expression) {
    $arr= debug_backtrace(TRUE);
    $filename = basename($arr[0]['file']);
    $expression = print_r($expression, true);
    print("$filename:{$arr[0]['line']}:{$expression}\n");
}

function testRouter() {
    
}