<?php
use Toknot\Control\Application;
use Toknot\Control\Router;

//If developement set true, product set false
define('DEVELOPMENT', true);
require_once "../../../Toknot/Control/Application.php";

$app = new Application;

/**
the first paramter of function what is router mode that value maybe is below:
Router::ROUTER_PATH         is default, the path similar class full name with namespace
                            the URI un-match-part use FMAI::getParam() which pass
                            index of order
Router::ROUTER_GET_QUERY    is router use $_GET['r']
Router::ROUTER_MAP_TABLE    is use Config/router_map.ini, the file is ini configure
                            key is pattern, value is class full name with namespace
                            use FMAI::getParam() get match sub
*/
$app->setRouterArgs(Router::ROUTER_PATH, 2);
$app->run("\Shop",dirname(__DIR__));