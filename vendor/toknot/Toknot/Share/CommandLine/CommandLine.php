<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\CommandLine;

class CommandLine {

    const NL = PHP_EOL;
    const HT = "\t";
    const CR = "\r";
    const BS = "\b";
    const SP = "\s";

    public $prefixMaxLen = 30;
    public $suffixMaxLen = 10;

    public function ttyCols($defaultCols = 150) {
        $cols = trim(shell_exec('tput cols'));
        if (empty($cols)) {
            $size = trim(shell_exec('stty size'));
            if (!empty($size)) {
                list(, $cols) = explode(' ', $size);
            }
        }
        if (!$cols || $cols <= 20) {
            $cols = $defaultCols;
        }
        return $cols;
    }

    protected function formatPercent($percent) {
        $fpercent = floor($percent);
        if ($fpercent > 100) {
            $fpercent = 100;
        }
        $perStr = sprintf("%3d", $fpercent);
        $per = "[$perStr/100]";
        return $per;
    }

    protected function maxPrefixMsgLen() {
        $cols = $this->ttyCols();
        $len = floor($cols / 3);
        return $len > $this->prefixMaxLen ? $this->prefixMaxLen : $len;
    }

    protected function maxSuffixMsgLen() {
        $cols = $this->ttyCols();
        $len = floor($cols / 8);
        return $len > $this->suffixMaxLen ? $this->suffixMaxLen : $len;
    }

    protected function formatPrefixMessage($msg, &$msgLen) {
        $msgLen = $this->maxPrefixMsgLen();
        return mb_substr($msg, 0, $msgLen);
    }

    protected function formatSuffixMessage($msg, &$msgLen) {
        $msgLen = $this->maxSuffixMsgLen();
        return mb_substr($msg, 0, $msgLen);
    }

    protected function processStr($cols, $percent, $char = '=') {
        $num = floor($cols - 5 - ($percent / 100));
        return sprintf("[%{$num}s]", $char);
    }

    public function process($percent, $prefix = '', $suffix = '', $char = '=') {
        $plen = $slen = 10;
        $cols = $this->ttyCols();
        $fprefix = $this->formatPrefixMessage($prefix, $plen);
        $fsuffix = $this->formatSuffixMessage($suffix, $slen);
        $percentStr = $this->formatPercent($percent);
        $lastCols = $cols - $plen - $slen - strlen($percentStr);
        $processStr = $this->processStr($lastCols, $percent, $char);

        return "{$fprefix} {$processStr} {$percentStr} {$fsuffix}";
    }

    protected function getDataMaxLen(&$data) {
        $maxKeyLen = $maxDescLen = 0;
        array_walk($data, function($desc, $key) use(&$maxKeyLen, &$maxDescLen) {
            $keyLen = strlen($key);
            $descLen = strlen($desc);
            if ($keyLen > $maxKeyLen) {
                $maxKeyLen = $keyLen;
            }

            if ($descLen > $maxDescLen) {
                $maxDescLen = $descLen;
            }
        });
        return [$maxKeyLen, $maxDescLen];
    }

    protected function getColumnWidth($maxLen) {
        $cols = $this->ttyCols();
        
        $maxWidth = $maxLen[0] + $maxLen[1];
        if ($maxWidth + 4 < $cols) {
            return $maxLen;
        } else {
            $descLen = $cols - $maxLen[0] - 4;
            return [$maxLen[0], $descLen];
        }
    }

    public function column($data) {
        $maxLen = $this->getDataMaxLen($data);
        $cols = $this->getColumnWidth($maxLen);
        array_walk($data, function($desc, $key) use($cols) {
            $len = strlen($desc);

            $paramLen = $cols[0] + 4;

            if ($len > $cols[1]) {
                $descPart1 = substr($desc, 0, $cols[1]);
                printf("%s%4s%s%s", $key, self::SP, $descPart1, self::NL);
                $descPart2 = substr($desc, $cols[1]);
                printf("%{$paramLen}s%s%s", self::SP, $descPart2, self::NL);
            } else {
                printf("%s%4s%s%s", $key, self::SP, $desc, self::NL);
            }
        });
    }

}
