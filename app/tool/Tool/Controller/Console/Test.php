<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;

use Toknot\Exception\BaseException;
use PDO;

/**
 * Test
 *
 */
class Test {

    /**
     *
     * @var \Toknot\Share\CommandLine
     */
    public $cmd;

    /**
     *
     * @var \Toknot\Share\Process\Process 
     */
    public $process;

    /**
     * @console test
     */
    public function __construct() {
        $this->cmd = new \Toknot\Share\CommandLine;
        $this->process = new \Toknot\Share\Process\Process();
        $this->run();
    }

    public function run() {
        $pid1 = $this->process->fork();
        $local = '127.0.0.1';
        $port = 98899;
        if ($pid1 === 0) {
            $this->process->taskQueue($local, $port, function($message, $time, $pid) {
                $this->cmd->message("[$time][$pid]$message");
            });
            return;
        }
//        $this->process->wait($pid1);
//        die;
        $pid2 = $this->process->fork();
        if ($pid2 > 0) {
            $this->process->setProcessTitle('php:main');
            $this->process->wait($pid1);
            exit;
        } else {
            $pid = $this->process->multiProcess(1);
            if ($pid > 0) {
                $this->process->setProcessTitle('php:multi child');
                exit;
            }
            do {
                $this->cmd->message('add task', 'blue');
                try {
                    $re = $this->process->addTask($local, $port, 'message:');

                    $this->cmd->message('add success', 'green');
                } catch (BaseException $e) {
                    $this->cmd->message('add error', 'red');
                    $re = false;
                }

                sleep(1);
            } while (!$re);
        }
    }

    public function loop() {
        
    }

}
