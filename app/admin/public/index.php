<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */
$path = dirname(dirname(dirname(__DIR__))) ;

include "$path/vendor/toknot/boot.php";
main("$path/app/admin");