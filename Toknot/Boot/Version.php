<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

/**
 * Toknot Framework Version message
 */
final class Version {

    const VERSION = '4.0';
    const MAJOR_VERSION = '4';
    const MINOR_VERSION = '0';
    const RELEASE_VERSION = '0';
    const STATUS = 'dev';
    const REQUIRE_PHP_VERSION = '7';
    const VERSION_ID = '40000';

    public static function checkPHPVersion() {
        if (version_compare(PHP_VERSION, self::REQUIRE_PHP_VERSION, '<')) {
            echo 'Error : PHP Interpreter version must >= '.self::REQUIRE_PHP_VERSION .', current version is '.PHP_VERSION. PHP_EOL;
            exit(255);
        }
    }

}
