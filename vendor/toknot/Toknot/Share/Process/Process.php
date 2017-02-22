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
 */
class Process {

    private $processPool = [];
    private $lock = null;

    const CMD_LOCK = 'LOCK';
    const CMD_UNLOCK = 'UNLOCK';
    const CMD_QUIT = 'QUIT';
    const CMD_SUCC = 'SUCCESS';
    const CMD_FAIL = 'FAIL';
    const CMD_ALREADY = 'ALREADY';

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

    /**
     * init process lock
     * 
     * @return type
     */
    public function createLock() {
        list($lock, $s) = $this->pipe();
        $this->lock = $lock;
        if (($cpid = $this->fork()) > 0) {
            fclose($s);
            return $cpid;
        }
        fclose($lock);
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
            }
        }
    }

    /**
     * get lock
     * 
     * @return boolean
     */
    public function lock() {
        stream_set_blocking($this->lock, 1);
        $pid = $this->getpid();
        $this->send($this->lock, self::CMD_LOCK . "|$pid");
        $ret = $this->read($this->lock);
        if ($ret == self::CMD_FAIL) {
            return false;
        }
        return true;
    }

    /**
     * release lock
     * 
     * @return boolean
     */
    public function unlock() {
        stream_set_blocking($this->lock, 1);
        $pid = $this->getpid();
        $this->send($this->lock, self::CMD_UNLOCK . "|$pid");
        $ret = $this->read($this->lock);
        if ($ret == self::CMD_FAIL) {
            return false;
        }
        return true;
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
        pcntl_wait($status);
        return 1;
    }

    private function processLoop($mport, $callable = null) {
        $status = 0;
        while (true) {
            $acp = stream_socket_accept($mport, 1);
            if ($acp) {
                list(,$pid) = explode('|',$this->read($acp));
                pcntl_waitpid($pid, $status);
                unset($this->processPool[$pid]);
                if ($callable) {
                    $pid = $callable();
                    if($pid == 0) {
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
        pcntl_wait($status);
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

}
