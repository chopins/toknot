<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

use Toknot\Boot\Logs;
use Toknot\Exception\BaseException;
use Toknot\Boot\Tookit;
use Toknot\Exception\ContinueException;

/**
 *  CommandLine
 *
 * @author chopin
 */
class CommandLine {

    use Tookit;

    private $progMsgStart = 0;
    private static $autoHistory = false;
    private static $readline = null;

    public function __construct($historyFile = null) {
        $this->checkReadline();
        if (self::$readline) {
            readline_write_history($historyFile);
        }
    }

    public function autoHistory() {
        self::$autoHistory = true;
    }

    public function checkReadline() {
        if (self::$readline !== null) {
            return self::$readline;
        }
        self::$readline = extension_loaded('readline');
    }

    public function getReadlineStatus() {
        return self::$readline;
    }

    /**
     * get terminal number of columns
     * 
     * @param int $defaultCols
     * @return int
     */
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
     * print progress
     * 
     * @param int $percent      current percent
     * @param string $message   progress message
     * @param string $speed     progress speed
     * @param string $color     message color
     * @throws BaseException
     */
    public function progress($percent, $message = '', $speed = '', $color = null) {
        if (!is_numeric($percent)) {
            throw new BaseException('speed must is numeric');
        }

        ($percent > 100) && ($percent = 100);
        $cols = $this->getcols();
        $allMsgLen = self::strlen($message);
        $msglen = $allMsgLen;
        $space = $cols - $allMsgLen;
        $speedlen = self::strlen($speed);

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
                $message = self::substr($message, $this->progMsgStart, $msglen);
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

    /**
     * print line wrap
     */
    public function nl() {
        echo PHP_EOL;
    }

    public function newline() {
        if (self::$readline) {
            readline_on_new_line();
        } else {
            $this->nl();
        }
    }

    /**
     * print message on same line
     * 
     * @param string $msg       print message
     * @param string $color     message color
     */
    public function flushLine($msg, $color = null) {
        $msg = $msg . str_repeat(' ', $this->getcols() - strlen($msg)) . "\r";
        $this->message($msg, $color, false);
    }

    public function flush() {
        if (self::$readline) {
            readline_redisplay();
        }
    }

    /**
     * print prompt message and get command line input
     * 
     * @param string $msg       command line prompt message
     * @param string $color     message color
     * @return string           input string
     */
    public function readline($msg, $color = null) {
        if (self::$readline) {
            $line = readline($msg);
        } else {
            $this->message($msg, $color, false);
            $line = trim(fgets(STDIN));
        }
        if (self::$autoHistory) {
            $this->addHistory($line);
        }
        return $line;
    }

    /**
     * print a message
     * 
     * @param string $msg
     * @param int $color
     * @param boolean $newLine
     */
    public function message($msg, $color = null, $newLine = true) {
        Logs::colorMessage($msg, $color, $newLine);
    }

    /**
     * exec interactive shell
     * 
     * @param callable $callable    callable after input
     * @param string $prompt        shell prompt message
     */
    public function interactive($callable, $prompt = null) {
        $this->message('Toknot interactive shell, ( Ctrl+C exit)');
        $prompt = self::coal($prompt, '>>>');
        do {
            $enter = $this->readline($prompt, 'green');
            if (!self::$autoHistory) {
                $this->addHistory($enter);
            }
            $callable($enter);
        } while (true);
    }

    /**
     * print prompt message and get command line input until input value to meet the conditions
     * 
     * @param string $msg               show prompt message
     * @param string $mismatch          if enter mismatch continue loop
     * @param callable $verifyEnter     check enter value function
     * @return string                   input value
     */
    public function freadline($msg, $mismatch = '', $verifyEnter = null, $color = '') {
        do {
            $enter = $this->readline($msg, $color);
            if ($enter == $mismatch) {
                continue;
            }
            if (!is_callable($verifyEnter)) {
                return $enter;
            }
            try {
                $verifyEnter($enter, $this);
            } catch (ContinueException $e) {
                continue;
            }
            return $enter;
        } while (true);
    }

    public function cont() {
        throw new ContinueException;
    }

    /**
     * print error message and exit script
     * 
     * @param string $msg
     * @param int $status
     */
    public function error($msg, $status = 255) {
        $this->message($msg, 'red');
        exit($status);
    }

    public function autoCompletion($func) {
        if (self::$readline) {
            readline_completion_function($func);
        }
    }

    public function addHistory($history) {
        if (self::$readline) {
            readline_add_history($history);
        }
    }

}
