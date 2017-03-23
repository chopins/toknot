<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

/**
 * File
 *
 */
class File extends \SplFileObject {

    public function findRange($start, $end) {
        $find = '';
        $res = '';
        $search = $start;
        while (!($this->eof())) {
            $char = $this->fread(1);
            $find .= $char;
            if ($search == $end) {
                $res .= $char;
            }
            if (strpos($search, $find) !== false) {
                $find = '';
            }
            if ($find == $end) {
                break;
            }
            if ($start == $find) {
                $find = '';
                $search = $end;
            }
        }
        return $res;
    }

    public function substr($start, $len) {
        $this->fseek($start);
        $i = 0;
        $res = '';
        while (!($this->eof())) {
            if ($i >= $len) {
                break;
            }
            $i++;
            $res .= $this->fread(1);
        }
        return $res;
    }

    public function strpos($search) {
        $find = '';
        $sl = strlen($search);
        while (!($this->eof())) {
            $char = $this->fread(1);
            $find .= $char;
            if (strpos($search, $find) !== false) {
                $find = '';
            }
            if ($search == $find) {
                return $this->ftell() - $sl;
            }
        }
    }

}
