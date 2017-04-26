<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

use Toknot\Share\Generator;

/**
 * File
 *
 */
class File extends \SplFileObject {

    private $writer = null;
    private $reader = null;
    private $readerLen = 1024;

    public function __construct($filename, $mode = 'r', $useInclude = false, $context = null) {
        $context ? parent::__construct($filename, $mode, $useInclude, $context) : parent::__construct($filename, $mode, $useInclude);

        if (PHP_MIN_VERSION >= 5) {
            $this->gwrite();
            $this->greader();
        }
    }

    private function gwrite() {
        $this->writer = Generator::sloop(true, array($this, 'fwrite'));
    }

    private function greader() {
        $this->reader = Generator::gloop(true, array($this, 'fread'), [$this->readerLen]);
    }

    public function getReader() {
        return $this->reader;
    }

    public function getWriter() {
        return $this->writer;
    }

    /**
     * find string in between $start string and $end string 
     * 
     * @param string $start
     * @param string $end
     * @return string
     */
    public function findRange($start, $end) {
        $find = $res = '';
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

            if ($find == $end && $search == $end) {
                break;
            }
            if ($start == $find) {
                $find = '';
                $search = $end;
            }
        }

        return substr($res, 0, strlen($end) * -1);
    }

    /**
     * move seek to a string
     * 
     * @param string $start
     * @return boolean
     */
    public function seekPos($start) {
        while (!($this->eof())) {
            $char = $this->fread(1);
            $find .= $char;
            if (strpos($start, $find) === false) {
                $find = $char;
            }
            if ($find == $start) {
                return $this->ftell();
            }
        }
        return false;
    }

    /**
     * find offset to end string from current seek
     * 
     * @param string $end
     * @return string
     */
    public function findNextRange($end) {
        $find = $res = '';
        while (!($this->eof())) {
            $char = $this->fread(1);
            $find .= $char;
            $res .= $char;
            if (strpos($end, $find) === false) {
                $find = $char;
            }
            if ($find == $end) {
                break;
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

    /**
     * yield write string
     * 
     * @param string $str
     */
    public function yfwrite($str) {
        $this->writer->send($str);
    }

    /**
     * yield read string
     * 
     * @param int $len
     * @return string
     */
    public function yfread($len) {
        $this->readerLen = $len;
        $res = $this->reader->current();
        $this->reader->next();
        return $res;
    }

}
