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

    public function getChildProcessExitStatus() {
        return $this->childProcessExitStatus;
    }

    public function checkEnvironment() {
        if (!function_exists('pcntl_fork')) {
            throw new DependExtensionException();
        }
        if (!function_exists('posix_setuid')) {
            throw new DependExtensionException();
        }
        if (!function_exists('setproctitle')) {
            throw new DependExtensionException();
        }
    }

    public function setWorkDirectory(string $directory) {
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
    public function setWorkUser(string $user, string $group) {
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
        if (!in_array($user, $group_info['member'])) {
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
    public function setChildFrontWork($callback, $param = null) {
        $this->createChildFrontCallback = $callback;
        $this->createChildFrontCallbackParam = $param;
    }
    public function delChildFrontWork() {
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
    public function createChildProcess(int $num, $callback) {
        $parentPid = posix_getpid();
        $argc = func_num_args();
        if ($argc > 2) {
            $argv = func_get_args();
            array_shift($argv);
            array_shift($argv);
        }
        for ($i = 0; $i < $num; $i++) {
            if($this->createChildFrontCallback !== null) {
                $forkFrontCallbackReturn = call_user_func_array($this->createChildFrontCallback, 
                        $this->createChildFrontCallbackParam);
                array_unshift($argv, $forkFrontCallbackReturn);
            }
            $pid = pcntl_fork();
            if ($pid > 0) {
                $this->processPool[$pid] = $argv;
                continue;
            } else if ($pid == 0) {
                $exitStatus = self::SUC_EXIT;
                if (is_callable($callback)) {
                    try {
                        call_user_func_array($callback, $argv);
                    } catch (StandardException $e) {
                        $exitStatus = self::CALLFUNC_ERR;
                    }
                } else {
                    $exitStatus = self::NOT_CALLABLE;
                }
                posix_kill($parent, SIGCHLD);
                exit($exitStatus);
            } else {
                throw new ProcessException();
            }
        }
    }

    public function registerChlidProcessSignalHandler() {
        pcntl_signal(SIGCHLD, array($this, 'chlidSignalHandler'));
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
     * 
     * @param resource $sock
     * @return mixed
     */
    public function IPCRead(resource $sock) {
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
     * 
     * @param resource $sock
     * @param mixed $message
     * @return bool 
     */
    public function IPCWrite(resource $sock, $message) {
        $messageString = serialize($message);
        $dataLength = strlen($messageString);
        $writeNum = ceil($this->pipMaxDataSize / $dataLength);
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
            $writeData = "{$dataLengthPart}0{$chunkString}";
            $len = strlen($writeData);
            fwrite($sock, $writeData, $len);
        }
        return true;
    }

    public function daemon() {
        $fockPid = pcntlFork();
        if ($fockPid == -1)
            throw new ProcessException('fork #1 Error');
        if ($fockPid > 0)
            exit(0);
        $fockPid = pcntl_fork();
        if ($fockPid == -1)
            throw new ProcessException('fork #2 ERROR');
        if ($fockPid > 0)
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

}
?>
