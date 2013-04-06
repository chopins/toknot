<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Http;

use Toknot\Http\HttpResponse;
use Toknot\Process\Process;

final class FastCGIServer extends HttpResponse {

    private $process = null;
    private $port = '9900';
    private $localsock = 'tcp://127.0.0.1';
    private $workUser = 'nobody';
    private $workGroup = 'nobody';
    private $workCurrentUser = false;
    private $socketFileDescriptor = null;
    private $workProcessPool = array();
    private $requestBackend = array();
    private $socketErrno = 0;
    private $socketErrstr = '';
    private $startWorkNum = 2;
    private $maxWorkNum = 5;
    private $isDaemon = false;
    private $workDirectory = './';
    private $IPCSock = null;
    private $masterExit = false;
    private $currentProcessNum = 2;
    private $applicationRouter = array();

    const CGI_VER = '1.1';
    const WORKER_ACCPT = 'accrpt';
    const WORKER_IDLE = 'idle';
    const WORKER_READ = 'read';
    const WORKER_WRIER = 'write';
    const CMD_QUIT = 1;
    const CMD_TERMINATE = 2;
    const CMD_REOPEN = 3;
    const CMD_OPEN_CHANNEL = 5;

    public function __construct() {
        $this->process = new Process();
    }

    public function registerApplicationRouter($callback) {
        $argv = func_get_args();
        array_shift($argv);
        array_push($this->applicationRouter, array('func' => $callback,
            'argv' => $argv));
        return key($this->applicationRouter);
    }

    public function removeApplicationRouter($idx) {
        if (isset($this->applicationRouter[$idx])) {
            unset($this->applicationRouter[$idx]);
        }
    }

    public function setListenPort($port) {
        $this->port = $port;
    }

    public function setLocalsock($local) {
        $this->localsock = $local;
    }

    public function setWorkUser($user, $group) {
        $this->workUser = $user;
        $this->workGroup = $group;
    }

    public function setWorkOnCurrentUser() {
        $this->workCurrentUser = true;
    }

    public function setWorkDirectory($directory) {
        $this->workDirectory = $directory;
    }

    public function setDaemon() {
        $this->isDaemon = true;
    }

    public function getSocketErr() {
        return array($this->socketErrno, $this->socketErrstr);
    }

    public function setStartWorkNum($num) {
        $this->startWorkNum = $num;
    }

    public function setMaxWorkNum($num) {
        $this->maxWorkNum = $num;
    }

    private function bindLocalListenPort() {
        $this->socketFileDescriptor = stream_socket_server($this->localsock, $this->socketErrno, $this->socketErrstr, STREAM_SERVER_LISTEN);
        stream_set_blocking($this->socketFileDescriptor, 0);
    }

    public function startServer() {
        if ($this->isDaemon)
            $this->process->daemon();
        $this->process->setWorkDirectory($this->workDirectory);
        $this->IPCSock = $this->process->createSocketPair();
        $this->bindLocalListenPort();
        $this->process->createChildProcess($this->startWorkNum, array($this, 'CGIWorkProcess'));
        $this->currentProcessNum = $this->startWorkNum;
        $this->CGIMasterProcess();
    }

    public function CGIWorkProcess() {
        if (!$this->workCurrentUser)
            $this->process->setWorkUser($this->workUser, $this->workGroup);
        fclose($this->IPCSock[0]);
        while (true) {
            $read = array($this->socketFileDescriptor, $this->IPCSock[1]);
            $write = array($this->socketFileDescriptor);
            $except = array($this->IPCSock[1]);
            $chgNum = stream_select($read, $write, $except, 0, 200000);
            if ($chgNum > 0) {
                foreach ($read as $r) {
                    if ($r == $this->socketFileDescriptor) {
                        flock($this->socketFileDescriptor, LOCK_SH);
                        $this->CIGIWorkerProcessIPCWrite(self::WORKER_ACCPT);
                        $this->requestBackend[] = stream_socket_accept($this->socketFileDescriptor);
                        flock($this->socketFileDescriptor, LOCK_UN);
                        $this->CGIAccept();
                    } else if ($r == $this->IPCSock[1]) {
                        $this->CGIIWorkerProcessIPCRead();
                    }
                }
            }
        }
    }

