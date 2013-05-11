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
    protected $errcss = '<style>
                        .ToKnotDebugArea {border:1px #555555 solid;background-color:#EEEEEE;padding-left:10px;}
                        .ToKnotMessage {color:#555555;font-size:20px;font-weight:bold;}
                        .ToKnotCallFile {color:#6A8295;}
                        .ToKnotAccess {color:#336258;}
                        .ToKnotTraceItem{list-style-type:none;border-bottom:1px #8397B1 solid;padding:5px;color:#0F4C9E;}
                        .ToKnotDebugArgs{background-color:#FAD5D2;font-size:12px;margin-right:10px;}
                        .ToKnotDebugFunc{color:#176B4E;font-weight:normal;}
                        .ToKnotDebugThrow{color:#A9291F;}
                        .ToKnotDebugProcess {color:#333;font-size:12px;}
                        </style>';

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

    public function getDebugTraceAsString($traceArr = null) {
        
        if ($this->isException == false)
            return $this->message;
        $str = '<meta content="text/html; charset=utf-8" http-equiv="Content-Type"><pre>';
        $str .='<div class="ToknotDebugArea">';
        $str .="<div ><span class='ToknotMessage'>{$this->message}</span>\n<ul>";
        $str .="<div class='ToknotDebugThrow'>Throw Exception in file {$this->errfile} line {$this->errline}</div>\n";
        if (PHP_SAPI == 'cli' && function_exists('posix_getpid')) {
            $str .= 'Process ID:' . posix_getpid() . "\n";
        }
        if (empty($traceArr)) {
            //$str .= $this->earch($this->getTrace());
        } else {
            //$str .= $this->earch($traceArr);
        }
        $str .='</ul></div>';
        if(PHP_SAPI == 'cli') {
            return strip_tags($str);
        } else {
            return $str;
        }
    }

    public function __toString() {
        return $this->getDebugTraceAsString();
    }
    public function debugPrintMessage() {
        
    }

    public function earch($traceArr) {
        $str = '';
        foreach ($traceArr as $key => $value) {
            if (isset($value['function']) && $value['function'] == 'error2debug')
                continue;
            $str .= "<li class='ToknotTraceItem'>#{$key} " . $this->getInfoStr($value) . "</li>\n";
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
