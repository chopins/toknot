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

    private $cacheObj = null;
    private $oop = true;
    private $compress = null;
    private $memcahed = true;
    private $extClassName = '';
    private $version = 0;

    public function __construct() {
        $this->version = phpversion('memcached');
        if ($this->version) {
            $this->extClassName = 'Memcached';
            $this->cacheObj = new \Memcached;
        } elseif (($this->version = phpversion('memcache'))) {
            $this->memcahed = false;
            $this->extClassName = 'Memcache';
            if (class_exists('Memcache', false)) {
                $this->cacheObj = new \Memcache;
            } else {
                $this->oop = false;
            }
        } else {
            $this->memcahed = false;
            $this->oop = false;
            $this->cacheObj = false;
            throw new BaseException('memcache/memcached extension unload');
        }
    }

    public function getVersion() {
        return $this->version;
    }

    public function getClass() {
        return $this->extClassName;
    }

    public function getObj() {
        return $this->memcahed;
    }

    public function close() {
        if ($this->memcahed) {
            return $this->cacheObj->quit();
        }
        if ($this->oop) {
            return $this->cacheObj->close();
        }
        return memcache_close();
    }

    public function setCompressed($compress) {
        if ($compress && !$this->memcahed) {
            $this->compress = MEMCACHE_COMPRESSED;
        } elseif ($this->memcahed) {
            $this->cacheObj->setOption(Memcached::OPT_COMPRESSION);
        }
    }

    public function addServer($host, $port = 11211) {
        if ($this->oop) {
            return $this->cacheObj->addServer($host, $port);
        } else {
            if (!$this->cacheObj) {
                return $this->connect($host, $port);
            }
            return memcache_add_server($this->cacheObj, $host, $port);
        }
    }

    public function connect($host, $port) {
        if ($this->memcahed) {
            return;
        }
        if ($this->oop) {
            $this->cacheObj->connect($host, $port);
        } else {
            $this->cacheObj = memcache_connect($host, $port);
        }
    }

    public function pconnect($host, $port) {
        if ($this->memcahed) {
            return;
        }
        if ($this->oop) {
            $this->cacheObj->pconnect($host, $port);
        } else {
            $this->cacheObj = memcache_pconnect($host, $port);
        }
    }

    private function changeFactory($m, $key, $value, $expire) {
        if ($this->memcahed) {
            return $this->cacheObj->$m($key, $value, $expire);
        }
        if ($this->oop) {
            return $this->cacheObj->$m($key, $value, $this->compress, $expire);
        } else {
            $func = "memcache_$m";
            return $func($key, $value, $this->compress, $expire);
        }
    }

    private function changeFactory2($m, $key, $num) {
        if ($this->oop) {
            return $this->cacheObj->$m($key, $num);
        } else {
            $func = "memcache_$m";
            return $func($this->cacheObj, $key, $num);
        }
    }

    public function get($key, $cacheCb = null, &$casToken = 0) {
        if ($this->memcahed) {
            return $this->cacheObj->get($key, $cacheCb, $casToken);
        }
        if ($this->oop) {
            return $this->cacheObj->get($key);
        } else {
            return memcache_get($this->cacheObj, $key);
        }
    }

    public function cas($casToken, $key, $value, $expire = 0) {
        if ($this->memcahed) {
            return $this->cacheObj->cas($casToken, $key, $value, $expire);
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
            return $this->cacheObj->flush();
        } else {
            return memcache_flush($this->cacheObj);
        }
    }

    public function __call($m, $argv = []) {
        if ($this->oop) {
            return self::callMethod($this->cacheObj, $m, $argv);
        } else {
            array_unshift($argv, $this->cacheObj);
            return self::callFunc("memcache_$m", $argv);
        }
    }

}
