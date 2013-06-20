<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use Toknot\Di\FileObject;

class DataCacheControl {

    /**
     * Data cache file name, without extension name, if use server , must set one 
     * server connect handle instance,the object be supposed set($key, $data, $expire)
     * and get($key) method, and set method should recvie array type parameter for $data, get 
     * method should return a array or boolean
     *
     * @var string
     */
    private $cacheHandle = '';

    /**
     * Current data modify seconds
     * @var integer
     * @access public
     */
    public $dataModifyTime = 0;

    /**
     * if cache type be seted CACHE_FILE, must set and is application root path
     *
     * @var string
     * @access public
     * @static
     */
    public static $appRoot = '';

    /**
     * Set use cache type
     *
     * @var integer
     */
    private $cacheType = self::CACHE_FILE;
    private $expire = 0;

    const CACHE_FILE = '1001';
    const CACHE_SERVER = '1002';

    /**
     * 
     * @param string $cacheFile 
     * @param integer $modifyTime option, if use expire time, pass it
     */
    public function __construct($cacheHandle, $modifyTime = 0, $cacheType = self::CACHE_FILE) {
        $this->cacheHandle = $cacheHandle;
        $this->dataModifyTime = $modifyTime;
        $this->cacheType = $cacheType;
    }

    /**
     * Get current cache data save seconds
     * 
     * @return int
     */
    public function cacheTime($key = '') {
        if ($this->cacheType == self::CACHE_SERVER || empty($this->cacheHandle)) {
            return 0;
        }
        $file = FileObject::getRealPath(self::$appRoot, "{$this->cacheHandle}{$key}.php");

        if (file_exists($file)) {
            return filemtime($file);
        } else {
            return 0;
        }
    }

    public static function createCachePath($path) {
        $path = FileObject::getRealPath(self::$appRoot, $path);
        if (file_exists($path)) {
            return;
        }
        return mkdir($path, 0777, true);
    }

    /**
     * Use expire time control data modify time
     * 
     * @param int $expire
     */
    public function useExpire($expire) {
        $this->expire = $expire;
    }

    /**
     * store data
     * 
     * @param mixed $data
     * @param string $key If not use file store, must set
     * @return boolean  if data not change return false
     */
    public function save($data, $key = '') {
        if ($this->cacheType == self::CACHE_SERVER) {
            $key = md5(self::$appRoot . "{$key}.php");
            $this->cacheHandle->set($key, $data, time() + $this->expire);
            return true;
        }

        if (empty($this->cacheHandle)) {
            return false;
        }

        if ($this->expire == 0 && $this->cacheTime($key) <= $this->dataModifyTime) {
            return false;
        }
        $dataString = '<?php return ' . var_export($data, true) . ';';
        $file = FileObject::getRealPath(self::$appRoot, "{$this->cacheHandle}{$key}.php");
        FileObject::saveContent($file, $dataString, LOCK_EX);
        return true;
    }

    /**
     * Get cache data
     * 
     * @param string $key data key
     * @return boolean|array  if cache data is old return false
     */
    public function get($key = '') {
        if ($this->cacheType == self::CACHE_SERVER) {
            $key = md5(self::$appRoot . "{$key}.php");
            return $this->cacheHandle->get($key);
        }
        if ($this->expire > 0 && ($this->cacheTime($key) + $this->expire) < time()) {
            return false;
        } elseif ($this->expire == 0 && $this->cacheTime($key) <= $this->dataModifyTime) {
            return false;
        }
  
        $file = FileObject::getRealPath(self::$appRoot, "{$this->cacheHandle}{$key}.php");
        if (file_exists($file)) {
            return include_once $file;
        } else {
            return false;
        }
    }

    /**
     * Delete cache data
     * 
     * @param sting $key
     * @return boolean
     */
    public function del($key = '') {
        if ($this->cacheType == self::CACHE_SERVER) {
            return $this->cacheHandle->del($key);
        }
        $file = FileObject::getRealPath(self::$appRoot, "{$this->cacheHandle}{$key}.php");
        if (file_exists($file)) {
            return unlink($file);
        }
    }

    public function exists($key = '') {
        if ($this->cacheType == self::CACHE_SERVER) {
            return $this->cacheHandle->exist($key);
        }
        $file = FileObject::getRealPath(self::$appRoot, "{$this->cacheHandle}{$key}.php");
        if ($this->expire > 0 && ($this->cacheTime($key) + $this->expire) < time()) {
            return false;
        } elseif ($this->expire == 0 && $this->cacheTime($key) <= $this->dataModifyTime) {
            return false;
        }
        return file_exists($file);
    }

}

?>
