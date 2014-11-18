<?php

//Server
use Toknot\Control\Application;
use Toknot\Http\FastCGIServer;
require_once "../../../Toknot/Toknot.php";

$app = new Application;
$cgi = new FastCGIServer;
$cgi->registerApplicationInstance($app,"\AppAdmin",dirname(__DIR__));
$cgi->setWorkOnCurrentUser();
$cgi->startServer();
