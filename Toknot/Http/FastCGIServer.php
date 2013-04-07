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

    /**
     * Process control object
     * 
     * @var object  
     */
    private $process = null;

    /**
     * CGI server listen local port
     * 
     * @var number 
     */
    private $port = '9900';

    /**
     * CGI server bind local socket address or unix scoket file
     * 
     * @var string
     */
    private $localsock = 'tcp://127.0.0.1';

    /**
     * CGI server work user
     * 
     * @var string 
     */
    private $workUser = 'nobody';

    /**
     * CGI server worker group
     * 
     * @var string
     */
    private $workGroup = 'nobody';

    /**
     * whether work on current user
     * 
     * @var boolean
     */
    private $workCurrentUser = false;

    /**
     * CGI server opened local socket
     * 
     * @var socket
     */
    private $socketFileDescriptor = null;

    /**
     * work process list
     * 
     * @var array 
     */
    private $workProcessPool = array();
    private $workProcessSockList = array();

    /**
     * connected list
     *
     * @var array 
     */
    private $requestBacklog = array();

    /**
     * @var integer
     */
    private $socketErrno = 0;

    /**
     *
     * @var string
     */
    private $socketErrstr = '';

    /**
     * @var number
     */
    private $startWorkNum = 2;

    /**
     * @var number
     */
    private $maxWorkNum = 5;

    /**
     * @var boolean
     */
    private $isDaemon = false;

    /**
     * @var directory
     */
    private $workDirectory = './';

    /**
     * a pair socket
     *
     * @var array 
     */
    private $IPCSock = null;

    /**
     * @var boolean 
     */
    private $masterExit = false;

    /**
     * @var number 
     */
    private $currentProcessNum = 2;

    /**
     * contain appliaction of callback function of list
     * 
     * @var array 
     */
    private $applicationRouter = array();
    static private $selfInstance = null;

    const CGI_VER = '1.1';
    const WORKER_ACCPT = 'accrpt';
    const WORKER_IDLE = 'idle';
    const WORKER_READ = 'read';
    const WORKER_WRIER = 'write';
    const CMD_QUIT = 1;
    const CMD_TERMINATE = 2;
    const CMD_REOPEN = 3;
    const CMD_OPEN_CHANNEL = 5;

    /**
     * Construct
     * 
     * @return void
     */
    public function __construct() {
        $this->process = new Process();
        self::$selfInstance = $this;
    }

    /**
     * Register Applicaton Router to CGI server
     * 
     * @param callable $callback
     * @return integer 
     */
    public function registerApplicationRouter($callback) {
        $argv = func_get_args();
        array_shift($argv);
        array_push($this->applicationRouter, array('func' => $callback,
            'argv' => $argv));
        return key($this->applicationRouter);
    }

    /**
     * Remove Application Router
     * 
     * @param integer $idx
     * @return void 
     */
    public function removeApplicationRouter($idx) {
        if (isset($this->applicationRouter[$idx])) {
            unset($this->applicationRouter[$idx]);
        }
    }

    /**
     * set CGI server listen port
     * 
     * @param number $port
     * @return void
     */
    public function setListenPort($port) {
        $this->port = $port;
    }

    /**
     * set local listen address
     * 
     * @param string $local
     * @return void
     */
    public function setLocalsock($local) {
        $this->localsock = $local;
    }

    /**
     * set CGI server work user info
     * 
     * @param string $user
     * @param string $group
     * @return void
     */
    public function setWorkUser($user, $group) {
        $this->workUser = $user;
        $this->workGroup = $group;
    }

    /**
     * set whether work on current user
     * 
     * @return void
     */
    public function setWorkOnCurrentUser() {
        $this->workCurrentUser = true;
    }

    /**
     * set work directory
     * 
     * @param directory $directory
     */
    public function setWorkDirectory($directory) {
        $this->workDirectory = $directory;
    }

    /**
     * set whether CGI server run on daemon mode
     * 
     * @return void 
     */
    public function setDaemon() {
        $this->isDaemon = true;
    }

    /**
     * get socket connect error number and message
     * 
     * @return array
     */
    public function getSocketErr() {
        return array($this->socketErrno, $this->socketErrstr);
    }

    /**
     * set CGI server work process number when start
     * 
     * @param integer $num
     * @return void 
     */
    public function setStartWorkNum($num) {
        $this->startWorkNum = $num;
    }

    /**
     * set CGI server of max work process number
     * 
     * @param integer $num
     * @return void 
     */
    public function setMaxWorkNum($num) {
        $this->maxWorkNum = $num;
    }

    /**
     * start run CGI server
     * 
     * @return void 
     */
    public function startServer() {
        if ($this->isDaemon)
            $this->process->daemon();
        $this->process->setWorkDirectory($this->workDirectory);
        $this->process->setCreateChildFrontCallback(array($this->process, 'createSocketPair'));
        $this->bindLocalListenPort();
        $this->process->enableProcessLock();
        $this->process->registerChlidProcessSignalHandler();
        $this->process->createChildProcess($this->startWorkNum, array('\Toknot\Http\FastCGIServer',
            'CGIWorkProcessCallbackProxy'));
        $this->currentProcessNum = $this->startWorkNum;
        $this->CGIMasterProcess();
    }

    static public function CGIWorkProcessCallbackProxy($argv) {
        if (self::$selfInstance instanceof FastCGIServer)
            self::$selfInstance->CGIWorkProcess($argv);
    }

    private function CGIWorkProcess($argv) {
        if (!$this->workCurrentUser)
            $this->process->setWorkUser($this->workUser, $this->workGroup);
        $this->IPCSock = $argv[1];
        fclose($argv[0]);
        unset($argv);
        while (true) {
            $read = array($this->socketFileDescriptor, $this->IPCSock);
            $write = array();
            $except = array();
            $chgNum = stream_select($read, $write, $except, 0, 200000);
            if ($chgNum > 0) {
                foreach ($read as $r) {
                    if ($r == $this->socketFileDescriptor) {
                        $lock = $this->process->processLock();
                        if ($lock === false) {
                            usleep (100000);
                            continue;
                        }
                        $this->CGIWorkerProcessIPCWrite(self::WORKER_ACCPT);
                        $this->requestBacklog[] = stream_socket_accept($r, 5);
                        $this->process->processUnLock();
                        $this->CGIAccept();
                    } else if ($r == $this->IPCSock) {
                        $this->CGIWorkerProcessIPCRead();
                    }
                }
            }
        }
    }

    private function bindLocalListenPort() {
        $sock = explode('://', $this->localsock);
        if (count($sock) == 2) {
            switch ($sock[0]) {
                case 'tcp':
                    $local = "{$this->localsock}:{$this->port}";
                    break;
                case 'unix':
                    $local = $this->localsock;
                    break;
                default :
                    throw new TransportsException();
            }
        } else {
            $local = "tcp://{$this->localsock}:{$this->port}";
        }

        $this->socketFileDescriptor = stream_socket_server($local, $this->socketErrno, $this->socketErrstr);
        stream_set_blocking($this->socketFileDescriptor, 0);
    }

    private function CGIAccept() {
        debugPrint('accept');
        $this->CGIWorkerProcessIPCWrite(self::WORKER_READ);
        foreach ($this->requestBacklog as $i=> $conn) {
            stream_set_blocking($conn, 0);
            $keepAliveTime = 5;
            $delay = 1;
            while (!feof($conn)) {
                echo fread($conn, 1024);
                sleep(1);
                $delay++;
                if($delay >- $keepAliveTime) {
                    break;
                }
            }
            $this->returnServerStatus(200);
            $header = $this->responseStatus;
            $header .= "Connection: close\r\n\r\n";
            debugPrint($header);
            fwrite($conn, $header);
            fclose($conn);
            unset($this->requestBacklog[$i]);
            //$this->callApplicationRouter();
        }
    }

    private function callApplicationRouter() {
        foreach ($this->applicationRouter as $router) {
            call_user_func_array($router[0], $router[1]);
        }
    }

    private function CGIWorkerExit() {
        foreach ($this->requestBacklog as $conn) {
            if (is_resource($conn)) {
                fclose($conn);
                $this->CGIWorkerProcessIPCWrite(self::CMD_QUIT);
                fclose($this->IPCSock);
            }
        }
    }

    private function CGIWorkerProcessIPCWrite($status) {
        $pid = posix_getpid();
        $this->process->IPCWrite($this->IPCSock, array($pid, $status));
    }

    private function CGIWorkerProcessIPCRead() {
        $cmd = $this->process->IPCRead($this->IPCSock);
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
                $this->workProcessSockList[$pid] = $pinfo[0][0];
                fclose($pinfo[0][1]);
            }
        }
    }

    private function CGIMasterProcess() {
        $this->getWorkerList();
        $delayTime = 0;
        $idleNum = count($this->workProcessPool);
        while (true) {
            $read = $write = $except = $this->workProcessSockList;
            $chgNum = stream_select($read, $write, $except, 0, 200000);
            if ($chgNum > 0) {
                foreach ($read as $r) {
                    $workerStatus = $this->process->IPCRead($r);
                    if (is_array($workerStatus)) {
                        switch ($workerStatus[1]) {
                            case self::CMD_QUIT:
                            case self::CMD_TERMINATE:
                                unset($this->workProcessPool[$workerStatus[0]]);
                                fclose($this->workProcessPool[$workerStatus[0]]['socket']);
                                $this->currentProcessNum--;
                                break;
                            case self::WORKER_IDLE:
                                $idleNum++;
                                break;
                            case self::WORKER_ACCPT:
                                $idleNum--;
                                break;
                            default :
                                break;
                        }
                        $this->workProcessPool[$workerStatus[0]] = $workerStatus[1];
                        if ($idleNum <= 0 && $this->currentProcessNum + 1 <= $this->maxWorkNum) {
                            $this->process->createChildProcess(1, array($this, 'CGIWorkProcessCallbackProxy'));
                            $this->getWorkerList();
                            $idleNum++;
                        }
                    }
                    if ($this->masterExit) {
                        $delayTime++;
                        $this->CGIServerExit();
                        continue;
                    }
                }
            }
        }
        pcntl_wait($status);
    }

    private function CGIServerExit() {
        foreach ($this->workProcessPool as $pid => $pinfo) {
            $this->process->IPCWrite($pinfo['socket'], self::CMD_QUIT);
        }
        pcntl_sigtimedwait(SIGCHLD, $siginfo, 1);
        if ($delayTime > 5) {
            $this->process->registerChlidProcessSignalHandler();
            foreach ($this->workProcessPool as $pid => $pinfo) {
                posix_kill($pid, SIGKILL);
                fclose($pinfo['socket']);
            }
        }
        if ($this->currentProcessNum <= 0 ||
                count($this->process->getChildProcessList()) <= 0) {
            fclose($this->socketFileDescriptor);
            exit(0);
        }
    }

}
