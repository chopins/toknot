<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2007 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */
include '../../vendor/toknot/boot.php';
include 'TestCase.php';
define('TKROOT', realpath('../../vendor/toknot/'));
include TKROOT . '/Toknot/Boot/Object.php';
include TKROOT . "/Toknot/Boot/Kernel.php";
$kernel = Kernel::single($argc, $argv);
