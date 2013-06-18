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
        if($this->isDir()) {
            throw new FileIOException($this->path . ' is not file');
        }
        return new SplFileObject($this->path, $mode);
    }
    public static function saveContent($file,$data) {
        $path = dirname($file);
        if(!is_dir($path)) {
            $r = mkdir($path, 0777, true);
            if(!$r) {
                throw new FileIOException("$file write fail or $path is not directory");
            } 
        }
        file_put_contents($file, $data);
        return new static($file);
    }

    public function rewind() {
        $dir = dir($this->path);
        while (false !== ($name = $dir->read())) {
            $this->interatorArray = new FileObject($this->path . '/' . $name);
        }
    }

}