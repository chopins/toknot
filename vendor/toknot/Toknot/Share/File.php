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

    /**
     * find string in between $start string and $end string 
     * 
     * @param string $start
     * @param string $end
     * @return string
     */
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
      
            if (strpos($search, $find) === false) {
                $find = $char;
            }
         
            if ($find == $end) {
                break;
            }
            if ($start == $find) {
                $find = '';
                $search = $end;
            }
        }

        return substr($res, 0, strlen($end) * -1);
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

    /**
     * find string offset
     * 
     * @param string $search
     * @return int
     */
    public function strpos($search) {
        $find = '';
        $sl = strlen($search);

        while (!($this->eof())) {
            $char = $this->fread(1);
            $find .= $char;

            if (strpos($search, $find) === false) {
                $find = $char;
            }
            if ($search == $find) {
                return $this->ftell() - $sl;
            }
        }
        return false;
    }

}
