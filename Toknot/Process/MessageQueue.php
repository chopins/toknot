<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Process;

use Toknot\Process\Process;
use Toknot\Process\Exception\PipException;
use Toknot\Exception\FileIOException;

class MessageQueue {

    private $process = null;
    private $database = null;
    private $socket = 'tcp://127.0.0.1';
    private $port = '2966';
    private $user = 'nobody';
    private $group = 'nobody';
    private $bakTime = 60;
    private $bakFile = null;
    private $workDirectory = './';

    const CMD_PUSH = 'push';
    const CMD_EXEC = 'exec';
    const CMD_SUCC = 'success';
    const CMD_COUNT = 'count';
    const CMD_BAKUP = 'bakup';
    const CMD_RESTORE = 'restore';

    public function __construct() {
        $this->process = new Process();
    }

    public function setBakup($file) {
        if (!is_writable(dirname($file))) {
            throw new FileIOException();
        }
        $this->bakFile = $file;
    }

    public function setPort($port) {
        $this->port = $port;
    }

    public function setWorkUser($username, $group) {
        $this->user = $username;
        $this->group = $group;
    }

    public function setWorkDir($directory) {
        $this->workDirectory = $directory;
    }

    public function runServer() {
        $this->process->daemon();
        $this->process->setWorkUser($this->user, $this->group);
        $this->process->setWorkDirectory($this->workDirectory);
        $this->process->createChildProcess(1, array($this, 'messageService'));
        if ($this->bakFile) {
            $this->process->createChildProcess(1, array($this, 'dataBakup'));
        }
        pcntl_wait($status);
    }

    public function messageService() {
        $this->process = null;
        $this->database = new \SplQueue();
        $sss = stream_socket_server("{$this->socket}:{$this->port}", $errno, $errstr, STREAM_SERVER_LISTEN);
        if (!$sss) {
            throw new PipException();
        }
        while ($conn = stream_socket_accept($sss)) {
            $data = $this->process->IPCRead($conn);
            switch ($data['cmd']) {
                case self::CMD_PUSH :
                    $this->database->push($data['value']);
                    $this->process->IPCWrite($conn, self::CMD_SUCC);
                    break;
                case self::CMD_EXEC:
                    $data = $this->database->shift();
                    $this->process->IPCWrite($conn, $data);
                    break;
                case self::CMD_COUNT:
                    $count = $this->database->count();
                    $this->process->IPCWrite($conn, $count);
                    break;
                case self::CMD_BAKUP:
                    $this->process->createChildProcess(1, array($this,'bakupOpreate'));
                    $this->process->IPCWrite($conn, self::CMD_SUCC);
                    break;
                case self::CMD_RESTORE:
                    $this->database->unserialize($data['value']);
                    $this->process->IPCWrite($conn, self::CMD_SUCC);
                default :
                    $this->process->IPCWrite($conn, self::CMD_SUCC);
                    break;
            }
        }
    }

    public function push($value) {
        $res = $this->messageClient(self::CMD_PUSH, $value);
        return $res == self::CMD_SUCC;
    }

    public function exec() {
        return $this->messageClient(self::CMD_EXEC);
    }

    public function count() {
        return $this->messageClient(self::CMD_COUNT);
    }

    private function messageClient($cmd, $value = null) {
        $ssc = stream_socket_client("$this->socket:$this->port");
        if (!$ssc) {
            throw new PipException();
        }
        $data = array('cmd' => $cmd, 'value' => $value);
        $this->process->IPCWrite($ssc, $data);
        return $this->process->IPCRead($ssc);
    }

    public function restore($date) {
        if (!file_exists("{$this->bakFile}.{$date}")) {
            throw new FileIOException();
        }
        $data = file_get_contents("{$this->bakFile}.{$date}");
        $this->messageClient(self::CMD_RESTORE, $data);
    }

    private function bakupOpreate() {
        $data = $this->database->serialize();
        $date = date('Y-m-d.H:i:s');
        file_put_contents("{$this->bakFile}.{$date}", $data);
    }

    public function dataBakup() {
        while (true) {
            sleep($this->bakTime);
            $this->messageClient(self::CMD_BAKUP);
        }
    }

}

?>
