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
    private $fatalError = false;
    public $traceArr = array();
    public $exceptionInstance = null;

    /**
     * construct StandardException
     * 
     * @param string $message
     * @param integer $code
     * @param string $file
     * @param integer $line
     * @param object|null $exceIns
     */
    public function __construct($message = '', $code = 0, $file = null, $line = null, $exceIns = null) {
        if ($this->exceptionMessage) {
            $this->message = $this->exceptionMessage;
        } else {
            $this->message = $message;
        }
        $this->exceptionInstance = $exceIns;
        $this->errfile = empty($file) ? $this->getFile() : $file;
        $this->errline = empty($line) ? $this->getLine() : $line;
        $this->getErrorType($code);
    }

    static public function errorReportHandler($argv) {
        $object = new StandardException($argv[1], $argv[0], $argv[2], $argv[3]);

        //when development finally script all error
        if (DEVELOPMENT === true || $object->fatalError === true) {
            try {
                throw $object;
            } catch (StandardException $e) {
                echo $e;
            }
        } else {
            echo $object;
        }
    }

    public function getErrorType($code) {
        switch ($code) {
            case E_USER_ERROR:
                $type = 'Fatal Error';
                $this->fatalError = true;
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
                break;
            case @E_RECOVERABLE_ERROR:
                $type = 'Catchable';
                break;
            case E_COMPILE_ERROR:
                $type = 'PHP Compile Error';
                $this->fatalError = true;
                break;
            case E_ERROR:
                $type = 'PHP Fatal Error';
                $this->fatalError = true;
                break;
            case E_PARSE:
                $type = 'PHP Parse Error';
                $this->fatalError = true;
                break;
            case E_CORE_ERROR:
                $type = 'PHP Core Error';
                $this->fatalError = true;
                break;
            default:
                $type = get_called_class();
                $this->fatalError = true;
                break;
        }
        if ($this->exceptionInstance) {
            $type = get_class($this->exceptionInstance);
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
        if (PHP_SAPI == 'cli' && !empty($_SERVER['COLORTERM'])) {
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
        if (isset($this->sqls) && is_array($this->sqls)) {
            $str .= '<ul class="ToKnotTraceItem">';
            foreach($this->sqls as $i=>$sql) {
                $str .= "<li>{$sql}:({$this->params[$i]})</li>";
            }
            $str .= '</ul>';
        }
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
            if (PHP_SAPI != 'cli') {
                header('500 Internal Server Error');
            }
            Log::save($traceInfo);
            return '500 Internal Server Error';
        }
    }
    public function save() {
        $traceInfo = $this->getDebugTraceAsString();
        Log::save($traceInfo);
    }
    public function earch($traceArr) {
        return Log::formatTrace($traceArr);
    }

}
