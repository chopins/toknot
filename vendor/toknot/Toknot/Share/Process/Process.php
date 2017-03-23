<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\Process;

use Toknot\Boot\Object;
use Toknot\Boot\Tookit;
use Toknot\Exception\BaseException;

/**
 * Process
 *
 * @todo Test the class
 */
class Process extends Object {

    private $processPool = [];
    private $lock = null;
    private $alock = null;

    const CMD_LOCK = 'LOCK';
    const CMD_UNLOCK = 'UNLOCK';
    const CMD_QUIT = 'QUIT';
    const CMD_SUCC = 'SUCCESS';
    const CMD_FAIL = 'FAIL';
    const CMD_ALREADY = 'ALREADY';
    const ANY_LOCK_SOCK = 'udp://127.0.0.1:';
    const QUEUE_ADD = 'QADD';
    const QUEUE_GET = 'QGET';

    public function __construct() {
        if (!extension_loaded('pcntl')) {
            throw new BaseException('pcntl extension un-loaded');
        }
        if (!extension_loaded('posix')) {
            throw new BaseException('posix extension un-loaded');
        }
    }

    public static function loadProcessExtension() {
        dl('pcntl.' . PHP_SHLIB_SUFFIX);
        dl('posix.' . PHP_SHLIB_SUFFIX);
    }

    public function setProcessTitle($title) {
        if(PHP_MIN_VERSION < 5) {
            throw new BaseException('setProcessTitle() is avaiabled when only php version greater then 5.5');
        }
        return cli_set_process_title($title);
    }

