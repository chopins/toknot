<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;

/**
 * Test
 *
 */
class Test {

    use \Toknot\Boot\ObjectHelper;

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
    public $kernel;

    /**
     * @console test
     */
    public function __construct() {
        $this->kernel = \Toknot\Boot\Kernel::single();
        $this->cmd = new \Toknot\Share\CommandLine;
        $this->process = new \Toknot\Share\Process\Process();
        $this->run();
    }

    public function run() {
       $number = new \Toknot\Share\ChineseNumber(5635001243389);
       $number = new \Toknot\Share\ChineseNumber(19);
       $this->cmd->message($number);
    }

    public function loop() {
        
    }

}

?>
