<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\User;

use Toknot\Di\ArrayObject;
use Toknot\Di\DataCacheControl;
use Toknot\Config\ConfigLoader;
use Toknot\Di\StringObject;

class Session extends ArrayObject {

    protected $havePHPSession = true;
    private $cacheInstance = null;
    private $fileStorePath = '';
            private$fileStore = true;
    private static $sessionStatus = false;

    /**
     * if use file store the properties can not be set, otherwise must set one opreate 
     * of class of instance be called by {@see Toknot\Di\DataCacheControl}
     *
     * @var mixed
     * <code>
     * Session::$storeHandle = new Memcache;
     * $session = Session::singleton();
     * $session->start();
     * </code>
     */
    public static $storeHandle = null;
    private static $sessionName = 'TKSID';
    private $sessionId = '';
    private static $maxLifeTime = 3600;
    private static $sessionInstance = null;

    public static function singleton() {
        self::$sessionInstance = parent::__singleton();
        return self::$sessionInstance;
    }

    /**
     * construct a new Session class instance and cover old
     * 
     * <code>
     * 
     * //if not use file store session data
     * Session::$storeHandle = new Memcache;
     * 
     * $session = Session::singleton();
     * $session->start();
     * 
     * //set session value
     * $session('username', 'username');
     * 
     * //above same below if enable php session extension:
     * $_SESSION['username'] = 'username';
     * 
     * //or
     * $session['usrname'] = 'username';
     * </code>
     * 
     * @access protected
     */
    public function __construct() {
        $class = __CLASS__;
        if (is_object(self::$sessionInstance) && self::$sessionInstance instanceof $class) {
            self::$sessionInstance = $this;
        }
        if (extension_loaded('session')) {
            session_set_save_handler(array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc'));
            $this->havePHPSession = true;
        } else {
            $this->havePHPSession = false;
        }
        $this->loadConfigure();
    }

    public function name($name = null) {
        if ($name == null) {
            return self::$sessionName;
        } else {
            self::$sessionName = $name;
        }
    }

    private function loadConfigure() {
        $CFG = ConfigLoader::CFG();
        $this->fileStore = $CFG->Session->fileStoreSession;
        self::$sessionName = $CFG->Session->sessionName;
        $this->fileStorePath = $CFG->Session->fileStorePath;
        self::$maxLifeTime = $CFG->Session->maxLifeTime;
    }

    public function writeClose() {
        $this->write($this->sessionId, serialize($this->interatorArray));
        $this->close();
    }

    public function start() {
        if (self::$sessionStatus) {
            //trigger_error('Session started', E_USER_WARNING);
            return;
        }
        if (isset($_COOKIE[self::$sessionName])) {
            $this->sessionId = $_COOKIE[self::$sessionName];
        } else {
            $this->regenerate_id();
        }
        if ($this->havePHPSession) {
            session_name(self::$sessionName);
            session_start();
            $this->sessionId = session_id();
            $this->interatorArray = $_SESSION;
        } else {
            self::$sessionStatus = true;
            $this->open(self::$storeHandle, self::$sessionName);
            $this->read($this->sessionId);
        }
    }

    public function regenerate_id() {
        debug_print_backtrace();
        if ($this->sessionId) {
            $this->destroy($this->sessionId);
        }

        $this->sessionId = StringObject::rand(10);
        setcookie(self::$sessionName, $this->sessionId);
    }

    private function setValue($name, $value) {
        if ($this->havePHPSession) {
            $_SESSION[$name] = $value;
        }
        $this->interatorArray[$name] = $value;
    }

    private function getValue($name) {
        if ($this->havePHPSession) {
            return $_SESSION[$name];
        }
        return $this->interatorArray[$name];
    }

    public function setPropertie($name, $value) {
        $this->setValue($name, $value);
    }

    public function __invoke($name) {
        $num = func_num_args();
        if ($num > 2) {
            $this->setValue($name, func_get_arg(1));
        } else {
            $this->getValue($name);
        }
    }

    public function open($dsn, $sessionName) {
        $this->path = $dsn . '.' . $sessionName;
        if ($this->fileStore) {
            if (!is_dir($this->fileStorePath)) {
                DataCacheControl::createCachePath($this->fileStorePath);
            }
            self::$storeHandle = $this->fileStorePath;
            $type = DataCacheControl::CACHE_FILE;
        } else {
            $type = DataCacheControl::CACHE_SERVER;
        }
        $this->cacheInstance = new DataCacheControl(self::$storeHandle, 0, $type);
        $this->cacheInstance->useExpire(self::$maxLifeTime);
        return true;
    }

    public function close() {
        return true;
    }

    public function read($sessionId) {
        if ($this->fileStore) {
            $sessionId = DIRECTORY_SEPARATOR . $sessionId;
            if (!$this->havePHPSession && !$this->cacheInstance->exists($sessionId)) {
                $this->regenerate_id();
            }
        }

        $data = $this->cacheInstance->get($sessionId);
        if (!$data) {
            $data = '';
        }
        if (!$this->havePHPSession) {
            $this->interatorArray = unserialize($data);
        }
        return $data;
    }

    public function write($sessionId, $data) {
        if ($this->fileStore)
            $sessionId = DIRECTORY_SEPARATOR . $sessionId;
        return $this->cacheInstance->save($data, $sessionId);
    }

    public function destroy($sessionId) {
        $this->cacheInstance->del($sessionId);
        return true;
    }

    public function gc($lifetime) {
        if ($this->fileStore) {
            foreach (glob($this->fileStorePath . DIRECTORY_SEPARATOR . '*.php') as $file) {
                if (file_exists($file) && filemtime($file) + $lifetime < time()) {
                    unlink($file);
                }
            }
        }
        return true;
    }

    public function unsetSession() {
        $this->interatorArray = array();
    }

    public function __destruct() {
        if (!$this->havePHPSession) {
            $this->writeClose();
            $rand = mt_rand(1, 10) % 2;
            if (date('i') == '00' || $rand == 0) {
                $this->gc(self::$maxLifeTime);
            }
        }
    }

}
