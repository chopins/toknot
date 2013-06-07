<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Process;

use Toknot\Exception\StandardException;
use Toknot\Exception\DependExtensionException;
use Toknot\Process\Exception\ProcessException;
use Toknot\Process\Exception\PipException;
use Toknot\Exception\FileIOException;

final class Process {

    const NOT_CALLABLE = 1;
    const CALLFUNC_ERR = 2;
    const SUC_EXIT = 0;

    private $pipMaxDataSize = 1024;
    private $processPool = array();
    private $childProcessExitStatusLog = array();
    private $maxLogNum = 50;
    private $createChildFrontCallback = null;
    private $createChildFrontCallbackParam = null;
    private $mutex = null;
    private $mutexId = 0;
    private $useFileLock = false;
    private $lockFileHanlde = null;
    private $enableProcessMutex = false;
    /**
     * process number in pool
     * 
     * @var int
     */
    private $processKeyInPool = 0;

    public function __construct() {
        $this->checkEnvironment();
    }

    /**
     *
     * @param int $size
     */
    public function setPipWindow(int $size) {
        $this->pipMaxDataSize = $size;
    }

    /**
     *
     * @return int
     */
    public function getPipWindow() {
        return $this->pipMaxDataSize;
    }

    public function getProcessInPoolKey() {
        return $this->processKeyInPool;
    }

    public function getChildProcessExitStatus() {
        return $this->childProcessExitStatus;
    }

    private function checkEnvironment() {
        if (!function_exists('pcntl_fork')) {
            try {
                dl('pcntl.'.PHP_SHLIB_SUFFIX);
            } catch (DependExtensionException $e) {
                echo $e;
            }
        }
        if (!function_exists('posix_setuid')) {
            try {
                dl('posix.'.PHP_SHLIB_SUFFIX);
            } catch (DependExtensionException $e) {
                echo $e;
            }
        }
    }

    public function setProcessTitle($title) {
        if (!function_exists('setproctitle')) {
            try {
                dl('proctitle.'.PHP_SHLIB_SUFFIX);
            } catch (StandardException $e) {
                echo $e;
            }
        }
        setproctitle($title);
    }
    
    /**
     * whether enable use process of metex lock
     * 
     * @return int if return 1 use file lock, otherwise use shmop
     */
    public function enableProcessLock() {
        $this->enableProcessMutex = true;
        if (!function_exists('sem_acquire')) { 
            try {
                dl('sysvsem.'.PHP_SHLIB_SUFFIX);
            } catch (StandardException $e) {
                $this->useFileLock = true;
            }
        }
        if($this->useFileLock == false) {
            if(!function_exists('shmop_open')) {
                try {
                    dl('shmop.'.PHP_SHLIB_SUFFIX);
                } catch (StandardException $e) {
                    $this->useFileLock = true;
                }
            }
        }
        if ($this->useFileLock) {
            $this->lockFileHanlde = tmpfile();
            return 1;
        } else {
            $size = strlen(count($this->processPool)) + 1;
            $key = ftok(__FILE__, 't');
            $this->mutexId = sem_get($key, 1);
            $this->mutex = shmop_open($key, 'c', 0644, $size);
            shmop_write($this->mutex,0,0);
            return 2;
        }
    }

    public function processLock() {
        if (!$this->enableProcessMutex)
            return false;
        if ($this->useFileLock) {
            return flock($this->lockFileHanlde, LOCK_EX|LOCK_NB);
        }
        sem_acquire($this->mutexId);
        $locker = shmop_read($this->mutex, 0, shmop_size($this->mutex));
        if($locker[0] != 0 && $locker != $this->processKeyInPool) {
            sem_release($this->mutexId);
            return false;
        }
        shmop_write($this->mutex, $this->processKeyInPool, 0);
        sem_release($this->mutexId);
        return true;
    }

    public function processUnLock() {
        if(!$this->enableProcessMutex)
            return false;
        if($this->useFileLock) {
            return flock($this->lockFileHanlde, LOCK_UN);
        }
        shmop_write($this->mutex, 0, 0);
    }

    public function setWorkDirectory($directory) {
        $directory = realpath($directory);
        if (!is_readable($directory)) {
            throw new FileIOException();
        }
        chdir($directory);
    }

    /**
     *
     * @param string $user
     * @param string $group
     * @return boolean
     * @throws ProcessException
     */
    public function setWorkUser($user, $group) {
        if (!preg_match('/^[a-zA-Z][a-zA-Z\d]*/', $user)) {
            return false;
        }
        $user_info = posix_getpwnam($user);
        if (empty($user_info)) {
            throw new ProcessException();
        }
        $group_info = posix_getgrnam($group);
        if (empty($group_info)) {
            throw new ProcessException();
        }
        if (!in_array($user, $group_info['members'])) {
            throw new ProcessException();
        }
        if (!posix_setegid($group_info['gid'])) {
            return false;
        }
        if (!posix_seteuid($user_info['uid'])) {
            return false;
        }
        return true;
    }

    public function getChildProcessList() {
        return $this->processPool;
    }

    public function setCreateChildFrontCallback($callback, $param = array()) {
        $this->createChildFrontCallback = $callback;
        $this->createChildFrontCallbackParam = $param;
    }

