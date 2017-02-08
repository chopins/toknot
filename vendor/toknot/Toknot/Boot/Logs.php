<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

class Logs {

    public static $shortPath = 0;

    /**
     * print debug backtrace
     */
    public static function printTrace() {
        $trace = debug_backtrace();
        print self::formatTrace($trace);
    }

    public static function message($info) {
        $time = date('Y-m-d H:i:s T');
        $message = "[$time] $info" . PHP_EOL;
        echo $message;
    }

    public static function save($logs, $str, $color = null) {
        if ($color) {
            $str = self::addColor($str, $color);
        }
        file_put_contents($logs, $str . PHP_EOL, FILE_APPEND);
    }

    public static function addColor($str, $color) {
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
            default :
                $number = false;
                break;
        }
        $return = '';
        if ($number) {
            $return .= "\033[1;{$number}m";
        }
        $return .= "$str";
        if ($number) {
            $return .= "\033[0m";
        }
        return $return;
    }

    public static function colorMessage($str, $color = null, $newLine = true) {
        $return = self::addColor($str, $color);
        if ($newLine) {
            $return .= PHP_EOL;
        }
        echo $return;
    }

    public static function coalesce(&$arr, $key, $def = '') {
        $arr[$key] = isset($arr[$key]) ? $arr[$key] : $def;
        return $arr[$key];
    }

    public static function getType($value) {
        if (is_string($value)) {
            return 'string';
        } elseif (is_int($value)) {
            return 'integer';
        } elseif (is_float($value)) {
            return 'float';
        } elseif (is_array($value)) {
            return "array";
        } elseif (is_object($value)) {
            return 'object';
        } elseif (is_resource($value)) {
            return 'resource';
        } elseif (is_null($value)) {
            return 'null';
        }
    }

    public static function opeateArg($value, &$str) {
        $argc = count($value['args']) - 1;
        foreach ($value['args'] as $i => $arg) {
            if (is_null($arg)) {
                continue;
            }
            $str .= '<span>';

            $title = is_scalar($arg) ? substr($arg, 0, 20) : print_r($arg, true);
            //$type = self::getType($arg);
            if (is_scalar($arg)) {
                $pad = strlen($arg) > 20 ? '...' : '';
                $str .= "<small><b title='$arg'>'$title$pad'</b></small>, ";
            } elseif (is_array($arg)) {
                $str .= "<small title='$title'><b>Array()</b></small>, ";
            } elseif (is_object($arg)) {
                $cls = get_class($arg);
                $str .= "<small><b>Object(</b><i>$cls</i><b>)</b></small>, ";
            } elseif (is_resource($arg)) {
                $title = get_resource_type($arg);
                $str .= "<small><i>$title</i></small>, ";
            }
            if ($argc == $i) {
                $str = trim($str, ', ');
            }
            $str .= '</span>';
        }
    }

    /**
     * backtrace to html string
     *
     * @param array $traceArr
     * @return string
     */
    public static function formatTrace($traceArr) {
        $str = '<style>.tk-ds-li li{color:#666 ;} '
                . '.tk-ds-li span{display:inline-block;margin:5px;cursor:help;}</style>'
                . '<ul class="tk-ds-li">';
        $str = PHP_SAPI == 'cli' ? '' : $str;
        foreach ($traceArr as $key => $value) {
            $str .= "<li>#{$key} ";
            $file = self::coalesce($value, 'file');
            if (self::$shortPath) {
                $file = '...' . substr($file, self::$shortPath);
            }
            $str .= $file;
            $str .= '(' . self::coalesce($value, 'line') . '):';
            $str .= self::coalesce($value, 'class');
            $str .= self::coalesce($value, 'type');

            if ($value['function'] == 'unknown') {
                $value['function'] = 'main';
            }
            $str .= isset($value['function']) ? "{$value['function']}(" : '';
            if (isset($value['args']) && $value['function'] != 'errorReportHandler') {
                self::opeateArg($value, $str);
            }
            $str .= isset($value['function']) ? ")" : '';
            $str .= '</li>' . PHP_EOL;
        }
        return $str . '</ul>';
    }

}
