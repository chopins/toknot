<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

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
        print self::formatTrace($trace);
    }

    public static function message($info) {
        $day = date('Y-m-d');
        $time = date('Y-m-d H:i:s T');
        $message = "[$time] $info" . PHP_EOL;
        if (self::$enableSaveLog && !empty(self::$savePath)) {
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
                $number = 34;
                break;
            case 'yellow':
                $number = 33;
                break;
            case 'black':
                $number = 30;
                break;
            case 'white':
                $number = 37;
                break;
            case 'purple':
                $number = 35;
                break;
        }
        $return = '';
        if ($number) {
            $return .= "\033[1;{$number}m";
        }
        $return .= "$str";
        if ($newLine) {
            $return .= PHP_EOL;
        }
        if ($number) {
            $return .= "\033[0m";
        }
        echo $return;
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
        $time = date('Y-m-d H:i:s T');
        $message = '';
        $traceInfo = $message . strip_tags($traceInfo) . PHP_EOL;
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
            if($value['function'] == 'main') {
                $style = 'style="color:#AAA;"';
            } else if(isset($value['class']) && strpos($value['class'],'Toknot\\') === 0) {
                $style = 'style="color:#AAA;"';
            } else {
                $style = '';
            }
            
            $str .= "<li {$style}>#{$key} ";
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
                    $str .= '<span style="display:inline-block;margin:5px;">';
                    if (is_string($arg)) {
                        $str .= "<small title='String(" . strlen($arg) . ") " . $arg . "'><b>String(</b><i>" . substr($arg, 0, 100) . "</i><b>)</b></small>";
                    } elseif (is_int($arg)) {
                        $str .= "<small><b>Integer(</b><i>" . substr($arg, 0, 100) . "</i><b>)</b></small>";
                    } elseif (is_float($arg)) {
                        $str .= "<small><b>Float(</b><i>" . substr($arg, 0, 100) . "</i><b>)</b></small>";
                    } elseif (is_array($arg)) {
                        $str .= "<small title='" . print_r($arg, true) . "'><b>Array()</b></small>";
                    } elseif (is_object($arg)) {
                        $str .= "<small title='" . print_r($arg, true) . "'><b>Object(</b> <i>" . get_class($arg) . "</i><b>)</b></small>";
                    } elseif (is_resource($arg)) {
                        $str .= "<small><i>" . print_r($arg, true) . '</i></small>';
                    }
                    $str .= '</span>';
                }
            }
            $str .= isset($value['function']) ? ")" : '';
            $str .= '</li>' . PHP_EOL;
        }
        return $str;
    }

}
