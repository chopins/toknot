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
        //$this->process = new \Toknot\Share\Process\Process();
        $this->run();
    }

    public function run() {
        echo $this->kernel->getArg(1);
        echo $this->kernel->getArg('-a');
        echo $this->kernel->getArg('-b');
        echo $this->kernel->getArg('--with-fo');
        echo $this->kernel->getArg('-的');
                echo $this->kernel->getArg(8);

    }

    public function loop() {
        
    }

}

?>
