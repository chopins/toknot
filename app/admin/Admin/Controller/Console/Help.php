<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\Controller\Console;

use Zend\Reflection\Docblock;
use Toknot\Boot\Logs;
use Toknot\Boot\Kernel;

/**
 * Help
 *
 * @author chopin
 */
class Help {

    /**
     * show this message
     * 
     * @console help
     */
    public function __construct() {
        $dir = dir(__DIR__);
        $ns = 'Admin\Controller\Console\\';
        $script = Kernel::single()->getOption(0);
        $message = [];
        $maxlength = 0;
        while (false !== ($f = $dir->read())) {
            if ($f == '.' || $f == '..') {
                continue;
            }
            list($cn) = explode('.', $f);
            $rf = new \ReflectionClass("$ns$cn");
            $ms = $rf->getMethods();
            foreach ($ms as $m) {
                $docs = $m->getDocComment();
                if ($docs) {
                    $this->parseDocComment($message, $docs, $maxlength);
                }
            }
        }
        $maxlength = $maxlength + 8;
        Logs::colorMessage('The command line usage:', 'white');
        foreach ($message as $line) {
            $prefix = "php $script ";
            $lineMsg = $prefix . str_pad($line[0], $maxlength) . $line[1];
            Logs::colorMessage($lineMsg, 'green');
            $prefixSpace = str_repeat(' ', strlen($prefix));

            Logs::colorMessage($prefixSpace.str_replace("\n", PHP_EOL.$prefixSpace, $line[2]));
        }
    }

    /**
     * parse code comment, the method will get @console tag and short and long description
     * 
     * @param array $message
     * @param string $docs
     * @param int $maxlength
     */
    public function parseDocComment(&$message, $docs, &$maxlength) {
        $docblock = new Docblock($docs);
        if ($docblock->hasTag('console')) {
            $console = $docblock->getTag('console');
            $cmd = $console->getDescription();
            $desc = $docblock->getShortDescription();
            $usage = $docblock->getLongDescription();

            if ($maxlength < ($len = strlen($cmd))) {
                $maxlength = $len;
            }

            $message[] = [$cmd, $desc, $usage];
        }
    }

}
