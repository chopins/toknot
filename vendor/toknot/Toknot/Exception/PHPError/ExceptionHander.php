<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception\PHPError;

use Error;
use Toknot\Exception\BaseException;

class ExceptionHander {

    protected function __construct($exception) {
        if ($exception instanceof Error) {
            if ($this->specificeError($exception)) {
                $this->printErrorException($exception);
            }
        } else if ($exception instanceof BaseException) {
            echo $exception;
        } else {
            $this->printException($exception);
        }
    }

    protected function specificeError($exception) {
        $message = $exception->getMessage();
        if (strpos($message, 'Undefined class constant') !== false) {
            return false;
        }
        return true;
    }

    public static function setExceptionHandler() {
        set_exception_handler(array(__CLASS__, 'uncaughtExceptionHandler'));
    }

    public static function UncaughtErrorHandler($exception) {
        return new static($exception);
    }

    protected function printErrorException($exception) {
        echo new ErrorException($exception->getMessage(), $exception->getCode(), $exception);
    }

}
