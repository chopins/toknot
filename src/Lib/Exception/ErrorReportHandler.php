<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Exception;

use Toknot\Lib\Exception\ErrorReportException;
use Toknot\Boot\Kernel;
use Toknot\Boot\Controller;
use Toknot\Lib\Exception\NoSuchFileOrDirException;
use Toknot\Lib\Exception\PermissionDeniedException;
use Toknot\Lib\Exception\UndefinedConstantException;
use Toknot\Lib\Exception\UndefinedIndexException;
use Toknot\Lib\Exception\UndefinedVariableException;
use Toknot\Lib\Exception\SQLQueryErrorException;
use Toknot\Lib\Exception\RuntimeException;

class ErrorReportHandler {

    public $code = 0;
    public $message = '';
    public $file = '';
    public $line = '';

    public function __construct($errorArg) {
        $this->code = $errorArg[0];
        $this->message = $errorArg[1];
        $this->file = $errorArg[2];
        $this->line = $errorArg[3];
    }

    public function throwException() {
        if ($this->levelLogger()) {
            return true;
        }
        if ($this->checkMessage('No such file or directory')) {
            $this->throwExceptionInstance(NoSuchFileOrDirException::class);
        } elseif ($this->checkMessage('Permission denied')) {
            $this->throwExceptionInstance(PermissionDeniedException::class);
        } elseif ($this->checkMessage('Undefined index')) {
            return $this->whetherHideViewNotice(UndefinedIndexException::class);
        } elseif ($this->checkMessage('Use of undefined constant')) {
            return $this->whetherHideConstanceNotice();
        } elseif ($this->checkMessage('Undefined variable')) {
            return $this->whetherHideViewNotice(UndefinedVariableException::class);
        } elseif ($this->checkMessage('SQLSTATE[')) {
            return $this->throwExceptionInstance(SQLQueryErrorException::class);
        } elseif ($this->checkMessage('RuntimeException')) {
            throw new RuntimeException($this->message, $this->code);
        }

        $this->throwExceptionInstance(ErrorReportException::class);
    }

    protected function throwExceptionInstance($exceptionClass) {
        throw new $exceptionClass($this->message, $this->code, $this->file, $this->line);
    }

    public function checkMessage($message) {
        return strpos($this->message, $message) !== false;
    }

    public function releaseStatus() {
        return Kernel::getRelaseStatus();
    }

    protected function whetherHideViewNotice($exceptionClass) {
        $isView = strpos($this->file, Controller::$viewCacheDir) === 0;
        if (Controller::$hideViewError && $isView) {
            return true;
        } elseif ($isView) {
            return false;
        }
        $this->throwExceptionInstance($exceptionClass);
    }

    public function whetherHideConstanceNotice() {
        if (Kernel::$enableTokenString) {
            return true;
        }
        $this->throwExceptionInstance(UndefinedConstantException::class);
    }

    public function checkAllSaveLogger() {
        return in_array($this->releaseStatus(), [Kernel::R_RELEASE, Kernel::R_RC]);
    }

    public function checkExceptErrorSaveLogger() {
        return in_array($this->releaseStatus(), [Kernel::R_ALPHA, Kernel::R_BETA]) &&
                !in_array($this->code, [E_ERROR, E_COMPILE_ERROR, E_CORE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR]);
    }

    public function levelLogger($e = '') {
        if ($this->checkAllSaveLogger()) {
            $this->saveLogger();
            return true;
        } elseif ($this->checkExceptErrorSaveLogger()) {
            $this->saveLogger();
            return true;
        } elseif($e instanceof \Exception) {
            return false;
        }
        return false;
    }

    public function saveLogger() {
        if (in_array($this->code, [E_ERROR, E_COMPILE_ERROR, E_CORE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_PARSE])) {
            $func = 'error';
        } elseif (in_array($this->code, [E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING])) {
            $func = 'warning';
        } else {
            $func = 'notice';
        }
        Kernel::instance()->logger->$func($this->message, [$this->file, $this->line]);
    }

}
