<?php
use Toknot\Control\Application;
//use Toknot\Control\Router;

//If developement set true, product set false
define('DEVELOPMENT', false);
require_once "/home/chopin/NetBeansProjects/toknot/Toknot/Control/Application.php";

$app = new Application;
//$app->setRouterArgs(Router::ROUTER_PATH, 0);
$app->run("\AppAdmin",dirname(__DIR__));