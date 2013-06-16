<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

/**
 * The file is application enter file
 * 
 */

use Toknot\Control\Application;

define('DEVELOPMENT', false);
require_once dirname(dirname(dirname(__DIR__))).'/Toknot/Control/Application.php';

$app = new Application;
$app->run('\Shop',dirname(__DIR__));