<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\Service;

use Toknot\Boot\SystemCallWrapper;
use Toknot\Boot\Object;
use Toknot\Boot\Tookit;

/**
 * Wapper
 *
 * @author chopin
 */
class Wrapper extends Object implements SystemCallWrapper {

    protected $pathInfo = [];

    public static function register($protoName = 'ts') {
        stream_register_wrapper($protoName, __CLASS__, STREAM_IS_URL);
    }

    public function call() {
       
    }

    public function init($path = '') {
        $this->pathInfo = parse_url($path);
    }

    public static function getInstance() {
        return self::single();
    }

    public function stream_stat() {
        return true;
    }

    public function stream_open($path) {
        return true;
    }

}
