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

/**
 * memcache
 *
 */
class Memcache extends Object {

    private $extClass = null;
    private $oop = true;
    private $compress = null;
    private $memcahed = true;

    public function __construct() {
        if (extension_loaded('memcached')) {
            $this->extClass = new \Memcached;
        } elseif (extension_loaded('memcache')) {
            $this->memcahed = false;
            if (class_exists('Memcache', false)) {
                $this->extClass = new \Memcache;
            } else {
                $this->oop = false;
            }
        } else {
            $this->memcahed = false;
            $this->oop = false;
            $this->extClass = false;
            throw new BaseException('memcache/memcached extension unload');
        }
    }

    public function close() {
        if ($this->memcahed) {
            return $this->extClass->quit();
        }
        if ($this->oop) {
            return $this->extClass->close();
        }
        return memcache_close();
    }

    public function setCompressed($compress) {
        if ($compress && !$this->memcahed) {
            $this->compress = MEMCACHE_COMPRESSED;
        } elseif ($this->memcahed) {
            $this->extClass->setOption(Memcached::OPT_COMPRESSION);
        }
    }

    public function addServer($host, $port = 11211) {
        if ($this->oop) {
            return $this->extClass->addServer($host, $port);
        } else {
            if (!$this->extClass) {
                return $this->connect($host, $port);
            }
            return memcache_add_server($this->extClass, $host, $port);
        }
    }

    public function connect($host, $port) {
        if ($this->memcahed) {
            return;
        }
        if ($this->oop) {
            $this->extClass->connect($host, $port);
        } else {
            $this->extClass = memcache_connect($host, $port);
        }
    }

    public function pconnect($host, $port) {
        if ($this->memcahed) {
            return;
        }
        if ($this->oop) {
            $this->extClass->pconnect($host, $port);
        } else {
            $this->extClass = memcache_pconnect($host, $port);
        }
    }

    private function changeFactory($m, $key, $value, $expire) {
        if ($this->memcahed) {
            return $this->extClass->$m($key, $value, $expire);
        }
        if ($this->oop) {
            return $this->extClass->$m($key, $value, $this->compress, $expire);
        } else {
            $func = "memcache_$m";
            return $func($key, $value, $this->compress, $expire);
        }
    }

    private function changeFactory2($m, $key, $num) {
        if ($this->oop) {
            return $this->extClass->$m($key, $num);
        } else {
            $func = "memcache_$m";
            return $func($this->extClass, $key, $num);
        }
    }

    public function get($key, $cacheCb = null, &$casToken = 0) {
        if ($this->memcahed) {
            return $this->extClass->get($key, $cacheCb, $casToken);
        }
        if ($this->oop) {
            return $this->extClass->get($key);
        } else {
            return memcache_get($this->extClass, $key);
        }
    }

    public function cas($casToken, $key, $value, $expire = 0) {
        if ($this->memcahed) {
            return $this->extClass->cas($casToken, $key, $value, $expire);
        }
    }

    public function set($key, $value, $expire = 0) {
        return $this->changeFactory('set', $key, $value, $expire);
    }

    public function add($key, $value, $expire = 0) {
        return $this->changeFactory('add', $key, $value, $expire);
    }

    public function decrement($key, $offset = 1) {
        return $this->changeFactory2('decrement', $key, $offset);
    }

    public function increment($key, $offset = 1) {
        return $this->changeFactory2('increment', $key, $offset);
    }

    public function replace($key, $value, $expire = 0) {
        return $this->changeFactory('replace', $key, $value, $expire);
    }

    public function del($key, $timeout = 0) {
        return $this->changeFactory2('delete', $key, $timeout);
    }

    public function flush() {
        if ($this->oop) {
            return $this->extClass->flush();
        } else {
            return memcache_flush($this->extClass);
        }
    }

    public function __call($m, $argv = null) {
        if ($this->oop) {
            return self::callMethod(count($argv), $m, $argv, $this->extClass);
        } else {
            array_unshift($argv, $this->extClass);
            return self::callFunc("memcache_$m", $argv);
        }
    }

}
