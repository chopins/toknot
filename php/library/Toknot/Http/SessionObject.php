<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Http;

use Toknot\Di\ArrayObject;

final class SessionObject extends ArrayObject {
    private $sid = null;
    private $use_php_session = true;
    private $sess_name = 'xsess_';
    private $save_path  = '';
    private $sess_file = '';
    private $path_depth = 2;
    private $txtdb_ins = null;
    public $cookie_be_disable = false;
    public $use_url_sid = false;
    public function __construct($cfg) {
        $this->sess_name = $cfg->session_name;
        $this->save_path = __X_APP_DATA_DIR__."/{$cfg->save_path}";
        if($cfg->use_php_session == false) {
            $this->use_php_session = false;
        } else {
            $this->use_php_session = extension_loaded('session') && PHP_SAPI != 'cli';
        }

        $this->startSession();
        $this->initArray();
    }
    public function get_session_name() {
        return $this->sess_name;
    }
    public function get_session_sid() {
        return $this->sid;
    }
    public function get_session_last_modified($sid = null) {
        if($sid == null) {
            $sid = $this->sid;
        }
        if($this->use_php_session && ini_get('session.save_handler') == 'files') {
            return filemtime("{$this->save_path}/sess_{$sid}");
        }
        return NULL;
    }
    public function check_cookie_status() {
        if(empty($_COOKIE[$this->sess_name])) {
            if($this->cookie_be_disable) {
                ini_set('session.use_trans_sid',true);
            }
            $this->cookie_be_disable = true;
            if(isset($_GET[$this->sess_name])) {
                $this->cookie_be_disable = false;
                $this->use_url_sid = true;
                session_id($_GET[$this->sess_name]);
            }
        }
    }
    private function initArray() {
        if($this->use_php_session) {
            parent::__construct($_SESSION);
        } else {
            parent::__construct($this->storage);
        }
    } 
    private function startSession() {
        if(!file_exists($this->save_path)) {
            mkdir($this->save_path);
        }
        if($this->use_php_session) {
            $this->sid = session_id();
            if(empty($this->sid)) {
                session_name($this->sess_name);
                if(ini_get('session.save_handler') == 'files') {
                    session_save_path($this->save_path);
                }
                if(ini_get('session.cookie_httponly') == false) {
                    ini_set('session.cookie_httponly',true);
                }
                session_start();
                $this->sid = session_id();
            }
        } else {
            if(isset($_COOKIE[$this->sess_name])) {
                $this->sid = $_COOKIE[$this->sess_name];
            } else {
                $this->sid = md5($_SERVER['REMOTE_ADDR'].$_SERVER['REMOTE_PORT'].
                            microtime().mt_rand(1,1000).$_SERVER['REQUEST_URI']);
            }
            $this->sess_file = "{$this->save_path}/{$this->sess_name}_{$this->sid}";
            if(file_exists($this->sess_file)) {
                $sess_data = file_get_contents($this->sess_file);
                $this->storage = unserialize($sess_data);
            } else {
                file_put_contents($this->sess_file,'');
                $this->storage = array();
            }
        }
    }
    /**
     * set array object element value at a specified any type index
     */
    public function offsetSet($sKey, $value) {
        parent::offsetSet($sKey,$value);
        if($this->use_php_session) {
            $_SESSION[$sKey] = $value;
        }
    }
    public function offsetUnset($sKey) {
        parent::offsetUnset($sKey);
        if($this->use_php_session) {
            unset($_SESSION[$sKey]);
        }
    }
    public function __destruct() {
        if(!$this->use_php_session) {
            $data = serialize($this->storage);
            file_put_contents($this->sess_file,$data, LOCK_EX);
        } else {
            session_write_close();
        }
    }
}
