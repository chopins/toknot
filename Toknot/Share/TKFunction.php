<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Object\TKFunction;
use Toknot\Exception\HeaderLocationException;

function header($string, $replace = true, $http_response_code = null) {
    if($_SERVER['TK_SERVER']) {
        $_SERVER['HEADERS_LIST'][] = $string;
        if(strpos($string, 'Location:') !== false) {
            throw new HeaderLocationException;
        }
    } else {
        \header($string, $replace, $http_response_code);
    }
}

function headers_list() {
    if($_SERVER['TK_SERVER']) {
        return $_SERVER['HEADERS_LIST'];
    } else {
        return \headers_list();
    }
}

function setcookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false,$httponly = false) {
    if($_SERVER['TK_SERVER']) {
        $_SERVER['COOKIES_LIST'][$name] = array($name, $value, $expire, $path, $domain, $secure, $httponly);
    } else {
        return \setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
}
