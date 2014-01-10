<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

class Log {

    /**
     * log file save path
     *
     * @var string
     * @access public
     * @static
     */
    public static $savePath = '';

    /**
     * whether enable save the log info
     *
     * @var boolean
     * @access public
     * @static
     */
    public static $enableSaveLog = false;

    /**
     * print debug backtrace
     */
    public static function printTrace() {
        $trace = debug_backtrace();
        print self::traceCss();
        print self::formatTrace($trace);
    }

    public static function message($info) {
        $day = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $message = "[$time] $info\r\n";
        if(self::$enableSaveLog && !empty(self::$savePath)) {
            FileObject::saveContent(self::$savePath . DIRECTORY_SEPARATOR . $day, $message, FILE_APPEND);
        } else {
            echo $message;
        }
        return;
    }
    public static function colorMessage($str, $color = null, $newLine = true) {
        $number = FALSE;
        switch ($color) {
            case 'red':
                $number = 31;
                break;
            case 'green':
                $number = 32;
                break;
            case 'blue':
                $number = 44;
                break;
            case 'yellow':
                $number = 43;
                break;
        }
        if ($number) {
            echo "\033[1;{$number}m";
        }
        echo "$str";
        if ($newLine) {
            echo "\r\n";
        }
        if ($number) {
            echo "\033[0m";
        }
    }
    /**
     * save trace info to log file
     *
     * @param string $traceInfo
     */
    public static function save($traceInfo) {
        if (!self::$enableSaveLog || empty(self::$savePath)) {
            return;
        }
        $day = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $message = "$time [{$_SERVER['REMOTE_ADDR']}]:{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}";
        $traceInfo = $message . strip_tags($traceInfo);
        $traceInfo = "\r\n";
        FileObject::saveContent(self::$savePath . DIRECTORY_SEPARATOR . $day, $traceInfo, FILE_APPEND);
    }

    /**
     * backtrace trans to html
     *
     * @param array $traceArr
     * @return string
     */
    public static function formatTrace($traceArr) {
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
                        $str .= "<span class='ToKnotDebugArgs' title='String(" . strlen($arg) . ") " . $arg . "'><b>String(</b>" . substr($arg, 0, 100) . "<b>)</b></span>";
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

    /**
     * Toknot trace web page style
     *
     * @return string
     */
    public static function traceCss() {
        return '<style type="text/css">
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
    }

}

