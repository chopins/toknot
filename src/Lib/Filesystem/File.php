<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Filesystem;

use Toknot\Boot\Kernel;
use SplFileObject;

class File extends SplFileObject {

    private $realpath = '';
    private $pathInfo = [];

    public function __construct($file) {
        parent::__construct($file);
        $this->realpath = $this->getRealPath();
        $this->pathInfo = pathinfo($this->realpath);
    }

    public function __destruct() {
        $this->realpath = null;
        $this->pathInfo = null;
    }

    public function getDir() {
        return $this->pathInfo['dirname'];
    }

    public function basename() {
        return $this->pathInfo['basename'];
    }

    public function ext() {
        return $this->pathInfo['extension'];
    }

    public function name() {
        return $this->pathInfo['filename'];
    }

    public function pathInfo() {
        return $this->pathInfo;
    }

    public function save($data) {
        return file_put_contents($this->realpath, $data);
    }

    public function getADate($format = 'Y-m-d H:i:s') {
        return date($format, $this->getATime());
    }

    public function getCDate($format = 'Y-m-d H:i:s') {
        return date($format, $this->getCTime());
    }

    public function getMDate($format = 'Y-m-d H:i:s') {
        return date($format, $this->getMTime());
    }

    public function humanSize($precision = 2, $si = false) {
        $size = $this->size();
        $base = $si ? 1000 : 1024;
        $unit = $si ? 'B' : 'iB';
        $arr = [5 => 'P', 4 => 'T', 3 => 'G', 2 => 'M', 1 => 'K', 0 => ''];
        foreach ($arr as $i => $u) {
            $suffix = " {$u}{$unit}";
            if ($i === 1) {
                return round($size / $base, $precision) . $suffix;
            } elseif ($size > pow($base, $i)) {
                return round($size / pow($base, $i), $precision) . $suffix;
            } else {
                return $size . $suffix;
            }
        }
    }

    public function verboseSize($si = false, $returnArr = false) {
        $size = $this->size();
        $base = $si ? 1000 : 1024;
        $unit = $si ? 'B' : 'iB';
        $arr = [5 => 'P', 4 => 'T', 3 => 'G', 2 => 'M', 1 => 'K', 0 => ''];
        $res = [];
        foreach ($arr as $i => $u) {
            if ($i === 1) {
                $res[] = floor($size / $base, 2);
                $size = $size % $base;
            } elseif ($size > pow($base, $i)) {
                $res[] = floor($size / pow($base, $i));
                $size = $size % pow($base, $i);
            } else {
                $res[] = $size;
            }
            $res[] = "{$u}{$unit}";
        }
        return $returnArr ? $res : join(' ', $res);
    }

    public function append($data) {
        return file_put_contents($this->realpath, $data, FILE_APPEND);
    }

    public function get() {
        return file_get_contents($this->realpath);
    }

    public function del() {
        if (unlink($this->realpath) === true) {
            return true;
        } else {
            return false;
        }
    }

    public function getJson() {
        return json_decode($this->get(), true);
    }

    public function copy($newFile) {
        return copy($this->realpath, $newFile);
    }

    public function move($newFile) {
        if (rename($this->realpath, $newFile) === true) {
            $this->__construct($newFile);
            return true;
        } else {
            return false;
        }
    }

    public function saveJson($array) {
        return $this->save(json_encode($array));
    }

    public static function randPathName($path, $prefix = '', $len = 6) {
        return $path . Kernel::PATH_SEP . self::randName($prefix, $len);
    }

    public static function randName($prefix = '', $len = 6) {
        $str = str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890');
        return $prefix . substr($str, 0, $len);
    }

    public static function timeName($path, $prefix) {
        return $path . Kernel::PATH_SEP . $prefix . '.' . time();
    }

}