    private function CGIAccept() {
        $this->CIGIWorkerProcessIPCWrite(self::WORKER_READ);
        foreach ($this->requestBackend as $conn) {
            stream_set_blocking($conn, 1);
            while (!feof($conn)) {
                echo fread($conn, 1024);
                /** Do something* */
            }
            //$this->callApplicationRouter();
        }
    }

    private function callApplicationRouter() {
        foreach ($this->applicationRouter as $router) {
            call_user_func_array($router[0], $router[1]);
        }
    }

    private function CGIWorkerExit() {
        foreach ($this->requestBackend as $conn) {
            if (is_resource($conn)) {
                fclose($conn);
                $this->CGIWorkerProcessIPCWrite(self::CMD_QUIT);
                fclose($this->IPCSock[1]);
            }
        }
    }

    private function CGIWorkerProcessIPCWrite($status) {
        $pid = posix_getpid();
        $this->process->IPCWrite($this->IPCSock[1], array($pid, $status));
    }

    private function CGIWorkerProcessIPCRead() {
        $cmd = $this->process->IPCRead($this->IPCSock[1]);
        switch ($cmd) {
            case self::CMD_QUIT:
                $this->CIGWorkerExit();
                break;
            case self::CMD_TERMINATE:
                $this->CGIWorkerExit();
                break;
            default :
                break;
        }
    }

    private function getWorkerList() {
        $workPidList = $this->process->getChildProcessList();
        foreach ($workPidList as $pid => $pinfo) {
            if (!isset($this->workProcessPool[$pid])) {
                $this->workProcessPool[$pid] = self::WORKER_IDLE;
            }
        }
    }

    private function CGIMasterProcess() {
        fclose($this->IPCSock[1]);
        $read = array($this->IPCSock[0]);
        $write = array($this->IPCSock[0]);
        $except = null;
        $this->getWorkerList();
        $delayTime = 0;
        $idleNum = count($this->workProcessPool);
        while (true) {
            stream_select($read, $write, $except, 1);
            $workerStatus = $this->process->IPCRead($this->IPCSock[0]);
            if (is_array($workerStatus)) {
                switch ($workerStatus[1]) {
                    case self::CMD_QUIT:
                    case self::CMD_TERMINATE:
                        unset($this->workProcessPool[$workerStatus[0]]);
                        $this->currentProcessNum++;
                        break;
                    case self::WORKER_IDLE:
                        $idleNum++;
                        break;
                    default :
                        $idleNum--;
                        if ($idleNum <= 0 && $this->currentProcessNum + 1 <= $this->maxWorkNum) {
                            $this->process->createChildProcess(1, array($this, 'CGIWorkProcess'));
                            $this->getWorkerList();
                            $idleNum++;
                        }
                        break;
                }
                $this->workProcessPool[$workerStatus[0]] = $workerStatus[1];
            }
            if ($this->masterExit) {
                $delayTime++;
                $this->CGIServerExit();
                continue;
            }
        }
        pcntl_wait($status);
    }

    private function CGIServerExit() {
        $this->process->IPCWrite($this->IPCSock[0], self::CMD_QUIT);
        pcntl_sigtimedwait(SIGCHLD, $siginfo, 1);
        if ($delayTime > 5) {
            $this->process->registerChlidProcessSignalHandler();
            foreach ($this->workProcessPool as $pid => $status) {
                posix_kill($pid, SIGKILL);
            }
        }
        if ($this->currentProcessNum <= 0 ||
                count($this->process->getChildProcessList()) <= 0) {
            fclose($this->IPCSock[0]);
            fclose($this->socketFileDescriptor);
            exit(0);
        }
    }

}
