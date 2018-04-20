<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception;

use Exception;

class BaseException extends Exception {

    protected $traceLine = [];

    const NEW_STACK = true;

    public function __construct($message = '', $code = 0, $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    protected function setFile($file) {
        $this->file = $file;
    }

    protected function setLine($line) {
        $this->line = $line;
    }

    public function getTraceLine() {
        $this->previousTrace($this);
        $this->traceHeaderInfo($this);
        $this->each($this);
        return $this->traceLine;
    }

    protected function previousTrace($next) {
        $previous = $next->getPrevious();
        if ($previous) {
            if (is_subclass_of($previous, __CLASS__)) {
                $this->traceLine = $previous->getTraceLine();
            } else {
                $this->previousTrace($previous);
                $this->traceHeaderInfo($previous);
                $this->each($previous);
            }
        }
    }

    protected function traceHeaderInfo($exception) {
        $this->traceLine[] = self::NEW_STACK;
        $this->traceLine[] = date('Y-m-d H:i:s T');
        $this->traceLine[] = $exception->getMessage();
        $this->traceLine[] = "Throw Exception in file {$exception->getFile()} line {$exception->getLine()}";

        if (PHP_SAPI == 'cli') {
            $hostname = getenv('HOSTNAME');
            $ip = gethostbyname($hostname);
            $this->traceLine[] = "CLI on Server IP: $ip($hostname)  User:" . getenv('USERNAME');
        } else {
            $this->traceLine[] = 'Server IP:' . getenv('SERVER_ADDR') . PHP_EOL;
        }
    }

    protected function each($exception) {
        $traceArr = - array_reverse($exception->getTrace());
        foreach ($traceArr as $key => $value) {
            $function = Tookit::coalesce($value, 'function');
            $this->traceLine[] = "#{$key} ";
            $file = Tookit::coalesce($value, 'file');
            $this->traceLine[] = $file;
            $this->traceLine[] = '(' . Tookit::coalesce($value, 'line') . '):';
            $this->traceLine[] = Tookit::coalesce($value, 'class');
            $this->traceLine[] = Tookit::coalesce($value, 'type');

            if ($function == 'unknown') {
                $function = 'main';
            }
            $this->traceLine[] = empty($function) ? '' : "$function(";
            if (isset($value['args'])) {
                self::opeateArg($value['args']);
            }
            $this->traceLine[] = isset($value['function']) ? ")" : '';
        }
    }

    public static function opeateArg($args) {
        foreach ($args as $arg) {
            if (is_null($arg)) {
                continue;
            }

            if (is_scalar($arg)) {
                $pad = strlen($arg) > 20 ? '...' : '';
                $subarg = substr($arg, 0, 20);
                $this->traceLine[] = [$arg, $subarg, $pad];
            } elseif (is_array($arg)) {
                $cnt = count($arg);
                $argStr = print_r($arg, true);
                $this->traceLine[] = [$argStr, "Array($cnt)"];
            } elseif (is_object($arg)) {
                $cls = get_class($arg);
                $this->traceLine[] = ['Object', $cls];
            } elseif (is_resource($arg)) {
                $title = get_resource_type($arg);
                $this->traceLine[] = " Resource: $title";
            }
        }
    }

    public function __toString() {
        $this->getTraceLine();
        return print_r($this->traceLine);
    }

}
