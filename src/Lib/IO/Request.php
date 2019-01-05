<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\IO;

use Toknot\Lib\IO\Route;
use Toknot\Boot\Kernel;
use Toknot\Boot\ArrayObject;
use Toknot\Boot\TKObject;
use Iterator;
use Countable;

class Request extends TKObject implements Iterator, Countable {

    public static $get = null;
    public static $post = null;
    public static $cookie = null;
    private static $requestMethod = '';
    protected $type = INPUT_GET;
    protected $iteratorArray = [];
    protected $iteratorPostion = 0;
    protected $count = 0;

    const METHOD_LIST = ['GET', 'POST', 'PUT', 'HEAD', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH', 'CLI', 'COOKIE'];
    const GET = 0;
    const POST = 1;
    const PUT = 2;
    const HEAD = 3;
    const DELETE = 4;
    const CONNECT = 5;
    const OPTIONS = 6;
    const TRACE = 7;
    const PATCH = 8;
    const CLI = 9;
    const COOKIE = 10;
    const FLITER_REQUIRE = 1;
    const FLITER_OPTION = 2;
    const FLITER_UNEMPTY = 4;
    const FLITER_EMAIL = 8;
    const FILTER_URL = 16;
    const FILTER_NUMBER = 32;
    const FILTER_WORD = 64;
    const FILTER_MAP = ['has' => self::FLITER_REQUIRE, 'option' => self::FLITER_OPTION,
        'value' => self::FLITER_UNEMPTY, 'isemail' => self::FLITER_EMAIL, 'isurl' => self::FILTER_URL,
        'word' => self::FILTER_WORD, 'number' => self::FILTER_NUMBER,
    ];
    const XML = 'xml';
    const JSON = 'json';
    const XHR = 'xmlhttprequest';

    protected function __construct() {
        $this->iteratorArray = filter_input_array($this->type);
        if (!$this->iteratorArray) {
            $this->iteratorArray = [];
        }
        $this->count = count($this->iteratorArray);
    }

    public function __call($name, $args = []) {
        return self::invokeStatic($name, $args);
    }

    public static function requestHash() {
        list($iparea) = explode('.', strrev(self::ip()), 2);
        $user = self::agent() . self::userBrowserId() . $iparea . self::mircotime();
        $rand = uniqid(sha1($user), true) . mt_rand(1000000, 10000000);
        return hash('sha256', $user . $rand);
    }

    public static function uri() {
        if (PHP_SAPI === Kernel::CLI) {
            return self::argv();
        }
        return self::server('REQUEST_URI');
    }

    public static function url() {
        $uri = ltrim(self::uri(), Kernel::URL_SEP);
        $host = self::host();
        $schema = self::schema();
        return "$schema://$host/$uri";
    }

    public static function host() {
        return self::server('HTTP_HOST');
    }

    public static function getDocumentRoot() {
        return self::server('DOCUMENT_ROOT');
    }

    public static function domian() {
        return self::server('SERVER_NAME');
    }

    public static function method() {
        if (self::$requestMethod) {
            return self::$requestMethod;
        }
        self::$requestMethod = strtoupper(self::server('REQUEST_METHOD'));
        if (PHP_SAPI === Kernel::CLI) {
            self::$requestMethod = 'CLI';
        }
        return self::$requestMethod;
    }

    public static function protocol() {
        return self::server('SERVER_PROTOCOL');
    }

    public static function schema() {
        if (self::server('HTTPS')) {
            return 'https';
        } elseif (stripos(self::protocol(), 'http') !== false) {
            return 'http';
        }
        return self::protocol();
    }

    public function port() {
        return self::server('SERVER_PORT');
    }

    public static function webserver() {
        return self::server('SERVER_SOFTWARE');
    }

    public static function time() {
        return self::server('REQUEST_TIME');
    }

    public static function mircotime() {
        return self::server('REQUEST_TIME_FLOAT');
    }

    protected static function checkWebServer($name) {
        return stripos(self::webserver(), $name) !== false;
    }

    public static function isApache() {
        return self::checkWebServer('apache');
    }

    public static function isNginx() {
        return self::checkWebServer('nginx');
    }

    public function isLighttpd() {
        return self::checkWebServer('lighttpd');
    }

    public function isIIS() {
        return self::checkWebServer('iis');
    }

    public static function argv($idx = -1) {
        if (PHP_SAPI === 'cli') {
            return $idx < 0 ? $GLOBALS['argv'] : (count($GLOBALS['argv']) > $idx ? $GLOBALS['argv'][$idx] : '');
        }
        return Route::instance()->getParameter($idx);
    }

    /**
     * 
     * @return \Toknot\Lib\IO\Request
     */
    public static function input($type = '') {
        if ($type) {
            return self::any($type);
        }
        $method = self::method();
        return self::any($method);
    }

    /**
     * 
     * @return \Toknot\Lib\IO\Request
     */
    public static function get() {
        if (self::$get === null) {
            self::$get = new Get();
        }
        return self::$get;
    }

    /**
     * 
     * @return \Toknot\Lib\IO\Request
     */
    public static function post() {
        if (self::$post === null) {
            self::$post = new Post;
        }
        return self::$post;
    }

    /**
     * 
     * @return \Toknot\Lib\Io\Request
     */
    public static function cookie() {
        if (self::$cookie === null) {
            self::$cookie = new Cookie;
        }
        return self::$cookie;
    }

    public static function cli() {
        return new ArrayObject(self::argv());
    }

    /**
     * 
     * @param string $method
     * @return \Toknot\Lib\IO\Request
     */
    public static function any($method) {
        if ($method == self::METHOD_LIST[0]) {
            return self::get();
        } elseif ($method == self::METHOD_LIST[1]) {
            return self::post();
        } elseif ($method === 'CLI') {
            return self::cli();
        } elseif ($method === 'COOKIE') {
            return self::cookie();
        }
        $ins = new static;
        $ins->type = constant('INPUT_' . strtoupper($method));
        return $ins;
    }

    public static function server($key) {
        if ($key === '_') {
            return Kernel::globals('_SERVER')['_'];
        } elseif ($key == 'argv') {
            return self::argv();
        } elseif ($key == 'argc') {
            return Kernel::globals('_SERVER')['argc'];
        }
        return filter_input(INPUT_SERVER, $key);
    }

    public static function isxhr() {
        if (self::post()->_xhr == 1 || self::get()->_xhr == 1) {
            return true;
        } elseif (strtolower(self::server('HTTP_X_REQUESTED_WITH')) === self::XHR) {
            return true;
        }
        return false;
    }

    public static function referer() {
        return self::server('HTTP_REFERER');
    }

    public static function agent() {
        return self::server('HTTP_USER_AGENT');
    }

    public static function ip() {
        return self::server('REMOTE_ADDR');
    }

    public static function accept() {
        return self::server('HTTP_ACCEPT');
    }

    public static function wantXML() {
        if (self::any(self::method())->_want_xml == 1) {
            return true;
        } elseif (strpos(self::accept(), 'text/xml') !== false) {
            return true;
        } elseif (strtolower(self::server('HTTP_X_WANT_ACCEPT')) == self::XML) {
            return true;
        }
        return false;
    }

    public static function wantJSON() {
        if (self::any(self::method())->_want_json == 1) {
            return true;
        } elseif (strpos(self::accept(), 'json') !== false) {
            return true;
        } elseif (strtolower(self::server('HTTP_X_WANT_ACCEPT')) == self::JSON) {
            return true;
        }
        return false;
    }

    public static function browserIdHeaderFeild() {
        return 'X' . Kernel::BROWSER_ID;
    }

    public static function userBrowserId() {
        if (($id = self::server('HTTP_' . self::browserIdHeaderFeild()))) {
            return $id;
        } elseif (($id = self::cookie()->value(Kernel::BROWSER_ID))) {
            return $id;
        }
        return null;
    }

    public function checkParams(array $option = []) {
        $result = [];
        foreach ($option as $field => $opt) {
            $result[$field] = $this->check($opt, $field);
        }
        return $result;
    }

    public function number($key) {
        $value = $this->value($key);
        if (is_numeric($value)) {
            return $value;
        }
        return false;
    }

    public function word($key) {
        $value = $this->value($key);
        if (preg_match('/^[a-z0-9]+$/i', $value)) {
            return $value;
        }
        return '';
    }

    public function isValid($key, callable $checkVaild) {
        $value = $this->value($key);
        if ($checkVaild($value)) {
            return $value;
        }
        return '';
    }

    public function isemail($key) {
        return $this->value($key, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
    }

    public function isurl($key) {
        return $this->value($key, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED);
    }

    public function urlencode($key) {
        return $this->value($key, FILTER_SANITIZE_ENCODED);
    }

    public function magic($key) {
        return $this->value($key, FILTER_SANITIZE_MAGIC_QUOTES);
    }

    public function htmlencode($key) {
        return $this->value($key, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    public function value($key, $filter = FILTER_DEFAULT) {
        return filter_input($this->type, $key, $filter);
    }

    public function index($index = 0) {
        if ($index > $this->count - 1) {
            Kernel::runtimeException("index out of bounds ($index) ", E_USER_WARNING);
        }
        $i = 0;
        foreach ($this->iteratorArray as $k => $v) {
            if ($i == $index) {
                return $k;
            }
            $i++;
        }
    }

    public function has($key) {
        return filter_has_var($this->type, $key);
    }

    public function option($key) {
        return true;
    }

    public function __get($name) {
        if (!property_exists($this, $name)) {
            return $this->value($name);
        }
        return null;
    }

    protected function check($opt, $field) {
        foreach (self::FILTER_MAP as $k => $mask) {
            if (!$mask & $opt) {
                continue;
            }
            $res = $this->$k($field);
            if (!$res) {
                return false;
            }
        }
        return true;
    }

    public function toArray() {
        return filter_input_array($this->type);
    }

    public function current() {
        return current($this->iteratorArray);
    }

    public function key() {
        return key($this->iteratorArray);
    }

    public function next() {
        $this->iteratorPostion++;
        next($this->iteratorArray);
    }

    public function rewind() {
        reset($this->iteratorArray);
        $this->iteratorPostion = 0;
    }

    public function valid() {
        return $this->iteratorPostion < $this->count;
    }

    public function count() {
        return $this->count;
    }

}
