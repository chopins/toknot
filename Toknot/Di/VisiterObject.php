<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

class VisiterObject {

    public $visitIp = 0;
    public function __construct() {
        $this->visitIp = $this->getVisitIp();
    }
    public function getVisitIp() {
        $ip = 'unknown';
        $array = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach($array as $k) {
            if (!empty($_SERVER[$k]) && strcasecmp($_SERVER[$k], $ip)) {
                preg_match("/[\d\.]{7,15}/", $_SERVER[$k], $ipm);
                return isset($ipm[0]) ? $ipm[0] : $ip;
            }
        }
        return $ip;
    }
}