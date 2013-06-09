<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use Toknot\Di\FileObject;
use Toknot\Exception\StandardException;

class DataCacheControl {

    /**
     * Data cache file name, without extension name
     *
     * @var string
     */
    public $file = '';

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
    public $cacheType = self::CACHE_FILE;

    /**
     * if set cache type is server cache, must set, the object be supposed set($key, $data, $expire)
     * and get($key) method, and set method should recvie array type parameter for $data, get 
     * method should return a array or boolean
     *
     * @var object
     * @access public
     */
    public $cacheServerInstance = null;
    private $expire = 0;

    const CACHE_FILE = '1001';
    const CACHE_SERVER = '1002';

    /**
     * 
     * @param string $cacheFile 
     * @param integer $modifyTime option, if use expire time, pass it
     */
    public function __construct($cacheFile, $modifyTime = 0) {
        $this->file = $cacheFile;
        $this->dataModifyTime = $modifyTime;
    }

    /**
     * Get current cache data save seconds
     * 
     * @return int
     */
    public function cacheTime() {
        if (empty($this->file)) {
            return 0;
        }

        if (file_exists($this->file)) {
            $file = self::$appRoot . $this->file . '.php';
            return filemtime($file);
        } else {
            return 0;
        }
    }

    /**
     * Use expire time control data modify time
     * 
     * @param int $expire
     */
    public function useExpire($expire) {
        if ($this->cacheType == self::CACHE_SERVER) {
            if (!is_object($this->cacheServerInstance)) {
                throw new StandardException('must set cache server instance');
            }
            $this->expire = $expire;
            return;
        }
        $cacheTime = $this->cacheTime();
        $nowTime = time();

        //if cache time be expired
        if ($nowTime - $cacheTime > $expire) {
            $this->dataModifyTime = $nowTime;
        } else {
            $this->dataModifyTime = $cacheTime - 1;
        }
    }

    /**
     * store data
     * 
     * @param array $data
     * @return boolean  if data not change return false
     */
    public function save(array $data) {
        if ($this->cacheType == self::CACHE_SERVER) {
            $key = md5($this->appRoot . "{$this->file}.php");
            $this->cacheServerInstance->set($key, $data, time() + $this->expire);
            return true;
        }
        if(empty($this->file)) {
            return false;
        }
        if ($this->cacheTime() >= $this->dataModifyTime) {
            return false;
        }
        $dataString = '<?php return ' . var_export($data, true) . ';';

        FileObject::saveContent(self::$appRoot . "{$this->file}.php", $dataString);
        return true;
    }

    /**
     * Get cache data
     * 
     * @return boolean|array  if cache data is old return false
     */
    public function get() {
        if ($this->cacheType == self::CACHE_SERVER) {
            $key = md5(self::$appRoot . "{$this->file}.php");
            return $this->cacheServerInstance->get($key);
        }

        if ($this->cacheTime() <= $this->dataModifyTime) {
            return false;
        }
        return include_once self::$appRoot . "{$this->file}.php";
    }

}

?>
