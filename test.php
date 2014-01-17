<?php
use Toknot\Control\Application;
use Toknot\Http\FastCGIServer;
include_once __DIR__.'/Toknot/Toknot.php';

$app = new Application;
$cgi = new FastCGIServer;
$cgi->setWorkOnCurrentUser();
$cgi->startServer();
