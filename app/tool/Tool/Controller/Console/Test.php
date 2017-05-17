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
        $title = ['FRFWR','ONGs','Pn22','fs'];
        $data = [
            ['aa', 'vvdss对方的反对', 'dfd的发发热土额', 'ssfewrtw45ts'],
            ['aa', 'vvdss对21方的反对', 'dfd的发44发热土额', 'afertfrewtwe'],
            ['aa', 'vvdss对e方的反对', 'dfd的发发热土额', 'fewrqrsss'],
            ['aa', 'vvdss对方的33反对', 'dfd的发发热土额', 'ssfdfgwerts'],
            ['aa', 'vvdss对sd23rwqr方的反对', 'dfd的发发热土33额', 'fsgwt5t4'],
        ];
        $this->cmd->table($data, $title, true);
    }

    public function loop() {
        
    }

}
