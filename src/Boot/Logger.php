<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Boot;

use Toknot\Boot\Kernel;

class Logger {

    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    protected static $logFileHandle = [];
    protected $saveFile = '';

    public function __construct() {
        $this->saveFile = Kernel::instance()->logDir;
    }

    public function emergency($message, array $context = array()) {
        $this->log(self::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array()) {
        $this->log(self::ALERT, $message, $context);
    }

    public function critical($message, array $context = array()) {
        $this->log(self::CRITICAL, $message, $context);
    }

    public function error($message, array $context = array()) {
        $this->log(self::ERROR, $message, $context);
    }

    public function warning($message, array $context = array()) {
        $this->log(self::WARNING, $message, $context);
    }

    public function notice($message, array $context = array()) {
        $this->log(self::NOTICE, $message, $context);
    }

    public function info($message, array $context = array()) {
        $this->log(self::INFO, $message, $context);
    }

    public function debug($message, array $context = array()) {
        $this->log(self::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = array()) {
        $date = date('Y-m-d H:i:s e');
        $messageData = [$level];
        $messageData[] = PHP_SAPI;
        $messageData[] = $date;
        $messageData[] = gethostname();
        $messageData[] = Kernel::localIp();
        $messageData[] = Kernel::requestIp();
        $messageData[] = $message;
        $messageData[] = str_replace(PHP_EOL, Kernel::getEOLToken(), var_export($context, true));
        $logstr = join(' - ', $messageData);
        $file = $this->saveFile . DIRECTORY_SEPARATOR . $level . Kernel::DOT . date('Ymd');
        $this->save($file, $logstr);
    }

    public function save($file, $string) {
        if (empty(self::$logFileHandle[$file])) {
            self::$logFileHandle[$file] = fopen($file, 'ab');
        }
        flock(self::$logFileHandle[$file], LOCK_EX);
        fwrite(self::$logFileHandle[$file], $string);
        flock(self::$logFileHandle[$file], LOCK_UN);
    }

    public function __destruct() {
        foreach (self::$logFileHandle as $fp) {
            @fclose($fp);
        }
    }

}
