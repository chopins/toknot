<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Toknot\Share;

use Toknot\Boot\Logs;
use Toknot\Exception\BaseException;
use Toknot\Boot\Tookit;

/**
 *  CommandLine
 *
 * @author chopin
 */
class CommandLine {

    private $progressMsg = 'speed';
    private $progressColor = 'white';

    public function getcols($defaultCols = 150) {
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

    public function progress($speed, $message = '', $color = null) {
        if (!is_numeric($speed)) {
            throw new BaseException('speed must is numeric');
        }
        $this->progressMsg = $message ? $message : $this->progressMsg;
        $this->progressColor = $color ? $color : $this->progressColor;
        ($speed > 100) && ($speed = 100);
        $step = floor($speed / 10);
        $msglen = Tookit::strlen($this->progressMsg);
        $prog = '[' . str_repeat('=', $step) . str_repeat(' ', 10 - $step) . ']' . "$speed%";
        $proglen = strlen($prog);

        $space = $this->getcols() - $msglen - $proglen;
        if ($space < 0) {
            $this->progressMsg = Tookit::substr($this->progressMsg, 0, $msglen - abs($space));
        }
        $msg = $this->progressMsg . str_repeat(' ', $space) . $prog . "\r";
        $this->message($msg, $color, false);
    }

    public function nl() {
        echo PHP_EOL;
    }

    public function colsAlign($data, $fixed = null, $color = null) {
        $ttycols = $this->getcols();
        $maxlen = [];
        array_map(function($line) use(&$maxlen) {
            array_walk(function($part, $k) use(&$maxlen) {
                $len = Tookit::strlen($part);
                (isset($maxlen[$k]) && $maxlen[$k] < $len) && ($maxlen[$k] = $len);
            }, $line);
        }, $data);
        $partCnt = count($maxlen);
        $msglen = array_sum($maxlen);
        $spacelen = $ttycols - $msglen;
        if ($spacelen < 0) {
            if (isset($maxlen[$fixed])) {
                $avglen = floor(($ttycols - $maxlen[$fixed]) / ($partCnt - 1));
                $remainder = $ttycols - $maxlen[$fixed] - $avglen * ($partCnt - 1);
            } else {
                $avglen = floor($ttycols / $partCnt);
                $remainder = $ttycols - $avglen * $partCnt;
            }
            foreach ($maxlen as $k => $v) {
                if ($v < $avglen && $fixed != $k) {
                    $remainder += ($avglen - $v);
                }
            }
            $exceed = true;
        } else {
            $remainder = $spacelen;
            $exceed = false;
        }
        reset($maxlen);
        while ($remainder > 0) {
            $get = each($maxlen);
            if (!$get) {
                reset($maxlen);
                continue;
            }
            list($k, $v) = $get;

            if (($k == $fixed && $v < $avglen) || (!$exceed && $k == $partCnt - 1)) {
                continue;
            }

            $maxlen[$k] = $avglen + 1;
            $remainder--;
        }
        array_map(function($line) use($maxlen) {
            array_walk($line, function($col, $k) use($maxlen) {
                $msg = Tookit::substr($col, 0, $maxlen[$k]);
                $this->message(str_pad($msg, $maxlen[$k], ' '), $color, false);
            });
            $this->nl();
        }, $data);
    }

    public function flushLine($msg, $color = null) {
        $msg = $msg . str_repeat(' ', $this->getcols() - strlen($msg)) . "\r";
        $this->message($msg, $color, false);
    }

    public function prompt($msg, $color = null) {
        $this->message($msg, $color, false);
        return trim(fgets(STDIN));
    }

    public function message($msg, $color = null, $newLine = true) {
        Logs::colorMessage($msg, $color, $newLine);
    }

    public function interactive($callable, $prompt = null) {
        $this->message('Toknot interactive shell, ( Ctrl+C exit)');
        $prompt = $prompt ? $prompt : '>>> ';
        do {
            $enter = $this->prompt($prompt, 'white');
            $callable($enter);
        } while (true);
    }

    public function fprompt($msg, $mismatch = '', $verifyEnter = null) {
        do {
            $enter = $this->prompt($msg);
            $ret = $this->switchOption($verifyEnter, $enter, $mismatch);
            if ($ret === -1) {
                continue;
            }
            return $enter;
        } while (true);
    }

    public function switchOption($callable, $enter, $mismatch) {
        if ($enter == $mismatch) {
            return -1;
        }
        if (is_callable($callable)) {
            $ret = $callable($enter);
            if ($ret === -1) {
                return -1;
            }
        }
        return $enter;
    }

    public function error($msg, $status = 255) {
        $this->message($msg, 'red');
        exit($status);
    }

}
