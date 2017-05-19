<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\NoSQL;

use Toknot\Boot\Object;
use Toknot\Exception\BaseException;
use \Redis as PHPRedis;

/**
 * Redis
 *
 */
class Redis extends Object {

    private $redisExtension = false;
    private $redisIns = null;

    public function __construct($redisClass = null) {
        if (extension_loaded('redis')) {
            $this->redisIns = new PHPRedis;
            $this->redisExtension = true;
        } elseif ($redisClass) {
            $this->redisIns = new $redisClass;
        } else {
            throw new BaseException('must give a redis client class or load redis extension');
        }
    }

    public function __call($m, $argv = []) {
        if ($this->redisIns) {
            self::callMethod($this->redisIns,$m, $argv);
        }
        return;
    }

}
