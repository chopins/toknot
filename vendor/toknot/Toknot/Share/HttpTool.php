<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

use Toknot\Boot\Tookit;

/**
 * StreamKit
 *
 * @author chopin
 */
class HttpTool {

    private $wrapper = null;
    private $option = [];
    private $url = '';
    private $fp = null;
    private $scheme = '';
    private $cookie = [];

    public function __construct($url, $method = 'HEAD', $header = '') {
        $this->url = $url;
        $urlPart = parse_url($url);
        $this->scheme = $urlPart['scheme'];
        $this->option = [$urlPart['scheme'] => ['method' => $method, 'header' => $header]];
    }

    public function getScheme() {
        return $this->scheme;
    }

    public function getUrl() {
        return $this->url;
    }

    public function getOption() {
        return $this->option;
    }

    public function getWrapper() {
        $fp = null;
        $this->wrapper = Tookit::getStreamWrappersData($this->url, $this->option, $fp);
        $this->fp = $fp;
    }

    public function getHeader($field) {
        $this->getWrapper();
        foreach ($this->wrapper as $header) {
            if (strpos($header, $field) === 0) {
                list(, $v) = explode(':', $header, 2);
                return trim($v);
            }
        }
        return '';
    }

    public function getStatus() {
        list(, $statusCode) = explode(' ', $this->wrapper[0], 3);
        return $statusCode;
    }

    public static function formatDate($time) {
        return date('D, d M Y H:i:s e', $time);
    }

    public static function formatHeader($field, $value) {
        return "$field: $value\r\n";
    }

    public static function getClientIp($proxyField = null) {
        if ($proxyField) {
            $field = 'HTTP_' . strtoupper($proxyField);
            return Tookit::env($field);
        }

        if (($ip = Tookit::env('HTTP_X_REAL_IP'))) {
            return$ip;
        }
        if (($ip = Tookit::env('HTTP_X_FORWARDED_FOR'))) {
            list($ip) = explode(',', $ip);
            return trim($ip);
        }
        return Tookit::env('REMOTE_ADDR');
    }

    public function addCookie($cookie) {
        $this->option[$this->scheme]['header'] .= "Cookie: $cookie\r\n";
        return $this;
    }

    public function addReferer($referer) {
        $this->option[$this->scheme]['header'] .= "Referer: $referer\r\n";
        return $this;
    }

    public function getPage() {
        $context = stream_context_create($this->option);
        return file_get_contents($this->url, false, $context);
    }

    public function pushCookie($k, $v) {
        $v = urlencode($v);
        $this->cookie[] = "$k=$v";
    }

    public function buildCookie() {
        return implode(';', $this->cookie);
    }
    
    public function request() {
        $context = stream_context_create($this->option);
        $fp = fsockopen($hostname, $port, $errno, $errstr, $timeout);
    }

    public function map($urls) {
        $urls = $urls;
        foreach ($urls as $url) {
            yield $url;
        }
    }
}
