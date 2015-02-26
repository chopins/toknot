<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Http;

use Toknot\Http\HttpResponse;
use Toknot\Process\Process;
use Toknot\Exception\TKException;
use Toknot\Exception\HeaderLocationException;
use Toknot\Di\TKFunction as TK;
use Toknot\Di\Log;

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
    private $applicationInstance = null;
    private $appliactionArgv = array();

    /**
     * IDLE Process counter
     *
     * @var int 
     */
    private $idleNum = 0;
    private $delayTime = 5;
    private $enableEvIo = true;
    private $masterPid = 0;
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
        $_SERVER['TK_SERVER'] = true;
        $_SERVER['COLORTERM'] = '';
        ini_set('xdebug.cli_color', 0);
        $this->process = new Process();
        $this->masterPid = posix_getpid();
        self::$selfInstance = $this;
        if (!class_exists('\EvLoop', false)) {
            $this->enableEvIo = false;
        }
    }

    /**
     * Register Applicaton instance to CGI server
     * 
     * @param Toknot\Control\Application $instance
     */
    public function registerApplicationInstance($instance) {
        $this->applicationInstance = $instance;
        $argc = func_num_args();
        if ($argc > 1) {
            $argvs = func_get_args();
            array_shift($argvs);
            $this->appliactionArgv = $argvs;
        }
        $this->documentRoot = $argvs[1] . '/WebRoot';
    }

    /**
     * Remove Application instance
     * 
     */
    public function removeApplicationInstance() {
        $this->applicationInstance = null;
        $this->appliactionArgv = array();
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

    private function CGIWorkLoopCallBack($read) {
        foreach ($read as $r) {
            if ($r == $this->socketFileDescriptor) {
                $lock = $this->process->processLock();
                if ($lock === false) {
                    if ($this->enableEvIo) {
                        // \Ev::stop(\Ev::BREAK_ALL);
                    }
                    return false;
                }
                return $this->CGIAccept();
            } else if ($r == $this->IPCSock) {
                $this->CGIWorkerProcessIPCRead();
            }
        }
        return true;
    }

    private function CGIWorkProcess($argv) {
        if (!$this->workCurrentUser)
            $this->process->setWorkUser($this->workUser, $this->workGroup);
        $this->IPCSock = $argv[1];
        fclose($argv[0]);
        unset($argv);
        if ($this->enableEvIo) {
            $evloop = new \EvLoop(\Ev::recommendedBackends());
        }
        while (true) {
            $read = array($this->socketFileDescriptor, $this->IPCSock);
            $write = array();
            $except = array();
            if ($this->enableEvIo) {
                $evloop->io($this->socketFileDescriptor, \Ev::READ, function() use($read) {
                            //$evloop->invokePending();
                            $this->CGIWorkLoopCallBack($read);
                            //$w->stop();
                            //$evloop->stop(\Ev::BREAK_ALL);
                            return true;
                        });
                $evloop->run();
                //gc_collect_cycles();
            } else {
                $chgNum = stream_select($read, $write, $except, 0, 200000);
                if ($chgNum > 0) {
                    $this->CGIWorkLoopCallBack($read);
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
        if(DEVELOPMENT) {
            Log::message($local);
        }
        $this->socketFileDescriptor = stream_socket_server($local, $this->socketErrno, $this->socketErrstr);
        stream_set_blocking($this->socketFileDescriptor, 0);
    }

    private function CGIAccept() {
        $clientAddress = 'unknown';
        try {
            $this->requestBacklog[] = @stream_socket_accept($this->socketFileDescriptor, 5, $clientAddress);
        } catch (TKException $e) {
            echo strip_tags($e);
            return false;
        }
        $_SERVER['REMOTE_ADDR'] = $clientAddress;
        putenv("REMOTE_ADDR={$clientAddress}");
        $this->process->processUnLock();
        $this->CGIWorkerProcessIPCWrite(self::WORKER_READ);

        foreach ($this->requestBacklog as $i => $conn) {
            $this->returnServerStatus(200);
            if (!feof($conn)) {
                $this->getRequestHeader($conn);
                $this->getRequestBody($conn);
            }

            $this->CGIWorkerProcessIPCWrite(self::WORKER_WRIER);
            if ($this->requestStaticFile && $this->requestStaticFileState) {
                $body = file_get_contents($this->requestStaticFile);
            } else {
                $body = $this->callApplication();
                $this->userHeaders = TK\headers_list();
            }
            $this->responseBodyLen = strlen($body);
            $_SERVER['HEADERS_LIST'] = array();
            $header = $this->getResponseHeader();
            $header .= $body;
            $this->requestStaticFileType = false;
            $this->requestStaticFile = false;
            $this->requestStaticFileState = false;
            fwrite($conn, $header);
            fclose($conn);
            $this->CGIWorkerProcessIPCWrite(self::WORKER_IDLE);
            unset($this->requestBacklog[$i]);
        }
        return true;
    }

    private function callApplication() {
        $_SERVER['TK_SERVER_WEB'] = true;
        ob_start();
        try {
            $app = $this->applicationInstance->newInstance();
            call_user_func_array(array($app, 'run'), $this->appliactionArgv);
            unset($app);
        } catch (HeaderLocationException $e) {
            return '';
        } catch (TKException $e) {
            return $e;
        } catch (\Exception $e) {
            return $e;
        }
        $body = ob_get_clean();
        $_SERVER['TK_SERVER_WEB'] = false;
        return $body;
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
        $this->workProcessPool = array();
        $this->workProcessSockList = array();
        foreach ($workPidList as $pid => $pinfo) {
            $this->workProcessPool[$pid] = self::WORKER_IDLE;
            $this->workProcessSockList[$pid] = $pinfo[0][0];
            if (is_resource($pinfo[0][1])) {
                fclose($pinfo[0][1]);
            }
        }
        $this->currentProcessNum = count($this->workProcessPool);
    }

    private function CGIMasterProcessLoopCallBack($read) {
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
                        $this->idleNum++;
                        break;
                    case self::WORKER_ACCPT:
                        $this->idleNum--;
                        break;
                    default :
                        break;
                }
                $this->workProcessPool[$workerStatus[0]] = $workerStatus[1];
                $this->getWorkerList();
                if ($this->idleNum <= 0 && $this->currentProcessNum + 1 <= $this->maxWorkNum) {
                    $this->process->createChildProcess(1, array($this, 'CGIWorkProcessCallbackProxy'));
                    $this->getWorkerList();
                    $this->idleNum++;
                }
            }
            if ($this->masterExit) {
                $this->delayTime++;
                $this->CGIServerExit();
                continue;
            }
        }
        return true;
    }

    private function CGIMasterProcess() {
        $this->delayTime = 0;
        $this->getWorkerList();
        $this->idleNum = count($this->workProcessPool);
        if ($this->enableEvIo) {
            $evloop = new \EvLoop(\Ev::recommendedBackends());
        }
        while (true) {
            $write = $except = array();
            $this->getWorkerList();
            $read = $this->workProcessSockList;
            if ($this->enableEvIo) {
                foreach ($this->workProcessSockList as $sock) {
                    $evloop->io($sock, \Ev::READ, function ($w) use($evloop, $read) {
                                $this->CGIMasterProcessLoopCallBack($read);
                                //$w->stop();
                                //$evloop->stop(\Ev::BREAK_ALL);
                                return true;
                            });
                }
                $evloop->run();
                //gc_collect_cycles();
            } else {
                $chgNum = stream_select($read, $write, $except, 0, 2000000);
                if ($chgNum > 0) {
                    $this->CGIMasterProcessLoopCallBack($read);
                }
            }
        }
        $this->getWorkerList();
        foreach ($this->workProcessPool as $pid => $info) {
            pcntl_waitpid($pid, $status);
            fclose($info[0][1]);
        }
    }

    private function CGIServerExit() {
        foreach ($this->workProcessPool as $pid => $pinfo) {
            $this->process->IPCWrite($pinfo['socket'], self::CMD_QUIT);
        }
        pcntl_sigtimedwait(SIGCHLD, $siginfo, 1);
        if ($this->delayTime > 5) {
            $this->process->registerChlidProcessSignalHandler();
            foreach ($this->workProcessPool as $pid => $pinfo) {
                posix_kill($pid, SIGKILL);
                fclose($pinfo['socket']);
            }
        }
        if ($this->currentProcessNum <= 0 ||
                count($this->process->getChildProcessList()) <= 0) {
            fclose($this->socketFileDescriptor);
            // exit(0);
        }
    }

    public function __destruct() {
        if (posix_getpid() == $this->masterPid) {
            $this->CGIServerExit();
        }
    }

}
