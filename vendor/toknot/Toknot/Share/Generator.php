<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

use Toknot\Boot\Object;

/**
 * Generator
 *
 * @author chopin
 */
class Generator extends Object {

    public static function y($param) {
        yield $param;
    }

    public static function iteration($start, $end, $step = 1) {
        for ($i = $start; $i < $end; $i = $i + $step) {
            yield $i;
        }
    }

    public static function gloop($cont, $callable, $param) {
        while ($cont) {
            yield self::callFunc($callable, $param);
        }
    }

    public static function sloop($cont, $callable) {
        while ($cont) {
            self::callFunc($callable, yield);
        }
    }

}
