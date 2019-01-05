<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Auth;

use Toknot\Lib\IO\Request;
use Toknot\Boot\Kernel;

class RequestCsrf {

    protected $value = '';
    protected $state = [];
    protected $feildName = '';
    protected $handler = null;
    public $leftTime = 10;
    protected static $serverEntropy = '';
    public static $useAlgo = '';
    public static $hmac = false;
    public $refererWhiteList = [];
    protected $validReferer = false;

    public function __construct($handler = null, $feildName = '') {
        $this->feildName = $feildName;
        $this->handler = $handler;
    }

    public function hash($data, $key, $raw = false) {
        return Kernel::hash($data, $key, $raw, self::$useAlgo, self::$hmac);
    }

    public function setRefererWhite($referer) {
        $this->refererWhiteList[] = $referer;
    }

    public function forceValidReferer() {
        $this->validReferer = true;
    }

    public function getLastAlgo() {
        return self::$useAlgo;
    }

    public function getLastHmac() {
        return self::$hmac;
    }

    public function enableCsrf($method = null) {
        if ($method === null) {
            foreach (Request::METHOD_LIST as $m) {
                $this->state[$m] = true;
            }
        } else {
            if (is_numeric($method) && !empty(Request::METHOD_LIST[$method])) {
                $m = Request::METHOD_LIST[$method];
            } elseif (in_array($method, Request::METHOD_LIST)) {
                $m = $method;
            } else {
                throw new Exception('unknow http request method');
            }
            $this->state[$m] = true;
        }
    }

    public function checkCsrf($id) {
        $method = Request::method();
        if (isset($this->state[$method])) {
            $value = Request::any($method)->value($this->feildName);
            return $this->handler->get($id) === $value;
        }
        return true;
    }

    public function getCsrfHash($id) {
        $value = $this->hash(Request::requestHash());
        $this->handler->store($id, $value);
        return $value;
    }

    protected function checkWhiteReferer($referer) {
        if (!$this->validReferer && !$referer) {
            return true;
        }
        foreach ($this->refererWhiteList as $white) {
            if (strcasecmp($referer, $white) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 
     * @param string $key
     * @return type
     */
    public function selfVerifyHash($key = Kernel::NOP) {
        $t = pack('V', time());
        $uri = $this->hash(Request::url(), $key . $t, true);
        $userMark = $t . $this->hashReqeustInfo($t, $key, $uri);
        $hashValue = $uri . $t . password_hash($userMark, PASSWORD_DEFAULT);
        return urlencode(base64_encode($hashValue));
    }

    public function verifyReferer($refererHash, $key, $t) {
        $referer = Request::referer();
        if ($this->checkWhiteReferer($referer)) {
            return true;
        }
        $refererState = ($this->hash($referer . $key, $t, true) === $refererHash);
        if (($referer || $this->validReferer) && !$refererState) {
            return false;
        }
        return true;
    }

    public function execSelfVerifyHash($hash, $key = Kernel::NOP) {
        $referer = Request::referer() ? Request::referer() : Kernel::NOP;
        if ($this->validReferer && !$referer) {
            return false;
        }
        $hash = base64_decode(urldecode($hash));
        $hashLen = strlen($this->hash(1, 1, true));
        $t = substr($hash, $hashLen, 4);
        $uri = substr($hash, 0, $hashLen);
        if (!$this->verifyReferer($uri, $key, $t)) {
            return false;
        }

        list(, $hashTime) = unpack('V', $t);
        if (!is_numeric($hashTime)) {
            return false;
        }
        $checkHash = substr($hash, $hashLen + 4);
        $offsetTime = ceil((time() - $hashTime) / 60);
        if ($offsetTime > $this->leftTime) {
            return false;
        }
        $userMask = $t . $this->hashReqeustInfo($t, $key, $uri);
        if (password_verify($userMask, $checkHash)) {
            return true;
        }

        return false;
    }

    protected function hashReqeustInfo($t, $key, $uri) {
        $a = $t . $uri . Request::userBrowserId() . Request::ip() . Request::agent() . self::serverEntropy();
        return $this->hash($a, $key, true);
    }

    /**
     * multiple server must has same platfrom software that is php, mysqlnd, webserver ,os release
     * 
     * @param bool $single
     * @return string
     */
    public static function serverEntropy($single = true) {
        return Kernel::serverEntropy($single);
    }

}
