<?php

//Server
use Toknot\Control\Application;
use Toknot\Http\FastCGIServer;
include_once __DIR__.'/Toknot/Toknot.php';

$app = new Application;
$cgi = new FastCGIServer;
$cgi->registerApplicationInstance($app,"\AppAdmin",__DIR__.'/demos/AppAdmin');
$cgi->setWorkOnCurrentUser();
$cgi->startServer();


