<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\HTTPTool;

use Toknot\Boot\Tookit;
use Toknot\Boot\GlobalFilter;
use Toknot\Boot\Object;

/**
 * HttpTool
 *
 * @author chopin
 */
class HttpRequest extends Object {

    protected $wrapper = null;
    protected $option = [];
    protected $url = '';
    protected $fp = null;
    protected $scheme = '';
    protected $cookie = [];
    protected $context = null;

    public function __construct($url, $method = 'HEAD', $header = '') {
        $this->url = $url;
        $urlPart = parse_url($url);
        $this->scheme = $urlPart['scheme'];
        $this->option = [$this->scheme => ['method' => $method, 'header' => $header]];
    }

    public function setHttpVersion($ver) {
        $this->option[$this->scheme]['protocol_version'] = $ver;
    }

    public function setTimeout($time) {
        $this->option[$this->scheme]['timeout'] = $time;
    }

    public function setNoError($set = true) {
        $this->option[$this->scheme]['ignore_errors'] = $set;
    }

    public function setMaxRedirects($num) {
        $this->option[$this->scheme]['max_redirects'] = $num;
    }

    public function setContent($content) {
        $this->option[$this->scheme]['content'] = $content;
    }

    public function setUserAgent($agent) {
        $this->option[$this->scheme]['user_agent'] = $agent;
    }

    public function setFollowLocation($set = 0) {
        $this->option[$this->scheme]['follow_location'] = $set;
    }

    public function setRequestFulluri($set = true) {
        $this->option[$this->scheme]['request_fulluri'] = $set;
    }

    public function setProxy($proxy) {
        $this->option[$this->scheme]['proxy'] = $proxy;
    }

    public function getScheme() {
        return $this->scheme;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setOption($option) {
        $this->option = $option;
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
            return GlobalFilter::env($field);
        }

        if (($ip = GlobalFilter::env('HTTP_X_REAL_IP'))) {
            return$ip;
        }
        if (($ip = GlobalFilter::env('HTTP_X_FORWARDED_FOR'))) {
            list($ip) = explode(',', $ip);
            return trim($ip);
        }
        return GlobalFilter::env('REMOTE_ADDR');
    }

    public function addCookie($cookie) {
        $this->option[$this->scheme]['header'] .= "Cookie: $cookie\r\n";
        return $this;
    }

    public function addReferer($referer) {
        $this->option[$this->scheme]['header'] .= "Referer: $referer\r\n";
        return $this;
    }

    public function initContext() {
        $this->context = stream_context_create($this->option);
    }

    public function setContext($context) {
        $this->context = $context;
    }

    public function getContext() {
        return $this->context;
    }

    public function getPage() {
        return file_get_contents($this->url, false, $this->context);
    }

    public function pushCookie($k, $v) {
        $v = urlencode($v);
        $this->cookie[] = "$k=$v";
    }

    public function buildCookie() {
        return implode(';', $this->cookie);
    }

}
