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
    private static $supportColor = null;

    const COLOR_BLACK = 188;
    const COLOR_RED = 190;
    const COLOR_GREEN = 192;
    const COLOR_YELLOW = 194;
    const COLOR_BLUE = 196;
    const COLOR_PURPLE = 198;
    const COLOR_WHITE = 202;
    const COLOR_B_BLACK = 10240;
    const COLOR_B_RED = 10496;
    const COLOR_B_GREEN = 10752;
    const COLOR_B_YELLOW = 11008;
    const COLOR_B_BLUE = 11264;
    const COLOR_B_PUPPLE = 11520;
    const COLOR_B_WHITE = 12032;
    const SET_BOLD = 1;

    /**
     * print debug backtrace
     */
    public static function printTrace() {
        $trace = debug_backtrace();
        print self::formatTrace($trace);
    }

    public static function nl() {
        if (PHP_SAPI == 'cli') {
            return PHP_EOL;
        } else {
            return '<br />';
        }
    }

    public static function message($info) {
        $time = date('Y-m-d H:i:s T');
        $message = "[$time] $info" . self::nl();
        echo $message;
    }

    public static function save($logs, $str, $color = null) {
        if ($color) {
            $str = self::addColor($str, $color);
        }
        file_put_contents($logs, $str . PHP_EOL, FILE_APPEND);
    }

    /**
     * convert color value to mask value
     * 
     * @param int $color
     * @return int
     */
    public static function colorMask($color) {
        if ($color >= 30 && $color <= 39) {
            return ($color << 1) | (1 << 7);
        } elseif ($color >= 40 && $color <= 49) {
            return $color << 8;
        }
        return 0;
    }

    public static function strToColor($color) {
        $colors = explode('|', $color);
        $v = 0;
        foreach ($colors as $cs) {
            $str = strtoupper($cs);
            $fcv = "static::COLOR_{$str}";
            if (defined($fcv)) {
                $v = $v | constant($fcv);
                continue;
            }
            $bcv = "static::SET_$str";
            if (defined($bcv)) {
                $v = $v | constant($bcv);
                continue;
            }
        }
        return $v;
    }

    /**
     * add color for string,only support font color,bg color, whether set blod
     * 
     * @param string $str
     * @param int $color
     * @return string
     */
    public static function addCLIColor($str, $color) {
        $mask2 = 1 << 7;
        if (self::$supportColor == null) {
            self::$supportColor = Tookit::env('COLORTERM');
        }
        if (!self::$supportColor) {
            return $str;
        }
        if (!is_numeric($color) && is_string($color)) {
            $color = self::strToColor($color);
        } elseif (!is_numeric($color)) {
            return $str;
        }
        $colorCode = '';
        if ($color & self::SET_BOLD) {
            $colorCode .= '1;';
        }
        $bg = ($color >> 8);
        if ($bg && $bg >= 40 && $bg <= 49) {
            $colorCode .= "$bg;";
        }
        $fcolor = $bg ? ($color ^ ($bg << 8)) : $color;
        $fcolor && $fcolor = (($fcolor ^ $mask2) >> 1);
        if ($fcolor && $fcolor >= 30 && $fcolor <= 39) {
            $colorCode .= "$fcolor;";
        }

        if ($colorCode) {
            $colorCode = trim($colorCode, ';');
            return "\033[{$colorCode}m{$str}\033[0m";
        }
        return $str;
    }

    public static function addWebColor($str, $color) {
        return "<span style=\"color:$color;\">$str</span>";
    }

    /**
     * var_dump $value
     * 
     * @param mixed $value
     */
    public static function dump($value) {
        echo '<pre>';
        var_dump($value);
        echo '</pre>';
    }

    /**
     * 
     * @param string $str
     * @param int $color
     * @param boolean $newLine
     */
    public static function colorMessage($str, $color = null, $newLine = true) {
        if (PHP_SAPI == 'cli') {
            $return = self::addCLIColor($str, $color);
        } else {
            $return = self::addWebColor($str, $color);
        }
        if ($newLine) {
            $return .= self::nl();
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

    public static function opeateArg($args, &$str) {
        $argc = count($args) - 1;

        foreach ($args as $i => $arg) {
            if (is_null($arg)) {
                continue;
            }
            $str .= '<span>';

            $title = is_scalar($arg) ? substr($arg, 0, 20) : substr(print_r($arg, true), 0, 500);
            $title = htmlentities($title);
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
            $function = Tookit::coalesce($value, 'function');
            if ($value['function'] == 'errorReportHandler') {
                continue;
            }

            $str .= "<li>#{$key} ";
            $file = Tookit::coalesce($value, 'file');
            if (self::$shortPath) {
                $file = '...' . substr($file, self::$shortPath);
            }
            $str .= $file;
            $str .= '(' . Tookit::coalesce($value, 'line') . '):';
            $str .= Tookit::coalesce($value, 'class');
            $str .= Tookit::coalesce($value, 'type');

            if ($function == 'unknown') {
                $function = 'main';
            }
            $str .= empty($function) ? '' : "$function(";
            if (isset($value['args'])) {
                self::opeateArg($value['args'], $str);
            }
            $str .= isset($value['function']) ? ")" : '';
            $str .= '</li>' . PHP_EOL;
        }
        return $str . '</ul>';
    }

}