    public function delCreateChildFrontCallback() {
        $this->createChildFrontCallback = null;
        $this->createChildFrontCallbackParam = null;
    }

    /**
     *
     * @param int $num
     * @param callable $callback
     * @param mixed  Zero or more parameters to be passed to the callback
     * @return void
     * @throws ProcessException
     */
    public function createChildProcess($num, $callback) {
        $parentPid = posix_getpid();
        $argv = func_get_args();
        array_shift($argv);
        array_shift($argv);

        for ($i = 0; $i < $num; $i++) {
            $callBackArgv = array();
            if ($this->createChildFrontCallback !== null) {
                $forkFrontCallbackReturn = call_user_func_array($this->createChildFrontCallback, $this->createChildFrontCallbackParam);
                $callBackArgv = array_merge(array($forkFrontCallbackReturn) + $argv);
            } else {
                $callBackArgv = $argv;
            }

            $pid = pcntl_fork();
            if ($pid > 0) {
                $this->processPool[$pid] = $callBackArgv;
                continue;
            } else if ($pid == 0) {
                $exitStatus = self::SUC_EXIT;
                $this->processKeyInPool = count($this->processPool) + 1;
                if (is_callable($callback, true)) {
                    try {
                        call_user_func_array($callback, $callBackArgv);
                    } catch (StandardException $e) {
                        $exitStatus = self::CALLFUNC_ERR;
                        echo $e;
                    }
                } else {
                    $exitStatus = self::NOT_CALLABLE;
                }

                posix_kill($parentPid, SIGCHLD);
                throw new ProcessException();
            } else {
                throw new ProcessException();
            }
        }
    }

    public function registerChlidProcessSignalHandler() {
        pcntl_signal(SIGCHLD, array($this, 'childSignalHandler'));
    }

    private function childSignalHandler($signal) {
        if ($signal == SIGCHLD) {
            $status = 0;
            $childPid = pcntl_waitpid($status);
            $exitCode = pcntl_wexitstatus($status);
            if ($exitCode == self::SUC_EXIT) {
                unset($this->processPool[$childPid]);
            } else {
                $this->chlidExitLog($childPid, $exitCode);
            }
        }
    }

    private function chlidExitLog($childPid, $code) {
        if (count($this->childProcessExitStatusLog) > $this->maxLogNum) {
            array_shift($this->childProcessExitStatusLog);
        }
        $this->childProcessExitStatusLog["$childPid"] = $code;
    }

    /**
     *
     * @return array
     * @throws PipEception
     */
    public function createSocketPair() {
        $ssp = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if (!$ssp)
            throw new PipException();
        return $ssp;
    }

    /**
     * Inter-Process Message read
     * 
     * @param resource $sock
     * @return mixed
     */
    public function IPCRead($sock) {
        $buffer = '';
        $flag = 0;
        while (true) {
            $windowLength = strlen($this->pipMaxDataSize);
            $dataLength = (int) trim(fread($sock, $windowLength));
            if ($flag != 1 && $dataLength <= 0)
                return null;
            $flag = fread($sock, 1);
            $buffer .= fread($sock, $dataLength);
            if ($flag != 1) {
                break;
            }
        }
        $data = unserialize($buffer);
        return $data;
    }

    /**
     * Inter-Process Message write
     *
     * @param resource $sock
     * @param mixed $message
     * @return bool
     */
    public function IPCWrite($sock, $message) {
        $messageString = serialize($message);
        $dataLength = strlen($messageString);
        $writeNum = ceil($dataLength / $this->pipMaxDataSize);
        $windowLength = strlen($this->pipMaxDataSize);
        if ($writeNum > 1) {
            $dataChunkArray = str_split($messageString, $this->pipMaxDataSize);
            for ($i = 0; $i < $writeNum; $i++) {
                $chunkString = $dataChunkArray[$i];
                $chunkLength = strlen($chunkString);
                $dataLengthPart = sprintf("%-{$windowLength}s", $chunkLength);
                $flag = $i >= $writeNum - 1 ? 0 : 1;
                $writeData = "{$dataLengthPart}{$flag}{$chunkString}";
                $len = strlen($writeData);
                fwrite($sock, $writeData, $len);
            }
        } else {
            $dataLengthPart = sprintf("%-{$windowLength}s", $dataLength);
            $writeData = "{$dataLengthPart}0{$messageString}";
            $len = strlen($writeData);
            fwrite($sock, $writeData, $len);
        }
        return true;
    }

    /**
     * create one daemon process
     * 
     * @throws ProcessException
     */
    public function daemon() {
        $oneForkPid = pcntl_fork();
        if ($oneForkPid == -1)
            throw new ProcessException('fork #1 Error');
        if ($oneForkPid > 0)
            exit(0);
        $secForkPid = pcntl_fork();
        if ($secForkPid == -1)
            throw new ProcessException('fork #2 ERROR');
        if ($secForkPid > 0)
            die;
        chdir('/');
        umask('0');
        posix_setsid();
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        $subPid = pcntl_fork();
        if ($subPid == -1)
            throw new ProcessException('fork #3 ERROR');
        if ($subPid > 0)
            exit(0);
    }
    public function __destruct() {
        if($this->useFileLock) {
            fclose($this->lockFileHanlde);
        } else {
            shmop_delete($this->mutex);
            sem_remove($this->mutexId);
        }
    }
}

?>
