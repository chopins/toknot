<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\HTTPTool;

use Toknot\Boot\Object;

/**
 *  LoopRequest
 *
 * @author chopin
 */
class LoopRequest extends Object {

    protected $baseUrl = '';
    protected $startIdx = 1;
    protected $step = 1;
    protected $maxIdx = 1;
    protected $argName = 'id';
    protected $method = 'GET';
    protected $httpVersion = 1.1;
    protected $lastUrl = '';
    protected $context = null;
    protected $retry = 'retry';
    protected $retryCallable = null;
    protected $exit = 'break';
    protected $exitCallable = null;

    public function __construct($url, $method = 'GET', $header = '') {
        $this->baseUrl = $url;
        $this->method = $method;
        $this->context = new HttpRequest($url, $method, $header);
    }

    public function setArgName($name) {
        $this->argName = $name;
    }

    public function setStartIdx($idx) {
        $this->startIdx = $idx;
    }

    public function setStep($step) {
        $this->step = $step;
    }

    public function setBaseUrl($baseurl) {
        $this->baseUrl = $baseurl;
    }

    public function setMaxIdx($idx) {
        $this->maxIdx = $idx;
    }

    public function getContext() {
        return $this->context;
    }

    public function registerRetryEvent($ev, $callable) {
        $this->retry = $ev;
        $this->retryCallable = $callable;
    }

    public function registerLoopExitEvent($ev, $callable) {
        $this->exit = $ev;
        $this->exitCallable = $callable;
    }

    protected function loopCall($queryFlag, $pn) {
        $url = "{$this->baseUrl}{$queryFlag}{$this->argName}=$pn";
        $t = new HttpRequest($url, $this->method, $this->header);
        $option = $this->context->getOption();
        $t->setOption($option);
        $t->addReferer($this->lastUrl);
        $t->initContext();
        $content = $t->getPage();
        return [$content, $url];
    }

    protected function callEvent($callRet) {
        if ($callRet == $this->retry) {
            if ($this->retryCallable) {
                self::callFunc($this->retryCallable);
            }
            return 1;
        }
        if ($callRet == $this->exit) {
            if ($this->exitCallable) {
                self::callFunc($this->exitCallable);
            }
            return 2;
        }
        return 3;
    }

    public function loopGet($callable = null) {
        $queryFlag = strpos($this->baseUrl, '?') === false ? '?' : '';
        $pn = $this->startIdx;
        while ($pn < $this->maxIdx) {
            $res = $this->loopCall($queryFlag, $pn);
            $callRet = self::callFunc($callable, $res);
            $ev = $this->callEvent($callRet);

            if ($ev == 1) {
                continue;
            } elseif ($ev == 2) {
                break;
            }
            $this->lastUrl = $res[1];
            $pn++;
        }
    }

}
