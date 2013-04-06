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

class CookieObject extends ArrayObject {
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
