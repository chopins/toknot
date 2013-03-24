<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Http;

class Request {
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



