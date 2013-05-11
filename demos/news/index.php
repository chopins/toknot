<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

use Toknot\Control\Application;

require_once dirname(dirname(__DIR__)).'/Toknot/Control/Application.php';

$app = new Application;
$app->run('\news',__DIR__);
