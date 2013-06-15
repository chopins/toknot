<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
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
                        .ToKnotDebugArgs{background-color:#FAD5D2;font-size:12px;margin-right:10px;}
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
            $str .= str_repeat('=',20)."\n";
        }
        $str .='<div class="ToknotDebugArea">';
        if(PHP_SAPI == 'cli') {
            $this->message = "\e[1;31m{$this->message}\e[0m";
        }
        $str .="<p class='ToknotMessage'>{$this->message}</p>\n";
        $str .="<div class='ToknotDebugThrow'>Throw Exception in file {$this->errfile} line {$this->errline}</div><ul class='ToKnotTraceItem'>\n";
        if (PHP_SAPI == 'cli') {
            $str .= 'Process ID:' . getmypid(). "\n";
        }
        if (empty($this->traceArr)) {
            $traceStr = $this->getTraceAsString();
            $str .= '<li>' . str_replace("\n", "</li>\n<li>", $traceStr) . "</li>\n";
        } else {
            $str .= $this->earch($this->traceArr);
        }
        $str .='</ul></div>';
        if (PHP_SAPI == 'cli') {
            $str .= "==============\n";
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
        $key = 0;
        foreach ($traceArr as $value) {
            if (!isset($value['class']) || !isset($value['file'])) {
                continue;
            }
            $str .= "<li>#{$key} {$value['file']}({$value['line']}):{$value['class']}->{$value['function']}()</li>\n";
            $key++;
        }
        return $str;
    }

    public function getInfoStr($arr) {
        $par = $str = '';
        if (!empty($arr['args'])) {
            foreach ($arr['args'] as $key => $value) {
                $par .= '<span class="ToknotDebugSrgs">';
                if (is_array($value)) {
                    $info = print_r($value, true);
                    $par .= '<span title="' . $info . '">Array</span>';
                } elseif (is_object($value)) {
                    $par .= 'Object <span title="' . print_r($value, true) . '">' . get_class($value) . '</span>';
                } else {
                    if (is_string($value)) {
                        if (PHP_CLI == false) {
                            $value = '<span title="' . $value . '">' . substr($value, 0, 32) . '</span>';
                        }
                    }
                    $par .= "'$value'";
                }
                $par .= '</span>';
                $par .= ',';
            }
            $par = substr($par, 0, -1);
        }
        $par .='<b class="ToknotDebugFunc">)</b>';
        $msg = "<b class='ToknotDebugFunc'>";
        if (isset($arr['class'])) {
            $classReflectionInfo = new ReflectionClass($arr['class']);
            $mergeClassInfo = $classReflectionInfo->getDefaultProperties();
            if (isset($arr['file']) && isset($arr['line']) && $arr['file'] != $classReflectionInfo->getFileName()) {
                $fileName = basename($arr['file'], '.php');
                if (class_exists($fileName, false)) {
                    $fileReflectionInfo = new ReflectionClass($fileName);
                    $mergeClassInfo = $fileReflectionInfo->getDefaultProperties();
                }
            }
            $msg .= $arr['class'];
        }
        $msg .= isset($arr['type']) ? $arr['type'] : '';
        if ($arr['function'] == 'unknown')
            $arr['function'] = 'Main';
        $msg .= isset($arr['function']) ? $arr['function'] . '(' : '';
        $msg .= '</b> ';
        if (isset($arr['file'])) {
            $str = " in {$arr['file']} line {$arr['line']};";
        }
        return $msg . $par . $str;
    }

}
