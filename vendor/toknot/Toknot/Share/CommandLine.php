<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Toknot\Share;

use Toknot\Boot\Logs;

/**
 *  CommandLine
 *
 * @author chopin
 */
class CommandLine {

    public function prompt($msg) {
        $this->message($msg, null, false);
        return trim(fgets(STDIN));
    }

    public function message($msg, $color = null, $newLine = true) {
        Logs::colorMessage($msg, $color, $newLine);
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

}
