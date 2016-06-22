<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

use Toknot\Boot\Object;
use Toknot\Exception\FileIOException;
use \SplFileObject;
use \finfo;

class FileObject extends Object {

    private $path;
    private $dir;
    private $key = 0;
    private static $PHP_OS = null;
    private $fp;
    private $isDir = false;
    private $isFile = false;

    public function __init(string $path) {
        $this->path = $path;
        if (!file_exists($this->path)) {
            throw new FileIOException("$path not exists");
        }
        $sfo = new SplFileObject($path);
        $this->addExtendObject($sfo);
    }

    public function rm($res = false) {
        if ($this->isDir()) {
            if ($res) {
                $subDir = scandir($this->path);
                foreach ($subDir as $subDirName) {
                    if ($subDirName == '.' || $subDirName == '..') {
                        continue;
                    }
                    $filePath = "{$this->path}/$subDirName";
                    if (is_dir($filePath)) {
                        $f = new FileObject($filePath);
                        $f->rm(true);
                    } else {
                        unlink($filePath);
                    }
                }
            }
            return rmdir($this->path);
        }
        unlink($this->path);
    }

    /**
     * Get current path of parent directory
     * 
     * @return \Toknot\Boot\FileObject
     */
    public function parentDir() {
        return new FileObject(dirname($this->path));
    }

    /**
     * Create a child directory
     * 
     * @param string $path
     * @throws FileIOException
     */
    public function mkSubDir($path) {
        if ($this->path == null) {
            throw new FileIOException("must be exists path");
        }
        $name = explode(DIRECTORY_SEPARATOR, $path, 2);
        $path = $this->path . DIRECTORY_SEPARATOR . $name[0];
        if (!mkdir($path)) {
            throw new FileIOException('Directory create Fail');
        }
        $this->interatorArray[$name] = new FileObject($path);
        if (isset($name[1])) {
            $this->interatorArray[$name]->mkSubDir($name[1]);
        }
    }

    /**
     * Create a directory
     * 
     * @param string $path
     * @return \Toknot\Boot\FileObject|boolean
     */
    public static function mkdir($path, $mode = 0777, $res = false) {
        if (mkdir($path, $mode, $res)) {
            return new static($path);
        }
        return false;
    }

    public function __toString() {
        return $this->path;
    }

    public function getPropertie($name) {
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
        parent::getPropertie($name);
    }
    
    /**
     * The paramaters see PHP {@see file_put_contents()}, the difference is that the method 
     * will do creation of nested directories
     * 
     * @param string $file file name
     * @param string $data  data
     * @param integer $flag 
     * @return \Toknot\Boot\FileObject
     * @throws FileIOException
     */
    public static function saveContent($file, $data, $flag = 0) {
        $path = dirname($file);
        if (!is_dir($path)) {
            $r = mkdir($path, 0777, true);
            if (!$r) {
                throw new FileIOException("$file write fail or $path is not directory");
            }
        }
        $res = file_put_contents($file, $data, $flag);
        if ($res === false) {
            return false;
        }
        return new static($file);
    }

    /**
     *     implement method of PHP {@see \Iterator}
     */
    public function rewind() {
        $this->dir = dir($this->path);
        $this->dir->rewind();
        $this->key = 0;
//        while (false !== ($name = $dir->read())) {
//            $this->interatorArray = new FileObject($this->path . DIRECTORY_SEPARATOR . $name);
//        }
    }

    public function current() {
        $this->key++;
        return $this->dir->read();
    }

    public function key() {
        return $this->key;
    }

    public function next() {
        //$this->dir->read();
    }

    public function valid() {
        return true;
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
        if (self::isWin()) {
            if (strpos($path, '/') === 0 || strpos($path, '\\') === 0) {
                $winRoot = substr($appPath, 0, 2);
                return strtr($winRoot . $path, '/', DIRECTORY_SEPARATOR);
            } elseif (preg_match('/^[a-zA-Z]:/', $path)) {
                return $path;
            }
        } else {
            if (strpos($path, '/') === 0) {
                return $path;
            }
        }
        return $appPath . DIRECTORY_SEPARATOR . $path;
    }

    public function count() {
        return count(scandir($this->path));
    }

    /**
     * return the file of absolute path 
     * 
     * @param string $file
     * @return string
     * @access public
     * @static
     */
    public static function absPath($file) {
        return self::getRealPath(getcwd(), $file);
    }

    /**
     * check the file whether exists and case insensitive
     * 
     * @param string $file
     * @return boolean
     * @access public
     * @static
     */
    public static function fileExistCase($file) {
        $file = self::absPath($file);
        if (self::isWin()) {
            return file_exists($file);
        }
        if (file_exists($file)) {
            return $file;
        }
        $dir = dirname($file);
        $filename = basename($file);
        $caseDir = self::isDirCase($dir);
        if ($caseDir) {
            $dirList = scandir($caseDir);
            foreach ($dirList as $sub) {
                if (strcasecmp($filename, $sub) === 0) {
                    return $caseDir . DIRECTORY_SEPARATOR . $sub;
                }
            }
        }
        return false;
    }

    public static function isDirCase($dir) {
        $dir = self::absPath($dir);
        if (self::isWin()) {
            if (is_dir($dir)) {
                return $dir;
            }
            return false;
        }
        if (is_dir($dir)) {
            return $dir;
        }
        $parentDir = dirname($dir);
        $dirname = basename($dir);
        $casePath = self::isDirCase($parentDir);
        if ($casePath) {
            $dirList = scandir($casePath);
            foreach ($dirList as $sub) {
                if (strcasecmp($sub, $dirname) === 0) {
                    $p = $casePath . DIRECTORY_SEPARATOR . $sub;
                    if (is_dir($p)) {
                        return $p;
                    }
                }
            }
        }
        return false;
    }

    public static function isWin() {
        if (self::$PHP_OS === null) {
            self::$PHP_OS = (strpos(strtoupper(PHP_OS), 'WIN') === 0);
        }
        return false;
    }

    public static function fileMime($file) {
        if (function_exists('finfo_open')) {
            $fo = new finfo(FILEINFO_MIME);
            $type = $fo->file($file);
            return $type;
        } else if (file_exists('mime_content_type')) {
            return @mime_content_type($file);
        } else {
            $file = escapeshellcmd($file);
            $re = popen("file -ib $file", 'r');
            return fread($re, 1024);
        }
    }

}
