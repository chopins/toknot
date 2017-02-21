<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;
use Toknot\Boot\Tookit;

class Logs {

    public static $shortPath = 0;

    const COLOR_BLACK = 30;
    const COLOR_RED = 31;
    const COLOR_GREEN = 32;
    const COLOR_YELLOW = 33;
    const COLOR_BLUE = 34;
    const COLOR_PURPLE = 35;
    const COLOR_WHITE = 37;
    const COLOR_B_BLACK = 40;
    const COLOR_B_RED = 41;
    const COLOR_B_GREEN = 42;
    const COLOR_B_YELLOW = 43;
    const COLOR_B_BLUE = 44;
    const COLOR_B_PUPPLE = 45;
    const COLOR_B_WHITE = 47;

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

    private static function checkColorValue($color, $isbg = false) {
        $bg = $isbg ? 'B_' : '';
        if (is_numeric($color) && $color >= 30 && $color <= 49) {
            $number = $color;
        } elseif (is_string($color)) {
            $name = strtoupper($color);
            if (defined("static::COLOR_$bg$name")) {
                $number = constant("static::COLOR_$bg$name");
            } else {
                $number = false;
            }
        } else {
            $number = false;
        }
        return $number;
    }

    public static function addColor($str, $color, $bg = '', $bold = false) {
        if (empty($_SERVER['COLORTERM'])) {
            return $str;
        }
        $colorCode = '';
        if ($bold) {
            $colorCode .= '1;';
        }
        $number = self::checkColorValue($color);
        if ($number) {
            $colorCode .= "$number;";
        }

        if ($bg) {
            $bgnumber = self::checkColorValue($bg, true);
            if ($bgnumber) {
                $colorCode .= "$bgnumber;";
            }
        }
        $return = '';
        if ($colorCode) {
            $colorCode = trim($colorCode, ';');
            $return .= "\033[{$colorCode}m";
        }
        $return .= "$str";
        if ($colorCode) {
            $return .= "\033[0m";
        }
        return $return;
    }

    public static function colorMessage($str, $color = null, $newLine = true, $bg = '', $bold = false) {
        $return = self::addColor($str, $color, $bg, $bold);
        if ($newLine) {
            $return .= PHP_EOL;
        }
        echo $return;
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

            $title = is_scalar($arg) ? substr($arg, 0, 20) : substr(print_r($arg, true), 0, 500);
            //$type = self::getType($arg);
            if (is_scalar($arg)) {
                $pad = strlen($arg) > 20 ? '...' : '';
                $arg = substr($arg, 0, 500);
                $str .= "<small><b title='$arg'>'$title$pad'</b></small>, ";
            } elseif (is_array($arg)) {
                $cnt = count($arg);
                $str .= "<small title='$title'><b>Array($cnt)</b></small>, ";
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
            $file = Tookit::coalesce($value, 'file');
            if (self::$shortPath) {
                $file = '...' . substr($file, self::$shortPath);
            }
            $str .= $file;
            $str .= '(' . Tookit::coalesce($value, 'line') . '):';
            $str .= Tookit::coalesce($value, 'class');
            $str .= Tookit::coalesce($value, 'type');

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
