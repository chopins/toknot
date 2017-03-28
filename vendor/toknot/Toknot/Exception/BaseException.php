<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 * @since 4.0
 * @filesource
 * @package Toknot.Exception
 */

namespace Toknot\Exception;

use \Exception;
use Toknot\Boot\Logs;

/**
 * Toknot Statndrad Exception
 */
class BaseException extends Exception {

    protected $code = 0;
    protected $message = '';
    protected $isException = false;
    protected $exceptionMessage = null;
    private $fatalError = false;
    public $traceArr = array();
    public $exceptionInstance = null;
    protected $httpStatusCode = 0;
    protected $httpMessage = '';

    /**
     * construct StandardException
     * 
     * @param string $message
     * @param integer $code
     * @param string $file
     * @param integer $line
     * @param object|null $exceIns
     */
    public function __construct($message = '', $code = 0, $file = null, $line = null, $exceIns = null) {
        parent::__construct($message, $code, $exceIns);
        if ($this->exceptionMessage) {
            $this->message = $this->exceptionMessage;
        } else {
            $this->message = $message;
        }
        $this->httpStatusCode = 500;
        $this->httpMessage = 'Internal Server Error';
        $this->exceptionInstance = $exceIns;
        $this->file = empty($file) ? $this->getFile() : $file;
        $this->line = empty($line) ? $this->getLine() : $line;
        $this->getErrorType($code);
    }

    static public function errorReportHandler($argv) {
        if (strpos($argv[1], 'No such file or directory') > 0) {
            return new NoFileOrDirException($argv[1], $argv[0], $argv[2], $argv[3]);
        }
        return new BaseException($argv[1], $argv[0], $argv[2], $argv[3]);
    }

    public function getHttpCode() {
        return $this->httpStatusCode;
    }

    public function getHttpMessage() {
        return $this->httpMessage;
    }

    public function getErrorType($code) {
        switch ($code) {
            case E_USER_ERROR:
                $type = 'Fatal Error';
                $this->fatalError = true;
                break;
            case E_USER_WARNING:
            case E_WARNING:
                $type = 'Warning';
                $this->isException = true;
                break;
            case E_USER_NOTICE:
            case E_NOTICE:
            case @E_STRICT:
                $type = 'Notice';
                break;
            case @E_RECOVERABLE_ERROR:
                $type = 'Catchable';
                break;
            case E_COMPILE_ERROR:
                $type = 'PHP Compile Error';
                $this->fatalError = true;
                break;
            case E_ERROR:
                $type = 'PHP Fatal Error';
                $this->fatalError = true;
                break;
            case E_PARSE:
                $type = 'PHP Parse Error';
                $this->fatalError = true;
                break;
            case E_CORE_ERROR:
                $type = 'PHP Core Error';
                $this->fatalError = true;
                break;
            default:
                $type = get_called_class();
                $this->fatalError = true;
                break;
        }
        if ($this->exceptionInstance) {
            $type = get_class($this->exceptionInstance);
            $this->traceArr = $this->exceptionInstance->getTrace();
        }
        $this->message = "<b>$type : </b>" . $this->message;
    }

    public function getDebugTraceAsString() {
        $str = '';
        if (PHP_SAPI == 'cli') {
            $str .= str_repeat('=', 20) . PHP_EOL;
        }
        $str .= '<div>';
        $color = getenv('COLORTERM');
        $time = date('Y-m-d H:i:s T');
        $this->message = "[$time] $this->message";
        if (PHP_SAPI == 'cli' && !empty($color)) {
            $this->message = Logs::addCLIColor($this->message, Logs::COLOR_RED);
        }

        $str .= "<p>{$this->message}</p>" . PHP_EOL;
        $str .= "<div><b>Throw Exception in file {$this->file} line {$this->line}</b></div>" . PHP_EOL;
        if (PHP_SAPI == 'cli') {
            $hostname = getenv('HOSTNAME');
            $ip = gethostbyname($hostname);
            $str .= "CLI on Server IP: $ip($hostname)  User:" . getenv('USERNAME') . PHP_EOL;
        } else {
            $str .= 'Server IP:' . getenv('SERVER_ADDR') . PHP_EOL;
        }

        if (empty($this->traceArr)) {
            $traceArr = array_reverse($this->getTrace());
            $str .= $this->each($traceArr);
        } else {
            $str .= $this->each($this->traceArr);
        }
        $str .= '</div>';

        if (PHP_SAPI == 'cli') {
            $str .= str_repeat('=', 20) . PHP_EOL;
            $nohtml = strip_tags($str);
            $nohtml = html_entity_decode($nohtml);
            return $nohtml;
        } else {
            return $str;
        }
    }

    public function __toString() {
        try {
            return $this->getDebugTraceAsString();
        } catch (\Exception $e) {
            return '*** Exception has throw exception *** message is: ' . $e->getMessage();
        }
    }

    public function each($traceArr) {
        return Logs::formatTrace($traceArr);
    }

}
