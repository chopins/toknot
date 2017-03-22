<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

use Toknot\Exception\BaseException;

/**
 * SpamerTest
 *
 * @author chopin
 */
class RobotSpot extends ChineseNumber {

    const EN_ADD = 'add';
    const EN_MINUS = 'minus';
    const EN_DIV = 'divided by';
    const EN_TIMES = 'times';
    const ZH_ADD = '加';
    const ZH_MINUS = '减';
    const ZH_DIV = '除以';
    const ZH_TIMES = '乘';

    private $lang = 'en';

    public function __construct($lang = 'en') {
        $this->setLang($lang);
    }

    public function supportLang($lang = null) {
        $langs = ['zh', 'en'];
        if (!$lang) {
            return $langs;
        }
        return array_search($lang, $langs) !== false;
    }

    public function setLang($lang) {
        if ($this->supportLang($lang)) {
            $this->lang = $lang;
            return true;
        }
        return false;
    }

    public function number2en($number) {
        if ($number > 1000 && strpos($number, '.') !== false) {
            throw new BaseException('give number must less 1000 or is int');
        }
        $sign = $number < 0 ? 'minus ' : '';
        $number = abs($number);
        $strNumber = (string) $number;
        $numberTable = ['zero ', 'one ', 'two ', 'three ', 'four ', 'five ', 'six ', 'seven ', 'eight ', 'nine '];
        $lessTwenty = ['ten ', 'eleven ', 'twelve ', 'thirteen ', 'fourteen ', 'fifteen ', 'sixteen ', 'seventeen ', 'eighteen ', 'nineteen '];
        $ten = ['', '', 'twenty ', 'thirty ', 'fourty ', 'fifty ', 'sixty ', 'seventy ', 'eighty ', 'ninety '];
        //$unitTable = [$ten, 'hundred ', 'thousand ', 'million ', 'billion '];
        //$dot = 'point ';
        if ($number < 10) {
            $res = $numberTable[$number];
        } elseif ($number < 20) {
            $i = $strNumber{1};
            $res = $lessTwenty[$i];
        } elseif ($number < 1000) {
            $len = strlen($number);
            $revnumber = strrev($number);
            $res = '';
            if ($len == 3) {
                $res .= $numberTable[$revnumber{2}] . 'hundred ';
            }
            if ($len > 1) {
                $res .= $ten[$revnumber{1}];
            }
            if ($revnumber{0} > 0) {
                $res .= $numberTable[$revnumber{0}];
            }
        }
        return $sign . trim($res);
    }

    public function calculation(&$result) {
        $operator = ['ADD', 'MINUS', 'DIV', 'TIMES'];
        $ask1 = $this->lang == 'en' ? 'How much is' : '';
        $ask2 = $this->lang == 'en' ? '' : '等于多少';
        $method = "number2{$this->lang}";
        $op = $operator[array_rand($operator)];
        $v1 = mt_rand(2, 99);
        $v2 = mt_rand(2, 99);
        if ($op == 'DIV') {
            $v2 = mt_rand(2, 10);
            $result = mt_rand(2, 10);
            $v1 = $v2 * $result;
        } elseif ($op == 'ADD') {
            $result = $v1 + $v2;
        } elseif ($op == 'MINUS') {
            $result = $v1 - $v2;
        } elseif ($op == 'TIMES') {
            $v1 = mt_rand(2, 10);
            $v2 = mt_rand(2, 10);
            $result = $v1 * $v2;
        }
        $op = constant('self::' . strtoupper($this->lang) . "_$op");
        $v1 = $this->{$method}($v1);
        $v2 = $this->{$method}($v2);
        $result = [$result, $this->{$method}($result)];
        return "$ask1 $v1 $op $v2 $ask2?";
    }

    public function findOrder(&$result) {
        $elementNumber = 6;
        $rand = mt_rand(1, 3);
        $step = mt_rand(1, 3);
        $endOffset = $elementNumber * $step;
        if ($rand == 1) {
            $start = mt_rand(1, 100);
            $end = $start + $endOffset;
            $order = range($start, $end, $step);
        } elseif ($rand == 2) {
            $pos = mt_rand(0, 1) > 0 ? ['a', 'z'] : ['A', 'Z'];
            $allword = range($pos[0], $pos[1]);
            $alp = mt_rand(0, 1) ? range('A', 'Z') : range('a', 'z');
            $start = mt_rand(0, 26 - $endOffset);
            $end = $start + $endOffset;
            $order = [];
            $repeatNum = mt_rand(1, 4);
            for ($i = $start; $i < $end; $i = $i + $step) {
                $prev = $i -1 > 0 ? $i - 1 : 0;
                $repeat = $repeatNum / 2 == 0 ? $allword[$i] : $alp[$prev];
                $order[] = $allword[$i] . str_repeat($repeat, $repeatNum);
            }
        } else {
            $e = mt_rand(1, 100);
            $offset = 1;
            $order = [];
            for ($i = 0; $i < $elementNumber; $i++) {
                $e = $e + $offset;
                $order[] = $e;
                $offset = $offset + $step;
            }
        }

        $ri = array_rand($order);

        $result = $order[$ri];
        $method = "number2{$this->lang}";
        if ($rand != 2) {
            $res = '';
            $repeat = mt_rand(1, 4);
            foreach ($order as $i => $e) {
                if ($i == $ri) {
                    $res .= '___; ';
                } else {
                    $res .= $this->{$method}($e) . '; ';
                }
            }
            $result = [$result, $this->{$method}($result)];
        } else {
            $order[$ri] = '__';
            $res = implode('; ', $order);
        }

        return ($this->lang == 'en' ? 'Derive value of underlined place: ' : '推导出下划线的值：') . $res;
    }


}
