<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception;

use \ErrorException;
use Toknot\Di\Log;

/**
 * Toknot Statndrad Exception
 */
class StandardException extends ErrorException {

    protected $code = 0;
    protected $message = '';
    protected $errfile = '';
    protected $errline = '';
    protected $isException = false;
    protected $exceptionMessage = null;
    public $traceArr = array();
   
    /**
     * construct StandardException
     * 
     * @param string $message
     * @param integer $code
     * @param string $file
     * @param integer $line
     */
    public function __construct($message = '', $code = 0, $file = null, $line = null) {
        if ($this->exceptionMessage) {
            $this->message = $this->exceptionMessage;
        } else {
            $this->message = $message;
        }
        $this->errfile = empty($file) ? $this->getFile() : $file;
        $this->errline = empty($line) ? $this->getLine() : $line;
        $this->getErrorType($code);
    }

    static public function errorReportHandler($argv) {
        throw new StandardException($argv[1], $argv[0], $argv[2], $argv[3]);
    }

    public function getErrorType($code) {
        switch ($code) {
            case E_USER_ERROR:
                $type = 'Fatal Error';
                break;
            case E_USER_WARNING:
            case E_WARNING:
                $type = 'Warning';
                $this->isException = true;
                break;
            case E_USER_NOTICE:
            case E_NOTICE:
            case @E_STRICT:
                $type = 'Notice';
                $this->isException = true;
                break;
            case @E_RECOVERABLE_ERROR:
                $type = 'Catchable';
                break;
            case E_COMPILE_ERROR:
                $type = 'PHP Compile Error';
                break;
            case E_ERROR:
                $type = 'PHP Fatal Error';
                break;
            case E_PARSE:
                $type = 'PHP Parse Error';
                break;
            case E_CORE_ERROR:
                $type = 'PHP Core Error';
                break;
            default:
                $type = __CLASS__;
                $this->isException = true;
                break;
        }
        $this->message = "<b>$type : </b>" . $this->message;
    }

    public function getDebugTraceAsString() {

        $str = '<meta content="text/html; charset=utf-8" http-equiv="Content-Type">';
        if (PHP_SAPI != 'cli') {
            $str .= Log::traceCss();
        } else {
            $str .= str_repeat('=', 20) . "\n";
        }
        $str .='<div class="ToknotDebugArea">';
        if (PHP_SAPI == 'cli') {
            $this->message = "\e[1;31m{$this->message}\e[0m";
        }
        $str .="<p class='ToknotMessage'>{$this->message}</p>\n";
        $str .="<div class='ToknotDebugThrow'>Throw Exception in file {$this->errfile} line {$this->errline}</div><ul class='ToKnotTraceItem'>\n";
        if (PHP_SAPI == 'cli') {
            $str .= 'Process ID:' . getmypid() . "\n";
        }
        if (empty($this->traceArr)) {
            $traceArr = $this->getTrace();
            array_shift($traceArr);
            array_shift($traceArr);
            $traceArr = array_reverse($traceArr);
            $str .= $this->earch($traceArr);
        } else {
            $str .= $this->earch($this->traceArr);
        }
        $str .='</ul></div>';
        if (PHP_SAPI == 'cli') {
            $str .= str_repeat('=', 20) . "\n";
            return strip_tags($str);
        } else {
            return $str;
        }
    }

    public function __toString() {
        $traceInfo = $this->getDebugTraceAsString();
        if (DEVELOPMENT) {
            return $traceInfo;
        } else {
            header('500 Internal Server Error');
            Log::save($traceInfo);
            return '500 Internal Server Error';
        }
    }

    public function earch($traceArr) {
        return Log::formatTrace($traceArr);
    }
}
