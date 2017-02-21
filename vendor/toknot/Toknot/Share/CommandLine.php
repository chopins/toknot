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

    private $progMsgStart = 0;
    const CONT = -1;

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

    /**
     * 
     * @param int $percent
     * @param string $message
     * @param string $speed
     * @param string $color
     * @throws BaseException
     */
    public function progress($percent, $message = '', $speed = '', $color = null) {
        if (!is_numeric($percent)) {
            throw new BaseException('speed must is numeric');
        }
 
        ($percent > 100) && ($percent = 100);
        $cols = $this->getcols();
        $allMsgLen = Tookit::strlen($message);
        $msglen = $allMsgLen;
        $space = $cols - $allMsgLen;
        $speedlen = Tookit::strlen($speed);

        if ($space >= 102 + $speedlen) {
            $flag = $percent;
            $flagcnt = 100;
        } elseif ($space >= 52 + $speedlen) {
            $flag = floor($percent / 2);
            $flagcnt = 50;
        } elseif ($space >= 22 + $speedlen) {
            $flag = floor($percent / 5);
            $flagcnt = 20;
        } else {
            if ($space < 12 + $speedlen) {
                $msglen = $allMsgLen - ($space > 0 ? 12 + $speedlen - $space : abs($space) + 12 + $speedlen);
                $message = Tookit::substr($message, $this->progMsgStart, $msglen);
                $this->progMsgStart = ($this->progMsgStart + $msglen < $allMsgLen) ?
                        ($this->progMsgStart + 1) : 0;
            }
            $flag = floor($percent / 10);
            $flagcnt = 10;
        }

        $prog = '[' . str_repeat('=', $flag) . str_repeat(' ', $flagcnt - $flag) . ']' . $speed;
        $padSpace = str_repeat(' ', $cols - $msglen - strlen($prog));
        $msg = $message . $padSpace . $prog . "\r";
        $this->message($msg, $color, false);
    }

    public function nl() {
        echo PHP_EOL;
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

    /**
     * 
     * @param string $msg           show prompt message
     * @param string $mismatch      if enter mismatch continue loop
     * @param type $verifyEnter     check enter value function
     * @return type
     */
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
