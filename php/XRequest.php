<?php
/**
 * XPHPFramework
 *
 * XRequest class,XCookieObject class,
 * XCookieElementObject class,XSessionObject class
 *
 * PHP version 5.3
 * 
 * @category XRequest
 * @package XPHPFramework
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release 2.2
 */

exists_frame();

/**
 * session array object
 *
 * @package XPHPFramework
 * @author chopins xiao <chopins.xiao@gmail.com>
 */
final class XSessionObject extends XArrayObject {
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

/**
 * one cookie object
 *
 * @package XPHPFramework
 * @author chopins xiao <chopins.xiao@gmail.com>
 */
class XCookieElementObject {//extends XArrayElementObject {
    /**
     * set cookie expire, default value is 0,
     * @var int
     * @access public
     */
    public $expire = 0;

    /**
     * set cookie available for this domain
     * @var string
     * @access public
     */
    public $domain = '';

    /**
     * set cookie accessible only through the HTTP protocol
     * @var bool
     * @access public
     */
    public $httponly = false;

    /**
     * set cookie only be transmitted over a secure HTTPS
     * @var bool
     * @access public
     */
    public $secure = false;

    /**
     * set cookie be available on path
     * @var string
     * @access public
     */
    public $path = '/';
    public $name = '';
    public $value = '';
    private $cookie_uri_str = '';
    public function __construct($value, $name) {
        $this->value = $value;
        $this->name = $name;
    }
    public function get_setcookie() {
        return $this->cookie_uri_str;
    }
    /**
     * set cookie value
     */
    public function set() {
        if(PHP_SAPI == 'cli') {
            $cookie_name = urlencode($this->name);
            $cookie_value = urlencode($this->value);
            $header = "{$cookie_name}={$cookie_value};";
            if($this->expire > 0) {
                $date = $this->get_server_date(gtime() + $this->expire);
                $header .= "Expires={$date};";
            }
            if(!empty($this->domain)) {
                $header .= "Domain={$this->domain};";
            }
            $header .= "Path={$this->path};";
            if($this->secure) $header .="Secure;";
            if($this->httponly) $header .= "HttpOnly;";
            $this->cookie_uri_str = $header;
        } else {
            setcookie($this->name,$this->value,
                $this->expire,$this->path,$this->domain,
                $this->secure,$this->httponly);
        }
    }
}

/**
 * cookie array object
 *
 * @package XPHPFramework
 * @author chopins xiao <chopins.xiao@gmail.com>
 */
class XCookieObject extends XArrayObject {
    public $num = 0;
    private $cookie_uri_str_arr = array();
    protected $elementObjectName ='XCookieElementObject';
    public function __set($sKey, $value) {
        parent::__set($sKey,$value);
        $this->num ++;
    }
    public function get_cookie_array() {
        foreach($this->storage as $co) {
            $this->cookie_uri_str_arr[] = $co->get_setcookie();
        }
        return $this->cookie_uri_str_arr;
    }
}
class XRequestElementValue extends XArrayObject {
    public function isEmail() {
        return is_email($this->value);
    }
    public function isNumber() {
        return is_numeric($this->value);
    }
    public function isWord() {
        return is_word($this->value);
    }
    public function isZhMoblie() {
        return is_moblie($this->value);
    }
    public function isString() {
        return is_string($this->value);
    }
    public function isInt() {
        return is_int($this->value);
    }
    public function noQuotes() {
        return str_replace(array("'",'"'),'',$this->value);
    }
}
class XRequestArray extends XArrayObject {
    protected $elementObjectName = 'XRequestElementValue';
}
/**
 * base request operation class
 *
 * @package XPHPFramework
 * @author chopins xiao <chopins.xiao@gmail.com>
 */
class XRequest {
    /**
     * $_GET data object storage
     *
     * @var object
     * @access public-readonly
     */
    private $G = null;

    /**
     * $_POST data object storage
     *
     * @var object
     * @access public-readonly
     */
    private $P = null;
    
    /**
     * cookie data storage and operation class instance
     *
     * @var object
     * @access public-readonly
     */
    private $C = null;

    /**
     * session data storage and operation class instance
     *
     * @var object
     * @access public-readonly
     */
    private $S = null;

    /**
     * POST JSON data storage class instance
     *
     * @var object
     * @access public-readonly
     */
    private $A = null;

    /**
     * current user visit page method
     * @var string
     * @access public-readonly
     */
    private $M = 'GET';
   
    /**
     * Request Data storage class instance
     *
     * @var object
     * @access public-readonly
     */
    private $_R = null;

    private $AS = false;
    private $ajax_data_key = 'data';
    private $ajax_flag = 'is_ajax';
    private $sess_ini = null;

    /**
     * construct request data structure
     */
    public function __construct($_CFG) {
        $this->M = $_SERVER['REQUEST_METHOD'];
        $this->ajax_data_key = $_CFG->app->ajax_key;
        $this->ajax_flag = $_CFG->app->ajax_flag;
        $this->sess_ini = $_CFG->session;
        $this->check_ajax_status();
        $this->G = new XRequestElementValue($_GET);
        $this->P = new XRequestElementValue($_POST);
        $this->C = new XCookieObject($_COOKIE);
        $this->_R = new XArrayObject($_REQUEST);
    }
    private function check_ajax_status() {
        $this->AS = isset($_REQUEST[$this->ajax_flag]);
        $_ENV['__X_AJAX_REQUEST__'] = $this->AS;
    }
    public function set_ajax_data_key($key) {
        $this->ajax_data_key = $key;
    }
    public function set_ajax_request_flag($flag) {
        $this->ajax_flag = $flag;
    }
    public function __get($pro) {
        switch($pro) {
            case 'S':
            if(!is_object($this->S)) $this->initSession();
            $sid = $this->S->get_session_sid();
            $sname = $this->S->get_session_name();
            if($this->sess_ini->use_php_session == false ||
                    !extension_loaded('session') && PHP_SAPI == 'cli') {
                $this->C->{$sname} = $sid;
                $this->C->{$sname}->httponly = true;
                $this->C->{$sname}->set();
            }
            break;
            case 'A':
            $this->check_ajax_status();
            $this->getAjaxData();
            return $this->A;
            break;
            case '_R':
            throw new XException('XRequest class $_R propertie is private');
            return;
        }
        if(isset($this->$pro)) return $this->$pro;
        if(isset($this->_R->$pro)) return $this->_R->$pro;
    }
    public function __set($pro, $value) {
        $this->_R->$pro = $value;
    }
    public function initSession() {
        $this->S = new XSessionObject($this->sess_ini);
    }
    public function getAjaxData() {
        if($this->AS) {
            $ajax_data = json_decode($_REQUEST[$this->ajax_data_key]);
            if(json_last_error() == JSON_ERROR_NONE) {
                return $this->A = $ajax_data;
            }
            switch(json_last_error()) {
                case JSON_ERROR_DEPTH:
                    $error = 'The maximum stack depth has been exceeded';
                break;
                case JSON_ERROR_STATE_MISMATCH:
                    $error = 'Invalid or malformed JSON';
                break;
                case JSON_ERROR_CTRL_CHAR:
                    $error = 'Control character error, possibly incorrectly encoded';
                break;
                case JSON_ERROR_SYNTAX:
                    $error = 'JSON Syntax error';
                break;
                case JSON_ERROR_UTF8:
                    $error ='Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            }
            throw new XException("request ajax data decode error,$error");
        } else {
            return -1;
        }
    }
    public function __destruct() {
        if(extension_loaded('session') && PHP_SAPI != 'cli') {
            session_write_close();
        }
    }
}



