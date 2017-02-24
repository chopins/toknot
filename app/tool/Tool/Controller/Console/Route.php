<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;

use Toknot\Boot\Kernel;
use Toknot\Share\CommandLine;
use Toknot\Boot\Import;
use Zend\Reflection\Docblock;

/**
 * Route
 *
 */
class Route {

    public $appNs;

    /**
     * @console route
     */
    public function __construct() {
        $path = Kernel::single()->getOption(2);
        $output = Kernel::single()->getOption('-o');
        $cmd = new CommandLine;
        if (!is_dir($path)) {
            $cmd->error("must give app dir");
        }
        $apppath = realpath($path);
        $appToNs = ucwords(basename($apppath));
        $dir = $apppath . DIRECTORY_SEPARATOR . $appToNs;
        Import::addPath($dir);
        $this->appNs = $appToNs . PHP_NS . 'Controller' . PHP_NS;
        $ini = $this->dir($dir, $this->appNs);
        if ($output) {
            file_put_contents($output, $ini);
        } else {
            $cmd->message($ini);
        }
    }

    public function dir($dir, $appNs) {
        $path = Import::transformNamespaceToPath($appNs, $dir);
        $d = dir($path);
        $ini = '';
        while (false !== ($f = $d->read())) {
            if ($f == '.' || $f == '..') {
                continue;
            }

            $subpath = "$path/$f";

            if (is_dir($subpath)) {
                $ini .= $this->dir($dir, $appNs . $f . PHP_NS);
                continue;
            }

            $class = $appNs . basename($f, '.php');

            $rf = new \ReflectionClass($class);
            $ms = $rf->getMethods();
            foreach ($ms as $m) {
                $routepath = $this->parseDocComment($m);
                if ($routepath !== false) {
                    $method = $m->getName();
                    $cls = str_replace($this->appNs, '', $class);
                    $routepath = $routepath ? $routepath : strtolower('/' . str_replace(PHP_NS, '/', $cls));
                    $cont = str_replace(PHP_NS, '.', $cls);
                    $routename = strtolower(str_replace(PHP_NS, '-', $cls));
                    $mp = $m->isConstructor() || $m->isDestructor() ? '' : ":$method";
                    $ini .= <<<EOF
[$routename]
path = $routepath
controller = {$cont}{$mp}
method = GET

EOF;
                }
            }
        }
        return $ini;
    }

    /**
     * parse code comment, the method will get @console tag and short and long description
     * 
     * @param array $message
     * @param string $docs
     * @param int $maxlength
     * @return boolean|string
     */
    public function parseDocComment($m) {
        $docs = $m->getDocComment();
        if ($docs) {
            $docblock = new Docblock($docs);
            if ($docblock->hasTag('route')) {
                return $docblock->getTag('route')->getDescription();
            }
        }
        return false;
    }

}
