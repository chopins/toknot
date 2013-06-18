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
use \ReflectionClass;

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
    protected $errcss = '<style>
                        .ToKnotDebugArea {border:1px #CCCCCC solid;background-color:#EEEFFF;padding:0;font-family:Helvetica,arial,freesans,clean,sans-serif;}
                        .ToKnotDebugArea ul {margin-top:0;}
                        .ToKnotMessage {color:#666666;font-size:18px;font-weight:bold;padding:10px;margin:0px;background-color:#D6E685;border-bottom:1px solid #94DA3A;}
                        .ToKnotCallFile {color:#6A8295;}
                        .ToKnotAccess {color:#336258;}
                        .ToKnotTraceItem{list-style-type:none;padding:10px;color:#0F4C9E;font-size:15px;}
                        .ToKnotTraceItem li {padding:5px;}
                        .ToKnotDebugArgs{text-decoration:underline;font-size:12px;margin:0 3px;}
                        .ToKnotDebugArgs b {font-size:15;margin:0 3px;}
                        .ToKnotDebugFunc{color:#176B4E;font-weight:normal;}
                        .ToKnotDebugThrow{color:#D14836;font-weight:bold;background-color:#FFECCC;padding:8px;}
                        .ToKnotDebugProcess {color:#333;font-size:12px;}
                        </style>';

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
//        if ($this->isException == false)
//            return $this->message;
        $str = '<meta content="text/html; charset=utf-8" http-equiv="Content-Type">';
        if (PHP_SAPI != 'cli') {
            $str .= $this->errcss;
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
        if (DEVELOPMENT) {
            return $this->getDebugTraceAsString();
        } else {
            header('500 Internal Server Error');
            return '500 Internal Server Error';
        }
    }

    public function earch($traceArr) {
        $str = '';
        foreach ($traceArr as $key => $value) {
            $str .= "<li>#{$key} ";
            $str .= isset($value['file']) ? $value['file'] : '';
            $str .= isset($value['line']) ? "({$value['line']}): " : '';
            $str .= isset($value['class']) ? $value['class'] : '';
            $str .= isset($value['type']) ? $value['type'] : '';
            if ($value['function'] == 'unknown') {
                $value['function'] = 'main';
            }
            $str .= isset($value['function']) ? "{$value['function']}(" : '';
            if (isset($value['args'])) {
                foreach ($value['args'] as $arg) {
                    if (is_string($arg)) {
                        $str .= "<span class='ToKnotDebugArgs' title='String(" . strlen($arg) . ") ". $arg ."'><b>String(</b>" . substr($arg, 0, 100) . "<b>)</b></span>";
                    } elseif (is_int($arg)) {
                        $str .= "<span class='ToKnotDebugArgs'><b>Integer(</b>" . substr($arg, 0, 100) . "<b>)</b></span>";
                    } elseif (is_float($arg)) {
                        $str .= "<span class='ToKnotDebugArgs'><b>Float(</b>" . substr($arg, 0, 100) . "<b>)</b></span>";
                    } elseif (is_array($arg)) {
                        $str .= "<span class='ToKnotDebugArgs' title='" . print_r($arg, true) . "'><b>Array()</b></span>";
                    } elseif (is_object($arg)) {
                        $str .= "<span class='ToKnotDebugArgs' title='" . print_r($arg, true) . "'><b>Object(</b> " . get_class($arg) . " <b>)</b></span>";
                    } elseif (is_resource($arg)) {
                        $str .= "<span calss='ToKnotDebugArgs'" > print_r($arg, true) . '</span>';
                    }
                }
            }
            $str .= isset($value['function']) ? ")" : '';
            $str .= "</li>\n";
        }
        return $str;
    }
}
