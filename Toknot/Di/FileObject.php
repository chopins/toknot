<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use Toknot\Di\Object;
use Toknot\Exception\FileIOException;
use \SplFileObject;

class FileObject extends Object {

    private $path;
    private $fp;

    public function __construct($path) {
        $this->path = $path;
        if (!file_exists($this->path)) {
            throw new FileIOException("$path not exists");
        }
    }

    public function isDir() {
        return is_dir($this->path);
    }

    public function parentDir() {
        return new FileObject(dirname($this->path));
    }

    public function mkSubDir($path) {
        if ($this->path == null) {
            throw new FileIOException("must be exists path");
        }
        $name = explode(DIRECTORY_SEPARATOR, $path, 2);
        $path = $this->path . '/' . $name[0];
        if (!mkdir($path)) {
            throw new FileIOException('Directory create Fail');
        }
        $this->interatorArray[$name] = new FileObject($path);
        if (isset($name[1])) {
            $this->interatorArray[$name]->mkSubDir($name[1]);
        }
    }

    public static function mkdir($path) {
        if (mkdir($path)) {
            return new static($path);
        }
        return false;
    }

    public function __toString() {
        return $this->path;
    }

    public function __get($name) {
        if (isset($this->$name)) {
            return $this->$name;
        }
        if ($this->isDir) {
            if (isset($this->interatorArray[$name])) {
                return $this->interatorArray[$name];
            }
            if (file_exists($this->path . '/' . $name)) {
                $this->interatorArray[$name] = new FileObject($this->path . '/' . $name);
                return $this->interatorArray[$name];
            }
        }
    }

    public function open($mode) {
        if ($this->isDir()) {
            throw new FileIOException($this->path . ' is not file');
        }
        return new SplFileObject($this->path, $mode);
    }

    public static function saveContent($file, $data, $flag = 0) {
        $path = dirname($file);
        if (!is_dir($path)) {
            $r = mkdir($path, 0777, true);
            if (!$r) {
                throw new FileIOException("$file write fail or $path is not directory");
            }
        }
        file_put_contents($file, $data, $flag);
        return new static($file);
    }

    public function rewind() {
        $dir = dir($this->path);
        while (false !== ($name = $dir->read())) {
            $this->interatorArray = new FileObject($this->path . DIRECTORY_SEPARATOR . $name);
        }
    }

    /**
     * Get real path which is relative to $appPath of $path,
     * 
     * <code>
     * //in unix-like os:
     * $appPath = '/yourhome/path/appRoot';
     * $path = 'mySubPath/file';
     * $realPath = FileObject::getRealPath($appPath,$path);
     * echo $realPath; //will print /yourhome/path/appRoot/mySubPath/file
     * 
     * $path = '/mySubPath/file';
     * $realPath = FileObject::getRealPath($appPath,$path);
     * echo $realPath; //will print /mySubPath/file
     * 
     * //in windows e.g1:
     * $appPath = 'D:\path\appRoot';
     * $appPath = 'mySubPath/file';
     * $realPath = FileObject::getRealPath($appPath,$path);
     * echo $realPath; //will print D:\path\appRoot\mySubPath\file
     * 
     * //in window e.g2:
     * $appPath = 'D:\path\appRoot';
     * $appPath = '/mySubPath/file';
     * $realPath = FileObject::getRealPath($appPath,$path);
     * echo $realPath; //will print D:\mySubPath\file
     * </code>
     * 
     * @param string $appPath The path be relative, like current work path
     * @param string $path 
     * @return string
     */
    public static function getRealPath($appPath, $path) {
        $first = substr($path, 0, 1);
        $second = substr($path, 1, 1);
        $appPath = strtr($appPath, '/', DIRECTORY_SEPARATOR);
        if ($first == '/' || (preg_match('/[a-zA-Z]/', $first) && $second == ':')) {
            if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' && $first == '/') {
                $winRoot = substr($appPath, 0, 2);
                $path = $winRoot . $path;
            }
            $path = strtr($path, '/', DIRECTORY_SEPARATOR);
            return $path;
        } else {
            $path = strtr($path, '/', DIRECTORY_SEPARATOR);
            return $appPath . DIRECTORY_SEPARATOR . $path;
        }
    }

}