<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Filesystem;

use Toknot\Boot\Kernel;
use Toknot\Boot\TKObject;
use Exception;
use Error;

class Dir extends TKObject {

    private $path = '';
    private $realpath = '';

    public function __construct($path) {
        $this->path = $path;
        $this->realpath = realpath($path);
    }

    public function getPath() {
        return $this->path;
    }

    public function name() {
        return basename($this->realpath);
    }

    public function getRealPath() {
        return $this->realpath;
    }

    public function rm($r = true) {
        $this->walk('unlink', $r ? 'rmdir' : null);
        return rmdir($this->realpath);
    }

    public function perms() {
        return fileperms($this->realpath);
    }

    public function glob($pattern, $flags) {
        return glob($this->realpath . $pattern, $flags);
    }

    public function create($mode, $r = true) {
        return mkdir($this->realpath, $mode, $r);
    }

    public function walk(callable $fileCallable, $dirCallable = false) {
        $d = \dir($this->path);
        while (false !== ($name = $d->read())) {
            if ($name == Kernel::DOT || $name == '..') {
                continue;
            }

            $realpath = $this->path . Kernel::PATH_SEP . $name;
            if ($dirCallable && is_dir($realpath)) {
                $n = new static($realpath);
                $n->walk($fileCallable, $dirCallable);
                is_callable($dirCallable) && $dirCallable($realpath, $name);
            } elseif (is_file($realpath)) {
                $fileCallable($realpath, $name);
            }
        }
        return true;
    }

    public function move($newPath) {
        if (rename($this->path, $newPath) === true) {
            return true;
        } else {
            return false;
        }
    }

    public function copy($newPath, $override = true) {
        $newRealPath = realpath($newPath);
        if (!$override && ($exist = file_exists($newRealPath))) {
            Kernel::runtimeException("$newRealPath is file that not directory", E_USER_WARNING);
        }
        if ($exist) {
            $tmpPath = $newRealPath . File::randName();
            if (rename($newRealPath, $tmpPath) === false) {
                Kernel::runtimeException("move $newRealPath error", E_USER_WARNING);
            }
        }
        $perms = fileperms($this->realpath);
        if (mkdir($newRealPath, $perms, true) === false) {
            return false;
        }
        try {
            $pathLen = strlen($this->path);
            $this->walk(function($realpath) use($pathLen, $newRealPath) {
                $dest = $newRealPath . substr($realpath, $pathLen);
                copy($realpath, $dest);
            }, function($realpath)use($pathLen, $newRealPath) {
                $dest = $newRealPath . substr($realpath, $pathLen);
                mkdir($dest);
            });
        } catch (Exception $e) {
            $this->rollback($newRealPath, $tmpPath, $e);
        } catch (Error $e) {
            $this->rollback($newRealPath, $tmpPath, $e);
        }
        $this->commit($exist, $tmpPath);
        return true;
    }

    protected function rollback($newRealPath, $tmpPath, $e) {
        $newDir = new Dir($newRealPath);
        $newDir->rm();
        rename($tmpPath, $newRealPath);
        Kernel::runtimeException($e, E_USER_WARNING);
    }

    protected function commit($exist, $tmpPath) {
        if ($exist && is_dir($tmpPath)) {
            $tmpDir = new Dir($tmpPath);
            $tmpDir->rm();
        } elseif ($exist) {
            unlink($tmpPath);
        }
    }

}
