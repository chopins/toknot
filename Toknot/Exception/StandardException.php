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

class StandardException  extends ErrorException {
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
    public function __construct($message = '', $code =0,$file= null,$line= null) {
        if($this->exceptionMessage) {
            $this->message = $this->exceptionMessage;
        } else {
            $this->message = $message;
        }
        $this->errfile = empty($file) ? $this->getFile() : $file;
        $this->errline = empty($line) ? $this->getLine() : $line;
        $this->getErrorType($code);
    }
    static public function errorReportHandler($argv) {
        throw new StandardException($argv[1], $argv[0], $argv[2],$argv[3]);
    }


    public function getErrorType($code) {
        switch($code) {
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
        if($this->isException == false) return $this->message;
        $str = '';
        $str .='<div class="debug_area">';
        $str .="<div ><span class='message'>{$this->message}</span>\n<ul>";
        $str .="<div class='debug_throw'>Throw Exception in file {$this->errfile} line {$this->errline}</div>\n";
        if(PHP_CLI && function_exists('posix_getpid')) {
            $str .= 'Process ID:'.posix_getpid()."\n";
        }
        if(defined('__X_CALL_PAGE_FILE__')) {
            $str .='<div ><span class="call_file">Call PHP File is '.__X_CALL_PAGE_FILE__."</span>\n";
        }
        if(defined('__X_URI__')) {
            $str .='<div ><span class="access">Access URL address is '.$_SERVER['REQUEST_METHOD'].' http://'.$_SERVER['HTTP_HOST'].__X_URI__."</span>\n";
        }
        //if(PHP_CLI) $str .= 'Process ID is '.posix_getpid()."\n";
        if(empty($traceArr)) {
            $str .= $this->earch($this->getTrace());
        } else {
            $str .= $this->earch($traceArr);
        }
        $str .='</ul></div>';
        return strip_tags($str);
//        if(__X_SHOW_ERROR__) {
//            $__X_RUN_TIME__ = microtime(true) - __X_RUN_START_TIME__;
//            $str .= "<div class='debug_process'>Processed:{$__X_RUN_TIME__} second</div></div>";
//            if(isset($_ENV['__X_AJAX_REQUEST__']) && $_ENV['__X_AJAX_REQUEST__']) {
//                return strip_tags($str);
//            } elseif(PHP_SAPI == 'cli' && isset($_ENV['__X_OUT_BROWSER__'])
//                                       && $_ENV['__X_OUT_BROWSER__'] ==false) {
//                return strip_tags($str);
//            } else {
//                $str = $this->errcss . $str;
//                return $str;
//            }
//        }
//        if(__X_SHOW_ERROR__ === null) {
//            not_found();
//        }
//        $str = strip_tags($str);
//        $str = '----------------------------'.date('Y-m-d H:i:s')."------------------------\n$str";
//        $str .= isset($GLOBALS['_CFG']) ? $GLOBALS['_CFG']->exception_seg_line."\n"
//                : "===========================================================================\n";
//        file_put_contents(__X_APP_PHP_ERROR_LOG__,$str,FILE_APPEND);
//        not_found();
//        return false;
    }
    public function __toString() {
        return $this->getDebugTraceAsString();
    }
    public function earch($traceArr) {
        $str = '';
        foreach($traceArr as $key => $value) {
            if(isset($value['function']) && $value['function'] == 'error2debug') continue;
            $str .= "<li class='trace_item'>#{$key} " .$this->getInfoStr($value) ."</li>\n";
        }
        return $str;
    }
    public function getInfoStr($arr) {
        $par = $str = '';
        
        if(!empty($arr['args'])) {
            foreach($arr['args'] as $key=>$value) {
                $par .= '<span class="debug_args">';
                if(is_array($value)) {
                    $info = print_r($value,true);
                    $par .= '<span title="'.$info.'">Array</span>';
                }elseif(is_object($value)) {
                    $par .= 'Object <span title="'.print_r($value,true).'">'.get_class($value).'</span>';
                } else {
                    if(is_string($value)) {
                        if(PHP_CLI == false) {
                            $value = '<span title="'.$value.'">'. substr($value,0,32). '</span>';
                        }
                    }
                    $par .= "'$value'";
                }
                $par .= '</span>';
                $par .= ',';
            }
            $par = substr($par,0,-1);
        }
        $par .='<b class="debug-func">)</b>';
        $msg = "<b class='debug-func'>";
        if(isset($arr['class'])) {
            $class_reflection_info = new ReflectionClass($arr['class']);
            $merge_class_info = $class_reflection_info->getDefaultProperties();
            if(isset($merge_class_info['_xclass_merge_class_info'])) {
                if(isset($arr['function'])) {
                    foreach($merge_class_info['_xclass_merge_class_info'] as $merge_class_name) {
                        if(empty($merge_class_name)) continue;
                        $tref = new ReflectionClass($merge_class_name);
                        if($tref->hasMethod($arr['function'])) {
                            $msg .= "[merge from class] ";
                            $arr['class'] = $merge_class_name;
                            $arr['line'] = $tref->getMethod($arr['function'])->getStartLine();
                            $arr['file'] = $tref->getFileName();
                            break;
                        }
                    }
                }
            } else if(isset($arr['file']) && isset($arr['line']) && $arr['file'] != $class_reflection_info->getFileName()) {
                $file_name = basename($arr['file'],'.php');
                if(class_exists($file_name, false)) {
                    $file_reflection_info = new ReflectionClass($file_name);
                    $merge_class_info = $file_reflection_info->getDefaultProperties();
                    if(!empty($merge_class_info['_xclass_merge_class_info'])) {
                        foreach($file_reflection_info->getMethods() as $mref) {
                            if($arr['line'] > $mref->getStartLine() && $arr['line']<$mref->getEndLine()) {
                                foreach($merge_class_info['_xclass_merge_class_info'] as $merge_class_name) {
                                    if(empty($merge_class_name)) continue;
                                    $__tmp_ref = new ReflectionClass($merge_class_name);
                                    if($__tmp_ref->hasMethod($mref->getName())) {
                                        $msg .= '[instance of] ';
                                        $call_line = file_line($arr['file'],$arr['line']);
                                        $arr['file'] = $__tmp_ref->getFileName();
                                        $arr['line'] = file_str_line($arr['file'],$call_line);
                                        break;
                                    }
                                }
                                break;
                            }
                        }
                    }
                } 
            } 
            $msg .= $arr['class'];
        }
        $msg .= isset($arr['type']) ? $arr['type'] :'';
        if($arr['function'] == 'unknown') $arr['function'] = 'Main';
        $msg .= isset($arr['function'])? $arr['function'] .'(': '';
        $msg .= '</b> ';
        if(isset($arr['file'])) {
            $str =  " in {$arr['file']} line {$arr['line']};";
        }
        return $msg. $par . $str;
    }
}
