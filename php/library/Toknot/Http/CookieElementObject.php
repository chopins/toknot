<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Http;

class CookieElementObject {//extends XArrayElementObject {
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
