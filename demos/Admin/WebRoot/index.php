<?php
use Toknot\Control\Application;

require_once dirname(dirname(dirname(__DIR__))).'/Toknot/Control/Application.php';

$app = new Application;
$app->run('\Admin',dirname(__DIR__));
