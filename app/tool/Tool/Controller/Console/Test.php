<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;

use PDO;

/**
 * Test
 *
 */
class Test {

    public $cmd;
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
        $pid = $this->process->bloodLock(1);
        if ($pid > 0) {
            exit;
        } else {
            do {
                $lk = $this->process->lock();
                if ($lk) {
                    $this->cmd->message($this->process->getpid() . '|hold lock', 'green');
                } else {
                    $this->cmd->message($this->process->getpid() . '|not hold lock', 'red');
                    continue;
                }
                sleep(1);
                $this->process->unlock();
                $this->cmd->message($this->process->getpid() . '|un-lock', 'blue');
            } while (true);
        }
    }

    public function loop() {
        
    }

}
