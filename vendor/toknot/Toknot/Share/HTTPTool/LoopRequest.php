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

    /**
     * loop get page
     * 
     * <code>
     * //example 1
     * $this->loopGet(function() {
     *      //your code
     *      if($retry) {
     *          return 'retry';
     *      } elseif($exit) {
     *          return 'exit';
     *      }
     * }, 'retry', 'exit');
     * 
     * //example 2
     * $this->loopGet(function() {
     *      //your code
     *      if($retry) {
     *          return 'retry';
     *      } elseif($exit) {
     *          return 'exit';
     *      }
     * }, ['retry'=> functin() { 
     *       //before retry code
     *   }] , ['exit'=>function() { 
     *      //before exit code
     *  }]);
     *
     *
     * </code>
     * 
     * @param callable $callable
     * @param string|array $retry   if is array, key is flag,value is callable
     * @param string|array $exit    if is array, key is flag,value is callable
     * @return null
     */
    public function loopGet($callable, $retry = [], $exit = []) {
        $queryFlag = strpos($this->baseUrl, '?') === false ? '?' : '';
        $pn = $this->startIdx;
        while ($pn < $this->maxIdx) {
            $res = $this->loopCall($queryFlag, $pn);

            $callRet = self::callFunc($callable, $res);

            if ($callRet == $retry) {
                continue;
            }

            if ($callRet == $exit) {
                return;
            }
            if (is_array($retry)) {
                reset($retry);
                $retryFlag = current($retry);
                if ($callRet == $retryFlag) {
                    self::callFunc($retry[$retryFlag]);
                    continue;
                }
            }
            if (is_array($exit)) {
                reset($exit);
                $exitFlag = current($exit);
                if ($callRet == $exitFlag) {
                    self::callFunc($exit[$exitFlag]);
                    return;
                }
            }
            $this->lastUrl = $res[1];
            $pn++;
        }
    }

}