    public function pipe() {
        return stream_socket_pair(STREAM_PF_INET, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    }

    public function quit($pipe) {
        $pid = $this->getpid();
        $this->send($pid, self::CMD_QUIT . "|$pid");
        usleep(1000);
        fclose($pipe);
    }

    public function send($sock, $data) {
        return fwrite($sock, $data . PHP_EOL);
    }

    public function read($sock) {
        return trim(fgets($sock));
    }

    public function taskQueue($local) {
        $this->demon();

        list($add, $addServer) = $this->pipe();
        list($get, $getServer) = $this->pipe();

        $mpid = $this->queueManager($addServer, $getServer);
        $taskpid = $this->taskManager($get);

        $addpid = $this->recvTask($add, $local);

        while (true) {
            $pid = $this->wait(0, $status, 1);
            switch ($pid) {
                case $mpid:
                    $mpid = $this->queueManager($addServer, $getServer);
                    break;
                case $taskpid:
                    $taskpid = $this->taskManager($get);
                    break;
                case $addpid:
                    $addpid = $this->recvTask($add, $local);
            }
            usleep(200000);
        }
        exit;
    }

    protected function recvTask($add, $local) {
        $addpid = $this->fork();
        if ($addpid > 0) {
            return $addpid;
        }
        $errno = $errstr = 0;
        $recvSock = stream_socket_server($local, $errno, $errstr, STREAM_SERVER_BIND);
        while (($acp = stream_socket_accept($recvSock, 0))) {
            $message = $this->read($acp);
            $this->send($add, self::QUEUE_ADD . $message);
            $res = $this->read($add);
            $this->send($acp, $res);
        }
        exit;
    }

    public function addTask($socket, $function, $args = []) {
        $desc = serialize(['function' => $function, 'args' => $args]);
        $sock = stream_socket_client($socket);
        $this->send($sock, $desc);
        if ($this->read($sock) == self::CMD_SUCC) {
            return true;
        }
        return false;
    }

    protected function taskManager($get) {
        $taskpid = $this->fork();
        if ($taskpid > 0) {
            return $taskpid;
        }
        while (true) {
            $r = [];
            $w = [$get];
            $except = null;
            $change = stream_select($r, $w, $except, 0, 200000);
            if (false === $change) {
                throw new BaseException('task queue select fail');
            }
            if ($change > 0) {
                $this->execTask($w);
            }
        }
        exit;
    }

    protected function execTask($w) {
        $execpid = $this->fork();
        if ($execpid > 0) {
            return $this->wait($execpid);
        }
        foreach ($w as $rsock) {
            $this->send($rsock, self::QUEUE_GET);
            $line = $this->read($rsock);
            $this->send($rsock, self::QUEUE_GET . self::CMD_SUCC);
            $taskInfo = unserialize($line);
            $callPid = $this->fork();
            if ($callPid === 0) {
                self::callFunc($taskInfo['function'], $taskInfo['args']);
                exit;
            } else {
                $this->wait($callPid);
            }
        }
        exit;
    }

    protected function queueManager($addServer, $getServer) {
        $mpid = $this->fork();
        if ($mpid > 0) {
            return $mpid;
        }
        $taskQueue = new \SplQueue();
        while (true) {
            $r = [$addServer, $getServer];
            $w = [];
            $except = null;

            $change = stream_select($r, $w, $except, 0, 200000);
            if (false === $change) {
                throw new BaseException('task queue select fail');
            }
            if ($change > 0) {
                $this->queueRequest($r, $taskQueue);
            }
        }
        exit;
    }

    protected function queueRequest($r, $queue) {
        foreach ($r as $rsock) {
            $line = $this->read($rsock);
            $flag = substr($line, 0, 3);
            if ($flag == self::QUEUE_GET) {
                $this->readGet($rsock, $queue);
            } elseif ($flag == self::QUEUE_ADD) {
                $message = substr($line, 3);
                $queue->enqueue($message);
                $this->send($rsock, self::CMD_SUCC);
            }
        }
    }

    protected function readGet($wsock, $queue) {
        $task = $queue->dequeue();
        $cnt = 5;
        do {
            $this->send($wsock, $task);
            $res = $this->read($wsock);
            if (substr($res, 3, 3) == self::CMD_SUCC) {
                return true;
            }
            $cnt--;
        } while ($cnt > 0);
    }

    /**
     * init process lock for any process
     * 
     * @param int $port
     * @throws BaseException
     */
    public function anyLock($port = 99088) {
        $this->demon();
        $errno = 0;
        $errstr = '';
        $lock = stream_socket_server(self::ANY_LOCK_SOCK . $port, $errno, $errstr, STREAM_SERVER_BIND);
        if (!$this->lock) {
            throw new BaseException($errstr, $errno);
        }

        while (true) {
            if (($cpid = $this->fork()) > 0) {
                $this->wait($cpid);
                continue;
            } else {
                break;
            }
        }
        $this->lockAccept($lock);
    }

    public function alock($port = 99088) {
        $errno = 0;
        $errstr = '';
        $this->alock = stream_socket_client(self::ANY_LOCK_SOCK . $port, $errno, $errstr, 1);
        stream_set_blocking($this->alock, 1);
        if (!$this->lock) {
            return false;
        }
        return $this->sendLockMessage($this->lock, self::CMD_LOCK);
    }

    public function aunlock() {
        if (!is_resource($this->alock)) {
            throw new BaseException('the lock handle not exists');
        }
        return $this->sendLockMessage($this->alock, self::CMD_UNLOCK);
    }

    protected function lockAccept($s, $option = null) {
        $lockpid = 0;
        while (($acp = stream_socket_accept($s, 0))) {
            list($cmd, $pid) = explode('|', $this->read($acp));
            if ($cmd == self::CMD_LOCK) {
                if (!$lockpid) {
                    $lockpid = $pid;
                    $this->send($acp, self::CMD_SUCC);
                } elseif ($pid != $lockpid) {
                    $this->send($acp, self::CMD_FAIL);
                } else {
                    $this->send($acp, self::CMD_ALREADY);
                }
            } elseif ($cmd == self::CMD_UNLOCK) {
                if ($pid == $lockpid) {
                    $this->send($acp, self::CMD_SUCC);
                } else {
                    $this->send($acp, self::CMD_FAIL);
                }
            } elseif ($option !== null) {
                $option($cmd);
            }
        }
    }

    /**
     * init process lock, only in parent and child process
     * 
     * @return type
     */
    public function bloodLock() {
        list($lock, $s) = $this->pipe();
        $this->lock = $lock;
        if (($cpid = $this->fork()) > 0) {
            fclose($s);
            return $cpid;
        }
        fclose($lock);
        $this->lockAccept($s);
    }

    protected function sendLockMessage($lock, $type) {
        $pid = $this->getpid();
        $this->send($lock, $type . "|$pid");
        $ret = $this->read($lock);
        if ($ret == self::CMD_FAIL) {
            return false;
        }
        return true;
    }

    /**
     * get lock
     * 
     * @return boolean
     */
    public function lock() {
        stream_set_blocking($this->lock, 1);
        return $this->sendLockMessage($this->lock, self::CMD_LOCK);
    }

    /**
     * release lock
     * 
     * @return boolean
     */
    public function unlock() {
        stream_set_blocking($this->lock, 1);
        return $this->sendLockMessage($this->lock, self::CMD_UNLOCK);
    }

    /**
     * tell main process child quit
     * 
     * @param resource $cport
     */
    protected function childClean($cport) {
        Tookit::attachShutdownFunction(function() use($cport) {
            stream_set_blocking($cport, 1);
            $this->quit($cport);
        });
    }

    /**
     * init specil number porcess
     * 
     * @param int $number
     * @param resource $mport
     * @param resource $cport
     * @return boolean|int
     */
    protected function initMutiProcess($number, $mport, $cport) {
        for ($i = 0; $i < $number; $i++) {
            $pid = $this->fork();
            if ($pid > 0) {
                $this->processPool[$pid] = 1;
                continue;
            } else {
                fclose($mport);
                $this->childClean($cport);
                return 0;
            }
        }
        return true;
    }

    /**
     * multi-process run until task exit
     * 
     * @param int $number
     * @return int
     */
    public function multiProcess($number) {
        list($mport, $cport) = $this->pipe();
        if (!$this->initMutiProcess($number, $mport, $cport)) {
            return 0;
        }
        fclose($cport);
        $this->processLoop($mport);
        $this->wait();
        return 1;
    }

    private function processLoop($mport, $callable = null) {
        $status = 0;
        while (true) {
            $acp = stream_socket_accept($mport, 1);
            if ($acp) {
                list(, $pid) = explode('|', $this->read($acp));
                $this->wait($pid);
                unset($this->processPool[$pid]);
                if ($callable) {
                    $pid = $callable();
                    if ($pid == 0) {
                        return;
                    }
                }
            }
            usleep(10000);
        }
    }

    /**
     * keep specil number process is runing
     * 
     * @param int $number
     * @return int
     */
    public function processPool($number) {
        list($mport, $cport) = $this->pipe();
        if (!$this->initMutiProcess($number, $mport, $cport)) {
            return 0;
        }
        $this->processLoop($mport, function() use($cport) {
            $npid = $this->fork();
            if ($npid > 0) {
                $this->processPool[$npid] = 1;
            } else {
                $this->childClean($cport);
                return 0;
            }
            return $npid;
        });
        $this->wait();
        return 1;
    }

    public function getpid() {
        return getmypid();
    }

    public function fork() {
        $pid = pcntl_fork();
        if ($pid < 0) {
            throw new BaseException('process fork fail');
        } elseif ($pid == 0) {
            return 0;
        }
        return $pid;
    }

    public function demon() {
        if ($this->fork() > 0) {
            exit;
        }
        if ($this->fork() > 0) {
            exit;
        }

        chdir('/');
        umask('0');
        posix_setsid();
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        if ($this->fork() > 0) {
            exit;
        }
    }

    public function kill($pid, $sig) {
        return posix_kill($pid, $sig);
    }

    public function wait($pid = 0, &$status = 0, $unblock = 0) {
        if ($unblock) {
            $unblock = WNOHANG;
        }
        if ($pid > 0) {
            return pcntl_waitpid($pid, $status, WUNTRACED | $unblock);
        }
        return pcntl_wait($status, WUNTRACED | $unblock);
    }

}
