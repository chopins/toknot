<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception\PHPError;

class ErrorHander {

    private $type = '';

    protected function __construct($error) {
        $subClass = $this->errorLevel($error[0], $error[1]);
        throw new $subClass($error, $this);
    }

    public static function setErrorReportHander() {
        set_error_handler(array(__CLASS__, 'errorReportHandler'));
    }

    public static function errorReportHandler(...$argv) {
        return new static($argv);
    }

    protected static function specificWarning($message) {
        if (strpos($message, 'No such file or directory') > 0) {
            $this->type = NoFileOrDirException::class;
        }
    }
    
    protected function specificeNotice($message) {
        if(strpos($message, 'Use of undefined constant') !== false) {
            $this->type = UndefinedConstantException::class;
        }
    }

    protected static function errorLevel($code, $message) {
        switch ($code) {
            case E_USER_WARNING:
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_STRICT:
                $this->type = 'Warning';
                $this->specificWarning($message);
                break;
            case E_USER_NOTICE:
            case E_NOTICE:
                $this->type = 'Notice';
                $this->specificeNotice($message);
                break;
            case E_RECOVERABLE_ERROR:
            case E_USER_ERROR:
            case E_COMPILE_ERROR:
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
                $this->type = 'Error';
                break;
            default:
                $this->type = 'Warning';
        }
        return "PHP{$this->type}Exception";
    }

}
